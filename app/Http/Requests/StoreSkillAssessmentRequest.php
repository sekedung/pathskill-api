<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSkillAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // otorisasi cukup lewat middleware auth:sanctum di route
    }

    /**
     * Payload yang diharapkan dari frontend (submit sekaligus per career):
     * {
     *   "ratings": [
     *     { "career_skill_id": 1, "rating": 4 },
     *     { "career_skill_id": 2, "rating": 3 },
     *     ...
     *   ]
     * }
     */
    public function rules(): array
    {
        return [
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*.career_skill_id' => ['required', 'integer', 'exists:career_skills,id'],
            'ratings.*.rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'ratings.required' => 'Minimal satu skill harus dinilai.',
            'ratings.*.rating.min' => 'Rating minimal 1 (Pemula).',
            'ratings.*.rating.max' => 'Rating maksimal 5 (Pakar).',
        ];
    }
}