<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
        'type',
        'source',
        'status',
        'chunk_count',
        'error',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function chunks()
    {
        return $this->hasMany(DocumentChunk::class);
    }
}
