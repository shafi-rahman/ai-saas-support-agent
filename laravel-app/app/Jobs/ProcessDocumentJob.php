<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\DocumentChunk;
use App\Services\AI\EmbeddingService;
use App\Services\Qdrant\QdrantService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries   = 2;

    public function __construct(private Document $document) {}

    public function handle(EmbeddingService $embedder, QdrantService $qdrant): void
    {
        $this->document->update(['status' => 'processing']);

        try {
            $text   = $this->extractText();
            $chunks = $this->chunkText($text, chunkSize: 500, overlap: 50);

            $qdrant->ensureCollection();
            $count = 0;

            foreach ($chunks as $index => $chunk) {
                if (trim($chunk) === '') {
                    continue;
                }

                $vector  = $embedder->embed($chunk);
                $dbChunk = DocumentChunk::create([
                    'document_id' => $this->document->id,
                    'tenant_id'   => $this->document->tenant_id,
                    'chunk_index' => $index,
                    'content'     => $chunk,
                ]);

                $qdrant->upsert($dbChunk->id, $vector, [
                    'tenant_id'   => $this->document->tenant_id,
                    'document_id' => $this->document->id,
                    'chunk_id'    => $dbChunk->id,
                    'chunk_index' => $index,
                    'content'     => $chunk,
                    'title'       => $this->document->title,
                ]);

                $count++;
            }

            $this->document->update(['status' => 'ready', 'chunk_count' => $count]);
        } catch (\Throwable $e) {
            $this->document->update(['status' => 'failed', 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function extractText(): string
    {
        return match ($this->document->type) {
            'text'  => Storage::disk('local')->get($this->document->source),
            'pdf'   => $this->extractPdf(),
            'url'   => $this->extractUrl(),
            default => throw new \RuntimeException("Unsupported document type: {$this->document->type}"),
        };
    }

    private function extractPdf(): string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new \RuntimeException(
                'PDF parsing requires smalot/pdfparser. Run: composer require smalot/pdfparser'
            );
        }

        $path   = Storage::disk('local')->path($this->document->source);
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($path);

        return $pdf->getText();
    }

    private function extractUrl(): string
    {
        $response = Http::timeout(30)->get($this->document->source);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to fetch URL ({$response->status()}): {$this->document->source}");
        }

        $text = strip_tags($response->body());

        return preg_replace('/\s+/', ' ', trim($text));
    }

    // Fixed-size word chunker with overlap
    private function chunkText(string $text, int $chunkSize, int $overlap): array
    {
        $words  = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $total  = count($words);
        $step   = max(1, $chunkSize - $overlap);
        $i      = 0;

        while ($i < $total) {
            $chunk = implode(' ', array_slice($words, $i, $chunkSize));
            if ($chunk !== '') {
                $chunks[] = $chunk;
            }
            $i += $step;
        }

        return $chunks;
    }
}
