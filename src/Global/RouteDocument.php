<?php

namespace MindEdge\LaravelApiDoc\Global;

use Exception;
use Illuminate\Support\Collection;

class RouteDocument
{
    public string $path;
    public array $tags;
    public string $controller;
    public string $action;
    public string $method;
    public Collection $middleware;
    public Collection $parameters;
    public Collection $requestBody;
    public array $responses;
    public object $phpdoc;

    public function __construct(array|object $props = [])
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->path = $props->path ?? '';
        $this->tags = $props->tags ?? [];
        $this->controller = $props->controller ?? '';
        $this->action = $props->action ?? '';
        $this->method = $props->method ?? '';
        $this->middleware = collect($props->middleware ?? []);
        $this->parameters = collect($props->parameters ?? []);
        $this->requestBody = collect($props->requestBody ?? []);
        $this->phpdoc = $props->phpdoc ?? (object) [];
        $this->responses = [
            200 => [
                'description' => '',
            ]
        ];
    }

    /**
     * Get the route summary from phpdoc comments
     *
     * @return string
     */
    public function getSummary(): string
    {
        return $this?->phpdoc?->title ?? '';
    }

    /**
     * Get the route description from phpdoc comments
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this?->phpdoc?->description ?? '';
    }

    /**
     * Converts the properties of this class into an array format
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'tags' => $this->tags,
            'controller' => $this->controller,
            'action' => $this->action,
            'method' => $this->method,
            'middleware' => $this->middleware->toArray(),
            'parameters' => $this->parameters->map(function ($p) {
                return $p->toArray();
            })->toArray(),
            'requestBody' => $this->requestBody->map(function ($p) {
                return $p->toArray();
            })->toArray(),
            'responses' => $this->responses,
            'phpdoc' => $this->phpdoc,
        ];
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

        $data = [
            'tags' => $this->tags ?? [],
            'summary' => $this->getSummary(),
            'description' => $this->getDescription(),
            'parameters' => $this->parameters->filter(function ($p) {
                return !$p->getIsRequest();
            })->map(function ($p) use ($version) {
                return $p->toOpenApi($version);
            })->values()->toArray(),

            'requestBody' => [
                'description' => '',
                'required' => true,
                'content' => collect([])
            ],

            'responses' => $this->responses,
            'x-middleware' => $this->middleware->toArray(),
        ];

        // Remove empty fields and the request body if it's a get/delete request
        if ($this->requestBody->count() === 0 || strtolower($this->method) === 'get' || strtolower($this->method) === 'delete') {
            unset($data['requestBody']);
        } else {
            foreach ($this->requestBody as $requestBody) {
                $data['requestBody']['content'] = $data['requestBody']['content']->merge($requestBody->toOpenApi());
            }
            $data['requestBody']['content'] = $data['requestBody']['content']->toArray();
        }

        return $data;
    }
}
