<?php

namespace Jrogaishio\LaravelApiDoc;

use Jrogaishio\LaravelApiDoc\App\Models\ApiDoc;
use Jrogaishio\LaravelApiDoc\App\Models\ApiDocRoute;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use Exception;
use Jrogaishio\LaravelApiDoc\Global\Document;
use Jrogaishio\LaravelApiDoc\Global\RouteDocument;
use Jrogaishio\LaravelApiDoc\Global\Tag;

class Doc
{
    /**
     * Determine all the routes, route parameters, and middleware used and return them as an object
     *
     * @param $version=1.0 The api version to target the documentation generation for
     * @param string $title='My Api' The api info title
     * @param string $description='My Sample Api' The api info description
     *
     * @return Document The generated document with routes
     */
    public static function generate($version = '1.0', $title = 'My Api', $description = 'My Sample Api'): Document
    {
        $doc = new Document([
            'version' => $version,
            'name' => $title,
            'description' => $description,
        ]);

        $routeCollection = Route::getRoutes();
        $controllers = collect([]);

        // Loop over all the routes, exclude certain ones, register their data, and add their controllers to $controllers
        // to figure out the controller comments aka tag info
        foreach ($routeCollection as $route) {
            $uri = $route->uri();

            // Exclude any routes of the wrong api version. This also excludes non-prefixed routes
            if (!preg_match('/^\/?' . preg_quote($version) . '/', $uri)) {
                continue;
            }
            // Trim the version off the uri since we're capturing it on the document itself
            $uri = preg_replace('/^\/?' . preg_quote($version) . '\/?/', '', $uri);

            // Make sure the registered route actually exists and points to a valid class/method
            if ($route->getControllerClass() && method_exists($route->getControllerClass(), $route->getActionMethod())) {
                $methods = $route->methods();

                $methods = array_filter($methods, function ($method) {
                    return !in_array(strtoupper($method), ['HEAD', 'OPTIONS']);
                });

                foreach ($methods as $method) {
                    $routeItem = new RouteDocument([
                        'path' => $uri,
                        'controller' => $route->getControllerClass(),
                        'action' => $route->getActionMethod(),
                        'method' => $method ?? '',
                        'middleware' => collect($route->gatherMiddleware()),
                    ]);

                    $controllerMatch = [];

                    // Resolve the controller name to a tag and remove the `Controller` text
                    // Eg MyFooController -> MyFoo
                    preg_match('/(?<name>\w+)Controller/', $route->getControllerClass(), $controllerMatch);
                    if (!empty($controllerMatch['name'])) {
                        $routeItem->tags = [$controllerMatch['name']];

                        // Make sure we only add it once
                        if (!$controllers->contains($route->getControllerClass())) {
                            $controllers->push($route->getControllerClass());
                        }
                    }

                    // Figure out the parameters of the route method since we don't trust PHPDoc to 100% be accurate to reality
                    $rMethod = new ReflectionMethod($route->getControllerClass(), $route->getActionMethod());
                    $params = $rMethod->getParameters();

                    // Save the phpdoc info
                    $routeItem->phpdoc = self::phpdoc($rMethod);


                    // Figure out the parameter name, type, etc
                    foreach ($params as $param) {
                        $routeItem->parameters[] = (object) [
                            'name' => $param->getName(),
                            'type' => $param?->getType()?->getName() ?? 'mixed',
                            'in' => 'path',
                            'value' => '',
                        ];
                    }

                    $doc->routes->push($routeItem);
                }
            }
        }

        // Get the phpdoc comments at the top of controllers to use them as the group/tag text
        foreach ($controllers as $controller) {
            $rClass = new ReflectionClass($controller);
            $phpdoc = self::phpdoc($rClass);
            preg_match('/(?<name>\w+)Controller/', $controller, $controllerMatch);

            if (!empty($phpdoc->title) && !empty($controllerMatch)) {
                $tag = new Tag([
                    'key' => $controllerMatch['name'],
                    'name' => $phpdoc->title,
                    'description' => $phpdoc->description
                ]);
                $doc->tags->push($tag);
            }
        }

        return $doc;
    }

    /**
     * Sync the documentation to the database
     *
     * @param string $version='1.0' The api version to save this as
     * @param string $title='My Api' The api info title
     * @param string $description='My Sample Api' The api info description
     *
     * @return ApiDoc
     */
    public static function sync(string $version = '1.0', $title = 'My Api', $description = 'My Sample Api'): ApiDoc
    {
        $doc = self::generate($version);
        $doc->name = $title;
        $doc->description = $description;

        $docModel = self::syncDoc($version, $doc);
        $routes = self::syncRoutes($version, $doc);

        $docModel->setRelation('routes', $routes);

        return $docModel;
    }

    /**
     * Sync just the top level document but not the routes to the database
     *
     * @param string $version='1.0' The api version to save this as
     * @param Document|null $generatedDoc = null The documented generated by Doc::generate(). If not supplied the doc will be auto-generated
     * @return ApiDoc
     */
    public static function syncDoc($version = '1.0', Document|null $generatedDoc = null): ApiDoc
    {
        // Generate the doc since none was passed
        if ($generatedDoc === null) {
            $generatedDoc = self::generate($version);
        }
        $doc = ApiDoc::updateOrCreate([
            'version' => $version
        ], [
            'name' => $generatedDoc->name,
            'description' => $generatedDoc->description,
            'version' => $version,
            'tags' => $generatedDoc->tags,
        ]);

        $doc->tags = $generatedDoc->tags;
        $doc->save();

        return $doc;
    }

    /**
     * Sync just the top level document but not the routes to the database
     *
     * @param string $version='1.0' The api version to save this as
     * @param Document|null $generatedDoc = null The documented generated by Doc::generate(). If not supplied the doc will be auto-generated
     * @return \Illuminate\Support\Collection
     */
    public static function syncRoutes($version = '1.0', $generatedDoc = null): \Illuminate\Support\Collection
    {
        $docRoutes = collect([]);
        $existingDoc = ApiDoc::with('routes')->where('version', $version)->first();

        if (empty($existingDoc)) {
            throw new Exception("Cannot sync routes since no doc for version '{$version}' exists! Run syncDoc first!");
        }

        // Generate the doc since none was passed
        if ($generatedDoc === null) {
            $generatedDoc = self::generate($version);
        }

        // Loop over all the existing ApiDocRoute models and delete any that have been removed from the api
        foreach ($existingDoc->routes as $docRoute) {
            $exists = $generatedDoc->routes->search(function ($route) use ($docRoute) {
                return $route->path === $docRoute->path;
            });

            // Route no longer exists / isn't tracked anymore. Remove it from the docs
            if (!$exists) {
                $docRoute->forceDelete();
            }
        }

        foreach ($generatedDoc->routes as $route) {
            $docRoute = ApiDocRoute::updateOrCreate([
                'api_doc_id' => $existingDoc->id,
                'path' => $route->path,
                'method' => $route->method,
            ], [
                'api_doc_id' => $existingDoc->id,
                'name' => $route->doc->title ?? '',
                'description' => $route->doc->description ?? '',
                'path' => $route->path,
                'controller' => $route->controller,
                'action' => $route->action,
                'method' => $route->method,
                'middleware' => $route->middleware,
                'tags' => $route->tags,
                'enabled' => false,
                'parameters' => $route->parameters,
                'responses' => [],
                'metadata' => [],
            ]);
            $docRoutes->push($docRoute);
        }
        return $docRoutes;
    }

    private static function phpdoc(ReflectionClass|ReflectionMethod $r): object
    {
        // Retrieve the full PhpDoc comment block
        $doc = $r->getDocComment();

        // Trim each line from space and star chars
        $lines = array_map(function ($line) {
            return trim($line, " *");
        }, explode("\n", $doc));

        // Remove the first and last index since they're just the starting and ending slash
        $lines = array_splice($lines, 1, count($lines) - 2);

        // Retain lines that start with an @
        $textLines = array_filter($lines, function ($line) {
            return strpos($line, "@") !== 0 && $line !== '';
        });

        $argLines = array_filter($lines, function ($line) {
            return strpos($line, "@") === 0;
        });

        $args = (object) [];

        // Push each value in the corresponding @param array
        foreach ($argLines as $line) {
            // Remove the @ from the @param / @return so it's easier to retrieve data via $data->prop syntax
            $line = ltrim($line, '@');
            list($param, $value) = explode(' ', $line, 2);
            $args->{$param}[] = $value ?? '';
        }

        // The first line is the title and the remainder is the description
        $args->title = $textLines[0] ?? '';
        $args->description = implode('\n', array_slice($textLines, 1));

        return $args;
    }
}
