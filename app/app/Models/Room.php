<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Room for exam sessions. Per 04-data-model Phase 1.
 */
class Room extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'capacity',
        'location_notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
    ];
}
