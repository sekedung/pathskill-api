<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkillAssessment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'career_skill_id', 'rating'];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function careerSkill(): BelongsTo
    {
        return $this->belongsTo(CareerSkill::class);
    }
}
