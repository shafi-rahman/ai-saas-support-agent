<?php

namespace App\Http\Controllers;

use App\Models\AILog;
use App\Models\Tenant;
use App\Services\AI\AIManager;
use App\Services\AI\MemoryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WidgetController extends Controller
{
    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
        ];
    }

    public function preflight(): \Illuminate\Http\Response
    {
        return response('', 204, $this->corsHeaders());
    }

    public function chat(Request $request, AIManager $ai): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'widget_key' => 'required|string',
            'prompt'     => 'required|string|max:2000',
            'session_id' => 'required|string|max:100',
            'model'      => 'nullable|string|max:50',
        ]);

        $tenant = Tenant::where('widget_key', $request->widget_key)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            return response()->json(['message' => 'Invalid widget key.'], 401, $this->corsHeaders());
        }

        $tenantId = $tenant->id;
        $start    = microtime(true);

        try {
            $result = $ai->streamWithMemory(
                $request->prompt,
                'widget-' . $request->session_id,
                $request->model ?? 'phi',
                null,
                $tenantId
            );
        } catch (\Exception $e) {
            AILog::create([
                'tenant_id'      => $tenantId,
                'session_id'     => 'widget-' . $request->session_id,
                'model'          => $request->model ?? 'phi',
                'endpoint'       => 'widget',
                'prompt_preview' => mb_substr($request->prompt, 0, 200),
                'status'         => 'error',
                'error'          => $e->getMessage(),
            ]);

            return response()->json(['message' => $e->getMessage()], 503, $this->corsHeaders());
        }

        $memory    = app(MemoryService::class);
        $sessionId = 'widget-' . $request->session_id;
        $model     = $request->model ?? 'phi';
        $preview   = mb_substr($request->prompt, 0, 200);

        return new StreamedResponse(
            function () use ($result, $memory, $sessionId, $model, $preview, $start, $tenantId) {
                $body         = $result['stream']->getBody();
                $conversation = $result['conversation'];
                $fullResponse = '';

                while (!$body->eof()) {
                    $chunk = $body->read(1024);

                    foreach (explode("\n", $chunk) as $line) {
                        if (empty($line)) continue;
                        $data = json_decode($line, true);
                        if (!$data) continue;

                        $text = $data['message']['content'] ?? '';
                        if ($text !== '') {
                            $fullResponse .= $text;
                            echo "event: message\n";
                            echo 'data: ' . str_replace("\n", '\\n', $text) . "\n\n";
                            ob_flush();
                            flush();
                        }

                        if (!empty($data['done'])) {
                            echo "event: done\ndata: true\n\n";
                            ob_flush();
                            flush();
                        }
                    }
                }

                $memory->addMessage($conversation, 'assistant', $fullResponse);

                AILog::create([
                    'tenant_id'      => $tenantId,
                    'session_id'     => $sessionId,
                    'model'          => $model,
                    'endpoint'       => 'widget',
                    'prompt_preview' => $preview,
                    'duration_ms'    => (int) round((microtime(true) - $start) * 1000),
                    'status'         => 'success',
                ]);
            },
            200,
            array_merge($this->corsHeaders(), [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache',
                'Connection'        => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ])
        );
    }
}
