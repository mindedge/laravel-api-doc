<?php

namespace Jrogaishio\LaravelApiDoc\App\Models;

use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Model as BaseModel;

class ApiDocRoute extends BaseModel
{
    use Uuid;

    protected $table = 'api_doc_routes';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
        'middleware' => 'array',
        'parameters' => 'array',
        'responses' => 'array',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'api_doc_id',
        'name',
        'description',
        'path',
        'controller',
        'action',
        'method',
        'middleware',
        'tags',
        'parameters',
        'responses',
        'deprecated',
        'enabled',
        'metadata',
    ];

    public function doc()
    {
        return $this->belongsTo(ApiDoc::class);
    }
}
