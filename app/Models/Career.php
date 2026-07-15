<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Career extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'description', 'order'];

    public function skills(): HasMany
    {
        return $this->hasMany(CareerSkill::class)->orderBy('order');
    }

    public function learningModules(): HasMany
    {
        return $this->hasMany(LearningModule::class)->orderBy('order');
    }
}
