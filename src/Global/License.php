<?php

namespace MindEdge\LaravelApiDoc\Global;

class License
{
    public string $name;
    public string $url;

    public function __construct(array|object $props = [])
    {
        if (is_array($props)) {
            $props = (object) [...$props];
        }
        $this->name = $props->name ?? '';
        $this->url = $props->url ?? '';
    }

    /**
     * Converts the properties of this class into an array format
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'url' => $this->url,
        ];

        return $data;
    }
}
