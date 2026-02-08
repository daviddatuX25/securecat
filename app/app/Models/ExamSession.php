<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Exam session: course, room, date/time, proctor. Per 04-data-model Phase 1.
 */
class ExamSession extends Model
{
    protected $table = 'exam_sessions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'room_id',
        'proctor_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function proctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proctor_id');
    }

    public function examAssignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class, 'exam_session_id');
    }
}
