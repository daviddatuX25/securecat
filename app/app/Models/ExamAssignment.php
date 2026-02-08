<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Links application to exam session; holds QR payload and signature. Per 04-data-model Phase 1.
 */
class ExamAssignment extends Model
{
    protected $table = 'exam_assignments';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'exam_session_id',
        'seat_number',
        'qr_payload',
        'qr_signature',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }

    public function scanEntries(): HasMany
    {
        return $this->hasMany(ScanEntry::class, 'exam_assignment_id');
    }
}
