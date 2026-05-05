<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    private string $url;
    private string $model;

    public function __construct()
    {
        $base        = preg_replace('#/api/(chat|generate)$#', '', config('ai.providers.ollama.url'));
        $this->url   = rtrim($base, '/') . '/api/embeddings';
        $this->model = config('ai.embedding_model', 'nomic-embed-text');
    }

    public function embed(string $text): array
    {
        $response = Http::timeout(60)->post($this->url, [
            'model'  => $this->model,
            'prompt' => $text,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Embedding request failed: HTTP ' . $response->status());
        }

        $embedding = $response->json('embedding');

        if (empty($embedding)) {
            throw new \RuntimeException('Ollama returned an empty embedding. Is the model pulled? Run: ollama pull ' . $this->model);
        }

        return $embedding;
    }
}
