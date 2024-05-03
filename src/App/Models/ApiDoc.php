<?php

namespace Jrogaishio\LaravelApiDoc\App\Models;

use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model as BaseModel;

class ApiDoc extends BaseModel
{
    use Uuid;

    protected $table = 'api_docs';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'servers' => 'array',
        'tags' => 'array',
        'components' => 'array',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'name',
        'description',
        'version',
        'servers',
        'tags',
        'components',
        'deprecated',
        'enabled',
        'metadata',
    ];

    public function routes()
    {
        return $this->hasMany(ApiDocRoute::class);
    }
}
