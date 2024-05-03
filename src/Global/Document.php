<?php

namespace Jrogaishio\LaravelApiDoc\Global;

class Document
{
    public $name;
    public $description;
    public $version;
    public $servers;
    public $tags;
    public $components;
    public $deprecated;
    public $enabled;
    public $metadata;
    public $routes;

    public function __set($name, $value)
    {
        $method = 'set' . ucfirst($name);
        $this->{$method}($value);
    }

    public function setName(string $value)
    {
        $this->name = $value;
    }
    public function setDescription(string $value)
    {
        $this->description = $value;
    }
    public function setVersion(string $value)
    {
        $this->version = $value;
    }
    public function setServers($value)
    {
        $this->servers = collect($value);
    }
    public function setTags($value)
    {
        $this->tags = collect($value);
    }
    public function setComponents($value)
    {
        $this->components = collect($value);
    }
    public function setDeprecated(bool $value)
    {
        $this->deprecated = $value;
    }
    public function setEnabled(bool $value)
    {
        $this->enabled = $value;
    }
    public function setMetadata($value)
    {
        $this->metadata = collect($value);
    }
    public function setRoutes($value)
    {
        $this->routes = collect($value);
    }

    public function __construct(array|object $props)
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->name = $props->name ?? '';
        $this->description = $props->description ?? '';
        $this->version = $props->version ?? '';
        $this->servers = $props->servers ?? collect([]);
        $this->tags = $props->tags ?? collect([]);
        $this->components = $props->components ?? collect([]);
        $this->enabled = $props->enabled ?? false;
        $this->deprecated = $props->deprecated ?? false;
        $this->metadata = $props->metadata ?? collect([]);
        $this->routes = $props->routes ?? collect([]);
    }
}
