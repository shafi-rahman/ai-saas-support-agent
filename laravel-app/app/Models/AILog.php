<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AILog extends Model
{
    protected $table = 'ai_logs';

    protected $fillable = [
        'tenant_id',
        'session_id',
        'model',
        'endpoint',
        'prompt_preview',
        'duration_ms',
        'status',
        'error',
    ];
}
