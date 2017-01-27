<?php
namespace Czim\NestedModelUpdater;

use Czim\NestedModelUpdater\Contracts\ModelUpdaterFactoryInterface;
use Czim\NestedModelUpdater\Contracts\NestedValidatorFactoryInterface;
use Czim\NestedModelUpdater\Contracts\NestingConfigInterface;
use Czim\NestedModelUpdater\Contracts\TemporaryIdsInterface;
use Czim\NestedModelUpdater\Data\TemporaryIds;
use Czim\NestedModelUpdater\Factories\ModelUpdaterFactory;
use Czim\NestedModelUpdater\Factories\NestedValidatorFactory;
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
        $this->app->bind(ModelUpdaterFactoryInterface::class, ModelUpdaterFactory::class);
        $this->app->bind(NestedValidatorFactoryInterface::class, NestedValidatorFactory::class);
        $this->app->bind(NestingConfigInterface::class, NestingConfig::class);
        $this->app->bind(TemporaryIdsInterface::class, TemporaryIds::class);
    }

}
