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