<?php

namespace Jrogaishio\LaravelApiDoc\Global;

class RouteParameter
{
    public string $name;
    public string $description;
    public string $type;
    public string $in;

    public function __construct(array|object $props = [])
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->name = $props->name ?? '';
        $this->description = $props->description ?? '';
        $this->in = $props->in ?? '';
        $this->type = $props->type ?? [];
    }

    /**
     * Get if the parameter is a Request or FormRequest object
     *
     * @return bool If it is a request otherwise false
     */
    public function getIsRequest(): bool
    {
        $isRequest = false;
        if (class_exists($this->type)) {
            // Ensure the parameter is NOT a request parameter
            if ($this->type === 'Illuminate\Http\Request' || is_a($this->type, 'Illuminate\Foundation\Http\FormRequest', true)) {
                $isRequest = true;
            }
        }
        return $isRequest;
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
        } elseif (!$this->getIsRequest()) {
            // If the type is a model then try and infer the primary key type
            if (class_exists($this->type) && is_a($this->type, 'Illuminate\Database\Eloquent\Model', true)) {
                $model = new $this->type();
                $primitiveType = $model?->getKeyType() ?? 'null';
            } else {
                // Cannot infer the type, set to null
                $primitiveType = 'null';
            }
        } else {
            // The parameter is a request parameter
            $primitiveType = 'object';
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
}
