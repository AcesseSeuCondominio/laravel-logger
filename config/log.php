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
    
    /**
     * Ativar ou desativar a inclusão de detalhes do usuário autenticado
     * Útil para ambientes com restrições de LGPD/GDPR
     */
    'auth_details' => env('LOG_AUTH_DETAILS', true),
    
    /**
     * Lista adicional de chaves sensíveis que serão ocultadas nos logs
     * Útil para ocultar informações específicas do seu projeto
     */
    'sensitive_keys' => [
        // Exemplos:
        // 'cpf',
        // 'rg',
        // 'cnpj',
    ],
    
    /**
     * Nível mínimo de log (debug, info, notice, warning, error, critical, alert, emergency)
     * Padrão: debug (todos os logs)
     */
    'level' => env('LOG_LEVEL', 'debug'),
]; 