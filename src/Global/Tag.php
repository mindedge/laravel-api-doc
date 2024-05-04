<?php

namespace Jrogaishio\LaravelApiDoc\Global;

class Tag
{
    public $name;
    public $description;
    public $externalDocs;

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
    public function externalDocs(array $value)
    {
        $this->externalDocs = $value;
    }

    public function __construct(array|object $props)
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->name = $props->name ?? '';
        $this->description = $props->description ?? '';
        $this->externalDocs = $props->externalDocs ?? [];
    }

    public function toArray()
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'externalDocs' => $this->externalDocs,
        ];

        return $data;
    }
}
