<?php

namespace AcesseSeuCondominio\Logger\Formatter;

use DateTimeZone;
use Throwable;
use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Illuminate\Support\Facades\Auth;

/**
 * Class JsonFormatter
 *
 * @package App\Components\Log\Formatter
 */
class JsonFormatter extends BaseJsonFormatter
{
    protected $application;
    protected $environment;
    protected $gitCommit;
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

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->application = config('log.application');
        $this->environment = config('app.env');
        $this->gitCommit = config('log.git_commit');
        $this->auth = config('log.auth') !== false;
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
            $record['extra'] = $input['extra'];
        }

        return $this->replacePrivateKeys($this->toJson($this->normalize($record), true) . ($this->appendNewline ? "\n" : ''));
    }

    protected function fillAuth(&$record)
    {
        $auth = Auth::user();

        if ($auth) {
            if (! isset($record['user_id'])) {
                $record['user_id'] = $auth->id;
            }

            if (! isset($record['userable_type'])) {
                $record['userable_type'] = $auth->userable_type;
            }

            if (! isset($record['userable_id']) && isset($auth->userable)) {
                $record['userable_id'] = $auth->userable->id;
            }

            if (! isset($record['staff'])) {
                $record['staff'] = (int) (method_exists($auth, 'isAdmin') && $auth->isAdmin());
            }
        }
    }

    private function replacePrivateKeys(string $stackTraceJson)
    {
        $keys = ['api_key', 'token', 'bearerToken'];
        return preg_replace_callback('/(' . implode('|', $keys) . ')=.{4,8}/', function($matches) {
            return $matches[1] . '=' . str_repeat('*', 8);
        }, $stackTraceJson);
    }
}
