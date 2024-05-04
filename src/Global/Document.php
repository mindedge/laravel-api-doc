<?php

namespace Jrogaishio\LaravelApiDoc\Global;

use Exception;
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
    public array $metadata;
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
        $this->metadata = $props->metadata ?? [];
        $this->routes = collect($props->routes ?? []);
    }

    public function toArray()
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'servers' => $this->servers->toArray(),
            'tags ' => $this->tags->map(function ($t) {
                return $t->toArray();
            })->toArray(),
            'components ' => $this->components->toArray(),
            'enabled' => $this->enabled,
            'deprecated' => $this->deprecated,
            'metadata ' => $this->metadata,
            'routes' => $this->routes->map(function ($r) {
                return $r->toArray();
            })->toArray(),
        ];

        return $data;
    }

    public function toOpenApi(string $version = '3.1', string $format = 'yaml')
    {
        $data = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => $this->name,
                'description' => $this->description,
                'termsOfService' => '',
                'contact' => ['email' => 'sample@example.com'],
                'license' => ['name' => '', 'url' => ''],
                'version' => $this->version,
            ],
            'externalDocs' => [
                'description' => '',
                'url' => '',
            ],
            'servers' => $this->servers->toArray(),
            'tags' => $this->tags->map(function ($t) {
                $tag = ['name' => $t->name, 'description' => $t->description];
                if (!empty($t->externalDocs)) {
                    $tag['externalDocs'] = $t->externalDocs;
                }
                return $tag;
            })->toArray(),
            'paths' => [],
        ];

        foreach ($this->routes as $route) {
            if (empty($data['paths']['/' . $route->path])) {
                $data['paths']['/' . $route->path] = [];
            }

            $data['paths']['/' . $route->path][strtolower($route->method)] = [
                'tags' => $route->tags ?? [],
                'summary' => $route->phpdoc->title,
                'description' => $route->phpdoc->description,
                'parameters' => $route->parameters->filter(function ($p) {
                    return $p->name !== 'request';
                })->map(function ($p) {
                    return ['name' => $p->name, 'in' => $p->in, 'required' => true, 'schema' => ['type' => 'string']];
                })->values()->toArray(),
                'responses' => $route->responses,
            ];
        }

        if ($format === 'yaml') {
            return Yaml::dump($data, 10000, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        } elseif ($format === 'json') {
            return json_encode($data);
        } else {
            throw new Exception('Invalid format! Can only export to yaml or json');
        }
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
