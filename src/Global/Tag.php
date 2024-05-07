<?php

namespace Jrogaishio\LaravelApiDoc\Global;

class Tag
{
    public string $key;
    public string $name;
    public string $description;
    public array $externalDocs;

    public function __construct(array|object $props = [])
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->key = $props->key ?? '';
        $this->name = $props->name ?? '';
        $this->description = $props->description ?? '';
        $this->externalDocs = $props->externalDocs ?? [];
    }

    /**
     * Converts the properties of this class into an array format
     */
    public function toArray(): array
    {
        $data = [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
        ];

        if (!empty($this->externalDocs)) {
            $data['externalDocs'] = $this->externalDocs;
        }

        return $data;
    }
}
