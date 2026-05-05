<?php

namespace App\Services\AI;

use App\Models\Conversation;

class MemoryService
{
    public function getOrCreateConversation(string $sessionId, ?int $tenantId = null): Conversation
    {
        $attributes = ['session_id' => $sessionId];
        if ($tenantId) {
            $attributes['tenant_id'] = $tenantId;
        }

        return Conversation::firstOrCreate($attributes);
    }

    public function addMessage(Conversation $conversation, string $role, string $content): void
    {
        $conversation->messages()->create([
            'role'    => $role,
            'content' => $content,
        ]);
    }

    public function getHistory(Conversation $conversation, int $limit = 10): array
    {
        return $conversation->messages()
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->map(fn ($msg) => [
                'role'    => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();
    }
}
