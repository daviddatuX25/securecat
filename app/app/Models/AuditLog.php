<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log record (Phase 1). Per 04-data-model, SC-10.
 * Single table; no timestamps - uses 'timestamp' column.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = null;

    protected $table = 'audit_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'role',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'timestamp',
        'details',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timestamp' => 'datetime',
            'details' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
