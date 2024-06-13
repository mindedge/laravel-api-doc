<?php

namespace MindEdge\LaravelApiDoc\Global;

use Illuminate\Support\Collection;
use Exception;

class RequestBody
{
    public string $responseType = 'application/json';
    public Collection $parameters;

    public function __construct(array|object $props = [])
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->responseType = $props->responseType ?? 'application/json';
        $this->parameters = collect($props->parameters ?? []);
    }

    /**
     * Adds a RequestBodyParameter to the parameters list
     *
     * @param RequestBodyParameter $parameter
     */
    public function addParameter(RequestBodyParameter $parameter): void
    {
        $this->parameters->push($parameter);
    }

    /**
     * Converts the properties of this class into an array format
     */
    public function toArray(): array
    {
        $data = [
            'description' => $this->responseType,
            'parameters' => $this->parameters->map(function ($p) {
                return $p->toArray();
            })->toArray(),
        ];

        return $data;
    }

    /**
     * Converts the request body into the OpenApi format
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

        $data = [
            $this->responseType => [
                'schema' => [
                    'type' => 'object',
                    'properties' => collect([]),
                    'required' => collect([]),
                ],
                'example' => [],
            ]
        ];

        // Format and inject request body parameter fields
        foreach ($this->parameters as $param) {
            $data[$this->responseType]['schema']['properties'] = $data[$this->responseType]['schema']['properties']->merge($param->toOpenApi($version));

            if ($param->getIsRequired()) {
                $data[$this->responseType]['schema']['required'] = $data[$this->responseType]['schema']['required']->merge($param->getName());
            }
        }

        $data[$this->responseType]['schema']['properties'] = $data[$this->responseType]['schema']['properties']->toArray();
        $data[$this->responseType]['schema']['required'] = $data[$this->responseType]['schema']['required']->toArray();

        if (empty($data[$this->responseType]['schema']['required'])) {
            unset($data[$this->responseType]['schema']['required']);
        }
        if (empty($data[$this->responseType]['example'])) {
            unset($data[$this->responseType]['example']);
        }

        return $data;
    }
}
