<?php

namespace AcesseSeuCondominio\Logger;

use AcesseSeuCondominio\Logger\Formatter\JsonFormatter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Monolog\Processor\WebProcessor;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerServiceProvider extends ServiceProvider
{
    const CONFIGURE_MONOLOG_DEPRECATED_VERSION = '5.6';

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (version_compare(app()->version(), self::CONFIGURE_MONOLOG_DEPRECATED_VERSION) >= 0) {
            $logStreamHandler = $this->getLogStreamHandler();
            Log::getLogger()->pushHandler($logStreamHandler);
            return;
        }

        $this->app->configureMonologUsing(function ($monolog) {
            $logStreamHandler = $this->getLogStreamHandler();
            $monolog->pushHandler($logStreamHandler);
        });
    }

    /**
     * @return StreamHandler
     */
    public function getLogStreamHandler(): StreamHandler
    {
        $logPath = $this->app->storagePath() . '/logs/laravel-' . date('Y-m-d') . '-' . config('log.build_id') . '.json';
        $logLevel = Logger::DEBUG;
        $logStreamHandler = new StreamHandler($logPath, $logLevel);

        $webProcessor = new WebProcessor();
        $logStreamHandler->pushProcessor($webProcessor);

        $formatter = new JsonFormatter();
        $logStreamHandler->setFormatter($formatter);

        return $logStreamHandler;
    }
}
