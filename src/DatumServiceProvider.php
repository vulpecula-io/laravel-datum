<?php

declare(strict_types=1);

namespace Vulpecula\Datum;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Queue\Events\Looping;
use Illuminate\Queue\Events\WorkerStopping;
use RuntimeException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vulpecula\Datum\Contracts\Ingest;
use Vulpecula\Datum\Contracts\Storage;
use Vulpecula\Datum\Ingests\NullIngest;
use Vulpecula\Datum\Ingests\StorageIngest;
use Vulpecula\Datum\Storage\DatabaseStorage;

class DatumServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-datum')
            ->hasConfigFile()
            ->hasMigration('create_datum_table');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Datum::class);
        $this->app->bind(Storage::class, DatabaseStorage::class);
        $this->app->bind(Ingest::class, fn (Application $app) => match ($app->make('config')->get('datum.ingest.driver')) {
            'storage' => $app->make(StorageIngest::class),
            null, 'null' => $app->make(NullIngest::class),
            default => throw new RuntimeException("Unknown ingest driver [{$app->make('config')->get('datum.ingest.driver')}]."),
        });
    }

    public function packageBooted(): void
    {
        if ($this->app->make('config')->get('datum.enabled')) {
            $this->app->make(Datum::class)->register($this->app->make('config')->get('datum.recorders'));
            $this->listenForEvents();
        } else {
            $this->app->make(Datum::class)->stopRecording();
        }
    }

    /**
     * Listen for the events that are relevant to the package.
     */
    protected function listenForEvents(): void
    {
        $this->app->booted(function () {
            $this->callAfterResolving(Dispatcher::class, function (Dispatcher $event, Application $app) {
                $event->listen([
                    Looping::class,
                    WorkerStopping::class,
                ], function () use ($app) {
                    $app->make(Datum::class)->ingest();
                });
            });

            $this->callAfterResolving(HttpKernel::class, function (HttpKernel $kernel, Application $app) {
                $kernel->whenRequestLifecycleIsLongerThan(-1, function () use ($app) { // @phpstan-ignore method.notFound
                    $app->make(Datum::class)->ingest();
                });
            });

            $this->callAfterResolving(ConsoleKernel::class, function (ConsoleKernel $kernel, Application $app) {
                $kernel->whenCommandLifecycleIsLongerThan(-1, function () use ($app) { // @phpstan-ignore method.notFound
                    $app->make(Datum::class)->ingest();
                });
            });
        });

        $this->callAfterResolving(Dispatcher::class, function (Dispatcher $event, Application $app) {
            /** @noinspection PhpUndefinedClassInspection */
            $event->listen([
                \Laravel\Octane\Events\RequestReceived::class, // @phpstan-ignore class.notFound
                \Laravel\Octane\Events\TaskReceived::class, // @phpstan-ignore class.notFound
                \Laravel\Octane\Events\TickReceived::class, // @phpstan-ignore class.notFound
            ], function ($event) {
                if ($event->sandbox->resolved(Datum::class)) {
                    $event->sandbox->make(Datum::class)->setContainer($event->sandbox);
                }
            });
        });
    }
}
