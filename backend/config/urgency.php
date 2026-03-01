<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Urgency Keywords
    |--------------------------------------------------------------------------
    |
    | Keywords padrão para detecção de emergências. Estas palavras serão
    | criadas automaticamente pelo seeder se não existirem.
    |
    */

    'default_keywords' => [
        [
            'keyword' => 'preso',
            'match_type' => 'contains',
            'description' => 'Pessoa presa (geralmente em elevador)',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'presa',
            'match_type' => 'contains',
            'description' => 'Pessoa presa (geralmente em elevador)',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'fogo',
            'match_type' => 'contains',
            'description' => 'Incêndio',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'fumaça',
            'match_type' => 'contains',
            'description' => 'Fumaça detectada',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'fumaca',
            'match_type' => 'contains',
            'description' => 'Fumaça detectada (sem acento)',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'socorro',
            'match_type' => 'contains',
            'description' => 'Pedido de socorro',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'queda',
            'match_type' => 'contains',
            'description' => 'Pessoa caiu',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'caiu',
            'match_type' => 'contains',
            'description' => 'Pessoa caiu',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'ferido',
            'match_type' => 'contains',
            'description' => 'Pessoa ferida',
            'priority_level' => 4,
            'whole_word' => true,
        ],
        [
            'keyword' => 'ferida',
            'match_type' => 'contains',
            'description' => 'Pessoa ferida',
            'priority_level' => 4,
            'whole_word' => true,
        ],
        [
            'keyword' => 'sangue',
            'match_type' => 'contains',
            'description' => 'Sangramento',
            'priority_level' => 4,
            'whole_word' => true,
        ],
        [
            'keyword' => 'desmaio',
            'match_type' => 'contains',
            'description' => 'Pessoa desmaiada',
            'priority_level' => 4,
            'whole_word' => true,
        ],
        [
            'keyword' => 'desmaiou',
            'match_type' => 'contains',
            'description' => 'Pessoa desmaiada',
            'priority_level' => 4,
            'whole_word' => true,
        ],
        [
            'keyword' => 'explosão',
            'match_type' => 'contains',
            'description' => 'Explosão',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'explosao',
            'match_type' => 'contains',
            'description' => 'Explosão (sem acento)',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'gás',
            'match_type' => 'contains',
            'description' => 'Vazamento de gás',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'gas',
            'match_type' => 'contains',
            'description' => 'Vazamento de gás (sem acento)',
            'priority_level' => 5,
            'whole_word' => true,
        ],
        [
            'keyword' => 'emergência',
            'match_type' => 'contains',
            'description' => 'Emergência genérica',
            'priority_level' => 4,
            'whole_word' => true,
        ],
        [
            'keyword' => 'emergencia',
            'match_type' => 'contains',
            'description' => 'Emergência genérica (sem acento)',
            'priority_level' => 4,
            'whole_word' => true,
        ],
        [
            'keyword' => 'elevador (parado|travado|preso)',
            'match_type' => 'regex',
            'description' => 'Elevador com problema (regex)',
            'priority_level' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'cache_ttl' => env('URGENCY_CACHE_TTL', 3600), // 1 hour
];
