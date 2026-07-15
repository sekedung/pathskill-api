<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = ['learning_module_id', 'title', 'description', 'due_date', 'order'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class, 'learning_module_id');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserAssignmentProgress::class);
    }
}
