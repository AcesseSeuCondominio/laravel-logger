<?php

namespace AcesseSeuCondominio\Logger\Formatter;

use DateTimeZone;
use Throwable;
use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

/**
 * Class JsonFormatter
 *
 * @package AcesseSeuCondominio\Logger\Formatter
 */
class JsonFormatter extends BaseJsonFormatter
{
    protected $application;
    protected $environment;
    protected $gitCommit;
    protected $auth;
    protected $appendNewline = true;
    protected $extractContextKeys = [
        'exception',
        'code',
        'service',
        'erro_string',
        'action',
        'user_id',
        'duration',
        'query',
    ];
    
    /**
     * Chaves sensíveis que serão ocultadas nos logs
     */
    protected $sensitiveKeys = [
        'api_key',
        'token',
        'bearerToken',
        'password',
        'senha',
        'secret',
        'authorization',
        'api-key',
        'apikey',
        'access_token',
        'refresh_token',
        'private_key',
        'client_secret',
        'credentials',
    ];

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->application = Config::get('log.application');
        $this->environment = Config::get('app.env');
        $this->gitCommit = Config::get('log.git_commit');
        $this->auth = Config::get('log.auth') !== false;
        
        // Adicionar chaves sensíveis personalizadas
        if (Config::has('log.sensitive_keys') && is_array(Config::get('log.sensitive_keys'))) {
            $this->sensitiveKeys = array_merge($this->sensitiveKeys, Config::get('log.sensitive_keys'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function normalize($data, int $depth = 0)
    {
        if ($data instanceof Throwable) {
            return (string) $data;
        }

        return parent::normalize($data, $depth);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $input): string
    {
        $timestamp = clone $input['datetime'];
        $timestamp = $timestamp->setTimezone(new DateTimeZone('UTC'));

        $record = [
            '@timestamp' => $timestamp->format('Y-m-d\TH:i:s.v\Z'),
            'application' => $this->application,
            'environment' => $this->environment,
            'git_commit' => $this->gitCommit,
            'message' => $input['message'],
            'level' => $input['level'],
            'level_name' => $input['level_name'],
        ];

        if (! empty($input['context'])) {
            $context = $input['context'];
            
            // Remover dados sensíveis do contexto
            $this->removeSensitiveData($context);

            foreach ($this->extractContextKeys as $key) {
                if (isset($context[$key])) {
                    $record[$key] = $context[$key];
                }
                // Unset context key, even if its value is `null`
                unset($context[$key]);
            }

            if (! isset($record['code']) && isset($record['action'])) {
                $record['code'] = preg_replace('/\W+/', '_', $record['action']);
            }

            if ($this->auth && (! isset($context['auth']) || $context['auth'] !== false)) {
                $this->fillAuth($record);
            }
            unset($context['auth']);

            // Double encode context to prevent mapping explosion:
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html#mapping-limit-settings
            $record['context'] = $this->toJson($this->normalize($context), true);
        }

        if (! empty($input['extra'])) {
            $extra = $input['extra'];
            $this->removeSensitiveData($extra);
            $record['extra'] = $extra;
        }

        return $this->replacePrivateKeys($this->toJson($this->normalize($record), true) . ($this->appendNewline ? "\n" : ''));
    }

    protected function fillAuth(&$record)
    {
        // Se log.auth_details for false, não inclua detalhes de usuário
        if (Config::get('log.auth_details') === false) {
            return;
        }
        
        $auth = Auth::user();

        if ($auth) {
            if (! isset($record['user_id'])) {
                $record['user_id'] = $auth->id;
            }

            if (! isset($record['userable_type'])) {
                $record['userable_type'] = $auth->userable_type ?? null;
            }

            if (! isset($record['userable_id']) && isset($auth->userable)) {
                $record['userable_id'] = $auth->userable->id;
            }

            if (! isset($record['staff'])) {
                $record['staff'] = (int) (method_exists($auth, 'isAdmin') && $auth->isAdmin());
            }
        }
    }

    /**
     * Oculta valores de chaves sensíveis em uma string JSON
     */
    private function replacePrivateKeys(string $stackTraceJson)
    {
        $pattern = '/(' . implode('|', array_map('preg_quote', $this->sensitiveKeys)) . ')(=|":|:)([^,}\s]+|"[^"]+")/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $key = $matches[1];
            $separator = $matches[2];
            $value = $matches[3];
            
            // Se o valor estiver entre aspas, mantenha as aspas
            if (substr($value, 0, 1) === '"' && substr($value, -1) === '"') {
                return $key . $separator . '"********"';
            }
            
            return $key . $separator . '********';
        }, $stackTraceJson);
    }
    
    /**
     * Remove ou oculta dados sensíveis de um array recursivamente
     */
    private function removeSensitiveData(&$data)
    {
        if (!is_array($data)) {
            return;
        }
        
        foreach ($data as $key => &$value) {
            // Checa se a chave contém alguma das palavras sensíveis
            $keyLower = strtolower($key);
            foreach ($this->sensitiveKeys as $sensitiveKey) {
                if (strpos($keyLower, strtolower($sensitiveKey)) !== false) {
                    $data[$key] = '********';
                    break;
                }
            }
            
            // Recursivamente verifica arrays aninhados
            if (is_array($value)) {
                $this->removeSensitiveData($value);
            }
        }
    }
}
