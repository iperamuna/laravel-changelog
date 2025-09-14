<?php

namespace Iperamuna\LaravelChangelog;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelChangelogServiceProvider extends ServiceProvider
{

    protected string $vendor = 'iperamuna';

    protected string $name = 'laravel-changelog';

    protected string $alias = 'laravel-changelog';

    protected string $assetFolder = 'dist';

    protected string $description = 'A Laravel package to manage a changelog file';

    protected string $version = '1.0.0';

    protected string $config = 'changelog';
    protected string $configFile = 'changelog.php';

    protected string $routeFile = 'changelog.php';

    protected string $packageNamespace = 'Iperamuna\\LaravelChangelog';

    /**
     * Register services.
     */
    public function register(): void
    {

        $this->mergeConfigFrom(__DIR__.'/../config/'.$this->configFile, $this->config);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $this->loadConfig();

        $this->loadViews();

        $this->loadRoutes();

        $this->loadBladeComponents();

        $this->loadAssetPublishes();

        $this->loadViewPublishes();

        $this->loadConsoleCommands();

    }

    protected function loadConfig():void
    {
        $this->publishes([
            __DIR__ . '/../../config/'.$this->configFile => config_path($this->configFile),
        ], "{$this->name}-config");

    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', $this->name);;
    }

    protected function loadRoutes():void
    {
        Route::group($this->routeConfig(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/'.$this->routeFile);;
        });
    }

    protected function routeConfig(): array
    {
        $config = config($this->config);

        // If secure, attach middleware with the given guard
        if ($config['secure'] ?? false) {
            return [
                'middleware' => ['web', 'auth:' . ($config['guard'] ?? 'web')]
            ];
        }

        // If not secure, just normal web
        return [
            'middleware' => ['web']
        ];
    }

    protected function loadBladeComponents():void
    {

        Blade::anonymousComponentNamespace(
            __DIR__ . '/../../resources/views/components',
            $this->name
        );

        // Class-based components namespace => <x-$this->name::button />
        Blade::componentNamespace(
            $this->packageNamespace.'\\View\\Components',
            $this->name
        );
    }

    public function loadAssetPublishes(): void
    {

        $this->publishes([
            __DIR__ . '/../'.$this->assetFolder => public_path("vendor/{$this->vendor}/{$this->name}"),
        ], ["{$this->name}-assets", 'public']);

        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/'.$this->name),
        ], "{$this->name}-views");
    }

    public function loadViewPublishes(): void
    {
        $this->publishes([
            __DIR__ . '/../../resources/views' => resource_path('views/vendor/'.$this->name),
        ], "{$this->name}-views");
    }

    public function loadConsoleCommands(): void
    {

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\AddToChangeLog::class,
                Console\Commands\InitChangeLog::class,
            ]);
        }
    }
}
