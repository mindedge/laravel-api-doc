<?php

namespace MindEdge\LaravelApiDoc\Global;

use Exception;
use Illuminate\Support\Collection;
use ReflectionClass;

class RouteParameter
{
    public string $name;
    public string $description;
    public string $type;
    public string $in;
    public bool $required = false;

    public function __construct(array|object $props = [])
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->name = $props->name ?? '';
        $this->description = $props->description ?? '';
        $this->in = $props->in ?? '';
        $this->type = $props->type ?? [];
        $this->required = $props->required ?? false;
    }

    /**
     * Get if the parameter is a Illuminate\Http\Request or Illuminate\Foundation\Http\FormRequest object
     *
     * @return bool If it is a request otherwise false
     */
    public function getIsRequest(): bool
    {
        return $this->getIsIlluminateRequest() || $this->getIsFormRequest();
    }

    /**
     * Get if the parameter is an instance of Illuminate\Http\Request
     *
     * @return bool If it is a request otherwise false
     */
    public function getIsIlluminateRequest(): bool
    {
        return class_exists($this->type) && $this->type === 'Illuminate\Http\Request';
    }

    /**
     * Get if the parameter is a Illuminate\Foundation\Http\FormRequest object
     *
     * @return bool If it is a request otherwise false
     */
    public function getIsFormRequest(): bool
    {
        return (class_exists($this->type) && is_a($this->type, 'Illuminate\Foundation\Http\FormRequest', true));
    }

    private function parseRule(mixed $rule): object
    {
        $description = [];
        $type = 'null';
        $isRequired = false;

        // Skip this rule since we can't parse it
        if (is_string($rule)) {
            $description[] = $rule;
            $isRequired = preg_match('/(^|\|)required($|\|)/i', $rule);
            $ruleTypeMap = [
                'nullable' => 'null',
                'boolean' => 'bool',
                'integer' => 'int',
                'numeric' => 'float',
                'string' => 'string',
                'array' => 'array',
                'date' => 'string',
                'url' => 'string',
                'email' => 'string',
            ];
            preg_match('/(^|\|)(?<type>string|integer|boolean|array|nullable|date|url|email|)($|\|)/i', $rule, $typeMatch);

            if (!empty($typeMatch['type'])) {
                $type = !empty($ruleTypeMap[$typeMatch['type']]) ? $ruleTypeMap[$typeMatch['type']] : 'null';
            }
        } elseif (is_array($rule)) {

            // Loop over any custom rules
            foreach ($rule as $customRuleObject) {
                if (is_string($customRuleObject)) {
                    $result = $this->parseRule($customRuleObject);
                    $type = $result->type;
                    $description[] = $result->description;
                    // Retain the required status if it's already set to true
                    $isRequired = $isRequired || $result->isRequired;
                } elseif (
                    is_object($customRuleObject) &&
                    (is_a($customRuleObject, 'Illuminate\Contracts\Validation\Rule')
                        || is_a($customRuleObject, 'Illuminate\Contracts\Validation\ValidationRule')
                        || is_a($customRuleObject, 'Illuminate\Contracts\Validation\ImplicitRule')
                        || is_a($customRuleObject, 'Illuminate\Contracts\Validation\DataAwareRule')
                    )
                ) {
                    $reflect = new ReflectionClass($customRuleObject);
                    $description[] = $reflect->getShortName();
                }
            }
        }
        $ret = (object) [
            'type' => $type,
            'description' => implode('|', $description),
            'isRequired' => $isRequired,
        ];

        return $ret;
    }

    public function getQueryParameters(): Collection
    {
        $result = collect([]);
        if ($this->getIsFormRequest()) {
            $formRequest = new $this->type();
            foreach ($formRequest?->rules() ?? [] as $key => $rule) {
                $parsed = $this->parseRule($rule);

                $param = new RouteParameter([
                    'name' => $key,
                    'description' => $parsed->description,
                    'in' => 'query',
                    'type' => $parsed->type,
                    'required' => $parsed->isRequired,
                ]);

                $result->push($param);
            }
        }
        return $result;
    }

    /**
     * Get the primitive php type of the parameter
     *
     * Eloquent Models will have their key type checked (string/int)
     *
     * @return string The type
     */
    public function getPrimitiveType(): string
    {
        $primitiveType = 'null';
        // The type is already a primitive, just return it
        if (in_array($this->type, ['null', 'bool', 'int', 'float', 'string', 'array', 'object', 'callable', 'resource'])) {
            $primitiveType = $this->type;
        } elseif ($this->getIsRequest()) {
            // The parameter is a request parameter
            $primitiveType = 'object';
        } elseif (class_exists($this->type) && is_a($this->type, 'Illuminate\Database\Eloquent\Model', true)) {
            // If the type is a model then try and infer the primary key type
            $model = new $this->type();
            $primitiveType = $model?->getKeyType() ?? 'null';
        } else {
            // Cannot infer the type, set to null
            $primitiveType = 'null';
        }

        return $primitiveType;
    }

    /**
     * Converts the properties of this class into an array format
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'in' => $this->in,
            'type' => $this->type,
            'isRequest' => $this->getIsRequest(),
            'primitiveType' => $this->getPrimitiveType(),
        ];

        return $data;
    }

    /**
     * Converts the parameter into the OpenApi format
     *
     * @param string $version='3.0.3'
     * @return array The OpenApi formatted data
     */
    public function toOpenApi(string $version = '3.0.3'): array
    {
        $supportedVersions = ['3.0.3'];
        if (!in_array($version, $supportedVersions)) {
            throw new Exception('Unsupported Openapi version. Supported versions are: ' . implode(',', $supportedVersions));
        }

        $primitive = $this->getPrimitiveType();
        // Map of PHP types to their OpenAPI equivalents
        $openApiTypeMap = [
            'null' => 'string',
            'bool' => 'boolean',
            'int' => 'integer',
            'float' => 'number',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
            'callable' => 'object',
            'resource' => 'object',
        ];
        // If the primitive type isn't in the list of types accepted by openapi, default to string
        // This is usually when the php type is 'mixed' and cannot be determined
        $type = $openApiTypeMap[$primitive] ?? 'string';
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'in' => $this->in,
            'required' => $this->required,
            'schema' => ['type' => $type]
        ];

        // If the type is an array we need to supply what type the items are
        // Default to object since there's no way we can figure that out
        if ($type === 'array') {
            $data['schema']['items'] = ['type' => 'object'];
        }

        return $data;
    }
}
