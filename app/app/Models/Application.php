<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Application (links applicant to course choices). Per 04-data-model Phase 1.
 */
class Application extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVISION_REQUESTED = 'revision_requested';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'applicant_id',
        'course_id',
        'second_course_id',
        'third_course_id',
        'admission_period_id',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function secondCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'second_course_id');
    }

    public function thirdCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'third_course_id');
    }

    public function admissionPeriod(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class);
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function examAssignment(): HasOne
    {
        return $this->hasOne(ExamAssignment::class);
    }
}
