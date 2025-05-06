<?php

namespace AcesseSeuCondominio\Logger;

use AcesseSeuCondominio\Logger\Formatter\JsonFormatter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Monolog\Processor\WebProcessor;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerServiceProvider extends ServiceProvider
{
    const CONFIGURE_MONOLOG_DEPRECATED_VERSION = '5.6';
    
    /**
     * Mapeamento de níveis de log do Laravel para Monolog
     */
    protected $logLevels = [
        'debug' => Logger::DEBUG,
        'info' => Logger::INFO,
        'notice' => Logger::NOTICE,
        'warning' => Logger::WARNING,
        'error' => Logger::ERROR,
        'critical' => Logger::CRITICAL,
        'alert' => Logger::ALERT,
        'emergency' => Logger::EMERGENCY,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publicação do arquivo de configuração
        $this->publishes([
            __DIR__ . '/../config/log.php' => config_path('log.php'),
        ], 'config');
        
        if (version_compare($this->app->version(), self::CONFIGURE_MONOLOG_DEPRECATED_VERSION) >= 0) {
            $logStreamHandler = $this->getLogStreamHandler();
            Log::getLogger()->pushHandler($logStreamHandler);
            return;
        }

        // Método obsoleto nas versões mais recentes do Laravel, mas mantido para compatibilidade
        if (method_exists($this->app, 'configureMonologUsing')) {
            $this->app->configureMonologUsing(function ($monolog) {
                $logStreamHandler = $this->getLogStreamHandler();
                $monolog->pushHandler($logStreamHandler);
            });
        }
    }

    /**
     * Registra as configurações
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/log.php', 'log');
    }

    /**
     * @return StreamHandler
     */
    public function getLogStreamHandler(): StreamHandler
    {
        $buildId = Config::get('log.build_id', '');
        $logPath = $this->app->storagePath() . '/logs/laravel-' . date('Y-m-d') . 
            ($buildId ? '-' . $buildId : '') . '.json';
            
        // Obter o nível de log configurado
        $configLevel = strtolower(Config::get('log.level', 'debug'));
        $logLevel = $this->logLevels[$configLevel] ?? Logger::DEBUG;
        
        $logStreamHandler = new StreamHandler($logPath, $logLevel);

        $webProcessor = new WebProcessor();
        $logStreamHandler->pushProcessor($webProcessor);

        $formatter = new JsonFormatter();
        $logStreamHandler->setFormatter($formatter);

        return $logStreamHandler;
    }
}
