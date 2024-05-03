<?php

namespace Jrogaishio\LaravelApiDoc\Global;

class RouteDocument
{
    public $path;
    public $tags;
    public $controller;
    public $action;
    public $method;
    public $middleware;
    public $parameters;
    public $phpdoc;

    public function __construct(array|object $props)
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->path = $props->path ?? '';
        $this->tags = $props->tags ?? collect([]);
        $this->controller = $props->controller ?? '';
        $this->action = $props->action ?? '';
        $this->method = $props->method ?? '';
        $this->middleware = $props->middleware ?? collect([]);
        $this->parameters = $props->parameters ?? collect([]);
        $this->phpdoc = $props->phpdoc ?? collect([]);
    }
}
