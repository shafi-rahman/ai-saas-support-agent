<?php

namespace App\Services\Qdrant;

use Illuminate\Support\Facades\Http;

class QdrantService
{
    private string $url;
    private string $collection;
    private int $vectorSize;

    public function __construct()
    {
        $this->url        = rtrim(config('qdrant.url', 'http://localhost:6333'), '/');
        $this->collection = config('qdrant.collection', 'knowledge_base');
        $this->vectorSize = (int) config('qdrant.vector_size', 768);
    }

    public function ensureCollection(): void
    {
        $response = Http::timeout(10)->get("{$this->url}/collections/{$this->collection}");

        if ($response->status() === 404) {
            Http::timeout(10)->put("{$this->url}/collections/{$this->collection}", [
                'vectors' => [
                    'size'     => $this->vectorSize,
                    'distance' => 'Cosine',
                ],
            ]);
        }
    }

    public function upsert(int $id, array $vector, array $payload): void
    {
        Http::timeout(30)->put("{$this->url}/collections/{$this->collection}/points", [
            'points' => [[
                'id'      => $id,
                'vector'  => $vector,
                'payload' => $payload,
            ]],
        ]);
    }

    public function search(array $vector, int $tenantId, int $limit = 5): array
    {
        $response = Http::timeout(30)->post(
            "{$this->url}/collections/{$this->collection}/points/search",
            [
                'vector' => $vector,
                'limit'  => $limit,
                'filter' => [
                    'must' => [[
                        'key'   => 'tenant_id',
                        'match' => ['value' => $tenantId],
                    ]],
                ],
                'with_payload' => true,
            ]
        );

        if ($response->failed()) {
            return [];
        }

        return $response->json('result') ?? [];
    }

    public function deleteByDocument(int $documentId, int $tenantId): void
    {
        Http::timeout(30)->post("{$this->url}/collections/{$this->collection}/points/delete", [
            'filter' => [
                'must' => [
                    ['key' => 'document_id', 'match' => ['value' => $documentId]],
                    ['key' => 'tenant_id',   'match' => ['value' => $tenantId]],
                ],
            ],
        ]);
    }
}
