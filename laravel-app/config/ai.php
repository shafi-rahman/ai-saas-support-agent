<?php

return [

    'default' => 'ollama',

    // Embedding model pulled in Ollama (run: ollama pull nomic-embed-text)
    'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),

    // Ollama inference limits — lower values = less RAM/CPU on local machines
    'num_ctx'     => (int) env('OLLAMA_NUM_CTX', 2048),
    'num_predict' => (int) env('OLLAMA_NUM_PREDICT', 512),

    'providers' => [

        'ollama' => [
            'url'    => env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat'),
            'models' => [
                'phi'     => 'phi:latest',
                'llama3'  => 'llama3:latest',
                'gemma2'  => 'gemma2:latest',
                'mistral' => 'mistral:latest',
            ],
        ],

        // future
        'openai' => [
            'models' => [
                'gpt4' => 'gpt-4o',
            ],
        ],

    ],

];
