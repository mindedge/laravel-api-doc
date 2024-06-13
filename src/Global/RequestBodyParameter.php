<?php

namespace MindEdge\LaravelApiDoc\Global;

use Exception;

class RequestBodyParameter
{
    public string $name;
    public string $description;
    public string $type;
    public bool $required = false;

    public function __construct(array|object $props = [])
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->name = $props->name ?? '';
        $this->description = $props->description ?? '';
        $this->type = $props->type ?? 'string';
        $this->required = $props->required ?? false;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIsRequired(): bool
    {
        return $this->required;
    }

    /**
     * Converts the properties of this class into an array format
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'required' => $this->required,
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
        $type = $openApiTypeMap[$this->type] ?? 'string';

        $data = [
            $this->name => [
                'type' => $type,
                'description' => $this->description,
            ]
        ];

        if (strtolower($this->type) === 'array') {
            $data[$this->name]['items'] = ['type' => 'object'];
        }

        return $data;
    }
}
