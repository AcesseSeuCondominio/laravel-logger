# Laravel Logger

Package para padronização de logs no formato JSON para aplicações Laravel. Compatível com Laravel 8 (PHP 7.4) até Laravel 11 (PHP 8.2).

## Instalação

Adicione o repositório privado ao seu composer.json:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:AcesseSeuCondominio/laravel-logger.git"
    }
  ]
}
```

Instale o pacote via composer:

```shell
composer require acesseseucondominio/laravel-logger:"dev-main"
```

### Em projetos Laravel sem auto-discovery:

Se o seu projeto não usar auto-discovery, adicione o LoggerServiceProvider ao array providers em config/app.php:

```php
AcesseSeuCondominio\Logger\LoggerServiceProvider::class,
```

## Configuração

O pacote automaticamente registra o arquivo de configuração `config/log.php`. Você pode publicar o arquivo com:

```shell
php artisan vendor:publish --provider="AcesseSeuCondominio\Logger\LoggerServiceProvider" --tag="config"
```

Ou criar manualmente seguindo este exemplo:

```php
<?php

return [
    /**
     * Nome da aplicação para identificação nos logs
     */
    'application' => env('APP_NAME', 'laravel'),

    /**
     * ID da build para identificação nos logs
     */
    'build_id' => env('LOG_BUILD_ID', ''),

    /**
     * Git commit para identificação nos logs
     */
    'git_commit' => env('LOG_GIT_COMMIT', ''),

    /**
     * Ativar ou desativar a captura de dados de autenticação
     */
    'auth' => env('LOG_AUTH', true),
];
```

## Formato dos logs

O pacote gera logs no formato JSON com os seguintes campos padrão:

- `@timestamp`: Timestamp em formato UTC
- `application`: Nome da aplicação
- `environment`: Ambiente (development, production, etc.)
- `git_commit`: Hash do commit git
- `message`: Mensagem de log
- `level`: Nível de log (numérico)
- `level_name`: Nível de log (texto)

Campos adicionais do contexto são extraídos automaticamente quando presentes:
- `exception`: Objeto de exceção
- `code`: Código de erro
- `service`: Nome do serviço
- `erro_string`: String de erro
- `action`: Ação executada
- `user_id`: ID do usuário
- `duration`: Duração da operação
- `query`: Query SQL executada

