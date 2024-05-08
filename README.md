# Laravel-Api-Doc

Automatically create documentation for Laravel api's off the registered routes and [PHPDoc comments](https://www.phpdoc.org/)

## Table of Contents

- [Installation](#installation)
- [How it works](#how-it-works)
- [Usage](#usage)
- [Advanced Usage](#advanced-usage)
- [License](#license)

---

## Installation

You can install the package via composer:

```bash
composer require jrogaishio/laravel-api-doc
```

## How it works

Take the below example route url and controller method:

POST Request: `/1.0/pages/1234/posts`

```php
// PostController.php

/**
 * Create a new post
 * @param PostCreateRequest $request
 * @param int $page_id The page to create this post on
 */
public function create(PostCreateRequest $request, int $page_id)
{
    $post = Post::create([
        'page_id' => $page_id,
        'title' => 'My Sample Post',
        'body' => 'Hello world!',
    ]);

    return response($post, 201);
}
```

This library will scan the registered Laravel routes, and see that `/1.0/pages/1234/posts` is registered to `PostController@create`.

It will then scan the `create` method definition for route parameters. This is so we always grab the true parameter list even if the PHPDoc comments don't include them.

If the first parameter is an instance of `Illuminate\Foundation\Http\FormRequest` we will scan the class for a `rules()` method to build a list of the form/query parameters.

The we will continue parsing the controller `create` method to get the route parameters. In this example we have `$page_id` so we will check the PHPDoc comments to grab a description of what this parameter does.

## Usage

Basic Usage:

```php

use Jrogaishio\LaravelApiDoc\Doc;

// The base api path (typically the version) to scan for routes
// Doc::generate() defaults to '1.0'
$baseApiPath = '1.0';
$document = Doc::generate($baseApiPath);

// toOpenApi defaults to OpenApi version 3.0.3 and an export type of yaml
echo $document->toOpenApi();
```

Get the routes as an OpenApi json object:

```php

use Jrogaishio\LaravelApiDoc\Doc;

$document = Doc::generate('1.0');
print_r($document->toOpenApi(format: 'json'));
```

Get the routes as an OpenApi php array:

```php

use Jrogaishio\LaravelApiDoc\Doc;

$document = Doc::generate('1.0');
print_r($document->toOpenApi(format: 'array'));
```

## Advanced Usage

You can supply a partially pre-filled document to `Doc::generate()` to set default values

```php

use Jrogaishio\LaravelApiDoc\Doc;
use Jrogaishio\LaravelApiDoc\Global\Document;

$baseDocument = new Document([
    'name' => 'My Awesome Api',
    'description' => 'The Awesome API Documentation',
    'license' => ['name' => '', 'url' => ''],
    'contact' => 'support@example.com',
    'version' => '1.0'
]);
$document = Doc::generate(doc: $baseDocument);

echo $document->toOpenApi();
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
