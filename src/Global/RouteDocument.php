<?php

namespace Jrogaishio\LaravelApiDoc\Global;

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
    public array $responses;
    public object $phpdoc;

    public function __construct(array|object $props)
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
        $this->phpdoc = $props->phpdoc ?? (object) [];
        $this->responses = [
            200 => [
                'description' => '',
            ]
        ];
    }

    public function toArray()
    {
        return [
            'path' => $this->path,
            'tags' => $this->tags,
            'controller' => $this->controller,
            'action' => $this->action,
            'method' => $this->method,
            'middleware' => $this->middleware->toArray(),
            'parameters' => $this->parameters->toArray(),
            'responses' => $this->responses,
            'phpdoc' => $this->phpdoc,
        ];
    }
}
