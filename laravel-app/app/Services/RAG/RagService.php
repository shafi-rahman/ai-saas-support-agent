<?php

namespace App\Services\RAG;

use App\Services\AI\EmbeddingService;
use App\Services\Qdrant\QdrantService;

class RagService
{
    public function __construct(
        private EmbeddingService $embedding,
        private QdrantService $qdrant,
    ) {}

    public function getContext(string $query, int $tenantId, int $topK = 5): string
    {
        try {
            $vector  = $this->embedding->embed($query);
            $results = $this->qdrant->search($vector, $tenantId, $topK);

            if (empty($results)) {
                return '';
            }

            $chunks = array_filter(
                array_map(fn ($r) => trim($r['payload']['content'] ?? ''), $results)
            );

            return implode("\n\n---\n\n", $chunks);
        } catch (\Throwable) {
            // RAG is best-effort — fall back to no context so chat still works
            return '';
        }
    }
}
