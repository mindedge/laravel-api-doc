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
    }
}
