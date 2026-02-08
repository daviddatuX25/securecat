<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Logs each scan (valid or invalid). Per 04-data-model Phase 1.
 */
class ScanEntry extends Model
{
    public $timestamps = false;

    public const RESULT_VALID = 'valid';

    public const RESULT_INVALID = 'invalid';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'exam_assignment_id',
        'proctor_id',
        'scanned_at',
        'device_info',
        'validation_result',
        'failure_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    public function examAssignment(): BelongsTo
    {
        return $this->belongsTo(ExamAssignment::class);
    }

    public function proctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proctor_id');
    }
}
