<?php
namespace Czim\NestedModelUpdater;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterInterface;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Illuminate\Support\ServiceProvider;

class NestedModelUpdaterServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/nestedmodelupdater.php' => config_path('nestedmodelupdater.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/nestedmodelupdater.php', 'nestedmodelupdater'
        );

        $this->registerInterfaceBindings();
    }

    /**
     * Registers interface bindings
     */
    protected function registerInterfaceBindings()
    {
        $this->app->bind(ModelUpdaterInterface::class, ModelUpdater::class);
        $this->app->bind(NestingConfigInterface::class, NestingConfig::class);
    }

}
