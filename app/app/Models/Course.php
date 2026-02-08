<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Course under an admission period. Per 04-data-model Phase 1.
 */
class Course extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'admission_period_id',
        'name',
        'code',
        'description',
    ];

    public function admissionPeriod(): BelongsTo
    {
        return $this->belongsTo(AdmissionPeriod::class);
    }
}
