<?php

namespace MindEdge\LaravelApiDoc;

use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use MindEdge\LaravelApiDoc\Global\Document;
use MindEdge\LaravelApiDoc\Global\RouteDocument;
use MindEdge\LaravelApiDoc\Global\RouteParameter;
use MindEdge\LaravelApiDoc\Global\Tag;

class Doc
{
    /**
     * Determine all the routes, route parameters, and middleware used and return them as an object
     *
     * @param $version=1.0 The api version to target the documentation generation for
     * @param Document|null $doc=null An api document with prefilled values
     *
     * @return Document The generated document with routes
     */
    public static function generate($version = '1.0', Document|null $doc = null): Document
    {
        if (empty($doc)) {
            $doc = new Document([
                'version' => $version,
                'name' => 'My Api',
                'description' => 'My sample api',
            ]);
        }

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
                    $queryParams = collect([]);

                    // Save the phpdoc info
                    $routeItem->phpdoc = self::phpdoc($rMethod);

                    // Figure out the parameter name, type, etc
                    foreach ($params as $param) {
                        $paramDescription = '';
                        // Check if there's a description in the phpdoc
                        foreach ($routeItem->phpdoc?->param ?? [] as $phpdocParam) {
                            if (ltrim($phpdocParam->name, '$') === $param->getName()) {
                                $paramDescription = $phpdocParam->description ?? '';
                                break;
                            }
                        }

                        $routeParam = new RouteParameter([
                            'name' => $param->getName(),
                            'description' => $paramDescription,
                            'type' => $param?->getType()?->getName() ?? 'mixed',
                            'in' => 'path',
                            'required' => true,
                        ]);

                        // If it's a form request, try and get any registered query params
                        if ($routeParam->getIsFormRequest()) {
                            $requestBody = $routeParam->getRequestBody();
                            // Make sure the body as actually defined
                            if ($requestBody !== null) {
                                $routeItem->requestBody->push($requestBody);
                            }
                        }

                        $routeItem->parameters->push($routeParam);
                    }

                    // Merge in the query parameters to the end
                    $routeItem->parameters = $routeItem->parameters->merge($queryParams);

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
            list($param, $value) = explode(' ', trim($line), 2);

            $valueParts = explode(' ', trim($value), 3);
            $args->{$param}[] = (object) [
                'type' => $valueParts[0] ?? '',
                'name' => $valueParts[1] ?? '',
                'description' => $valueParts[2] ?? '',
            ];
        }

        // The first line is the title and the remainder is the description
        $args->title = $textLines[0] ?? '';
        $args->description = implode('\n', array_slice($textLines, 1));

        return $args;
    }
}
