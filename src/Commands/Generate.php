<?php

namespace Ab\ApiGenerator\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use Ramsey\Uuid\Uuid;
use ReflectionClass;

class Generate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate collection.json of Postman';

    /**
     * @var Router
     */
    private $router;

    /**
     * @var DocBlockFactory
     */
    private $factory;

    /**
     * Create a new command instance.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        parent::__construct();
        $this->router  = $router;
        $this->factory = DocBlockFactory::createInstance();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->router->getRoutes())) {
            $this->error("Your application doesn't have any routes.");

            return;
        }

        if (empty($routes = $this->getRoutes())) {
            $this->error("Your application doesn't have any routes matching the given criteria.");

            return;
        }

        Storage::put('collection.json', json_encode([
            'info'     => [
                'name'   => config('app.name'),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item'     => $routes,
            'variable' => [
                [
                    'id'    => Uuid::uuid4()->toString(),
                    'key'   => 'HOST',
                    'value' => parse_url(config('app.url'))['host'],
                ],
                [
                    'id'    => Uuid::uuid4()->toString(),
                    'key'   => 'PROTOCOL',
                    'value' => Str::contains(config('app.url'), 'https') ? 'https' : 'http',
                ],
            ],
        ]));
    }

    protected function getRoutes()
    {
        return collect($this->router->getRoutes())
            ->map(function (Route $route) {
                return [
                    'name'    => $route->uri(),
                    'request' => [
                        'method'      => $route->methods()[0],
                        'url'         => [
                            'raw'      => '{{PROTOCOL}}://{{HOST}}' . $route->uri(),
                            'protocol' => '{{PROTOCOL}}',
                            'host'     => ['{{HOST}}'],
                            'path'     => [$route->uri()],
                        ],
                        'description' => $this->getDescriptionOf($route),
                    ],
                ];
            });
    }

    private function getDescriptionOf(Route $route)
    {
        $middleware = collect($this->router->gatherRouteMiddleware($route))->map(function ($middleware) {
            return $middleware instanceof Closure ? 'Closure' : $middleware;
        })->implode("  \n");

        $description = '';

        $action = $route->getActionName();
        if (Str::contains($action, '@')) {
            $a          = Str::of($action)->explode('@');
            $controller = $a[0];
            $method     = $a[1];

            if (!class_exists($controller)) {
                return $description;
            }

            $reflectionClass = new ReflectionClass($controller);
            $docComment      = $reflectionClass->getMethod($method)->getDocComment();
            if ($docComment !== false) {
                $docBlock    = $this->factory->create($reflectionClass->getMethod($method)->getDocComment());
                $description = $docBlock->getSummary() . "\n\n---\n\n";
            }

            $description .= "**Action:** `" . $action . "`\n\n";
        }

        $description .= "**Uri:** `" . $route->uri() . "`  \n\n";

        if (($routeName = $route->getName()) !== false && strlen($routeName) > 0) {
            $description .= "**Name:** `" . $routeName . "`\n\n";
        }

        if (strlen($middleware) > 0) {
            $description .= "**Route middleware:**\n```\n$middleware\n```";
        }

        return $description;
    }
}
