<?php

namespace Jrogaishio\LaravelApiDoc\Global;

use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Yaml;

class Document
{
    public string $name;
    public string $description;
    public string $version;
    public Collection $servers;
    public Collection $tags;
    public Collection $components;
    public bool $deprecated;
    public bool $enabled;
    public Collection $metadata;
    public Collection $routes;

    public function __construct(array|object $props)
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->name = $props->name ?? '';
        $this->description = $props->description ?? '';
        $this->version = $props->version ?? '';
        $this->servers = collect($props->servers ?? []);
        $this->tags = collect($props->tags ?? []);
        $this->components = collect($props->components ?? []);
        $this->enabled = $props->enabled ?? false;
        $this->deprecated = $props->deprecated ?? false;
        $this->metadata = collect($props->metadata ?? []);
        $this->routes = collect($props->routes ?? []);
    }

    public function toArray()
    {
        $data = [
            'title' => $this->name,
            'description' => $this->description,
            'termsOfService' => '',
            'contact' => '',
            'license' => ['name' => '', 'url' => ''],
            'externalDocs' => [
                'description' => '',
                'url' => '',
            ],
            'servers' => $this->servers->toArray(),
            'tags' => $this->tags->map(function ($t) {
                return $t->toArray();
            })->toArray(),
            'paths' => [],
        ];

        foreach ($this->routes as $route) {
            if (empty($data['paths']['/' . $route->path])) {
                $data['paths']['/' . $route->path] = [];
            }
            $data['paths']['/' . $route->path][$route->method] = [
                'tags' => $route->tags ?? [],
                'summary' => $route->phpdoc->title,
                'description' => $route->phpdoc->description,
            ];
        }

        return $data;
    }

    public function toOpenApi($version = '3.1')
    {
        return Yaml::dump($this->toArray(), 100, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
