<?php

namespace Vinelab\Tracing;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\ServiceProvider;
use Vinelab\Tracing\Contracts\Tracer;
use Vinelab\Tracing\Facades\Trace;
use Vinelab\Tracing\Listeners\TraceCommand;

class TracingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tracing.php' => config_path('tracing.php'),
            ]);
        }

        $this->app['events']->listen(CommandStarting::class, TraceCommand::class);

        $this->app->terminating(function () {
            $rootSpan = Trace::getRootSpan();

            if ($rootSpan) {
                $rootSpan->finish();
            }

            Trace::flush();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom( dirname(__DIR__).'/config/tracing.php', 'tracing');

        $this->app->singleton(TracingDriverManager::class, function ($app) {
            return new TracingDriverManager($app);
        });

        $this->app->singleton(Tracer::class, function ($app) {
            return $app->make(TracingDriverManager::class)->driver($this->app['config']['tracing.driver']);
        });
    }
}