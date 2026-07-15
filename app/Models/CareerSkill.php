<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'career_id',
        'skill_name',
        'category',
        'industry_requirement',
        'order',
    ];

    protected $casts = [
        'industry_requirement' => 'float',
    ];

    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(UserSkillAssessment::class);
    }
}
