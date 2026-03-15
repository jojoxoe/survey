<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    public function view(User $user, Survey $survey): bool
    {
        return $user->id === $survey->user_id;
    }

    public function update(User $user, Survey $survey): bool
    {
        return $user->id === $survey->user_id;
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $user->id === $survey->user_id;
    }
}
