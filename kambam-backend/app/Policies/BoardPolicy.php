<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    public function update(User $user, Board $board)
    {
        return $user->id === $board->owner_id;
    }

    public function delete(User $user, Board $board)
    {
        return $user->id === $board->owner_id;
    }

    public function createCard(User $user, Board $board)
    {
        // Any authenticated user can create cards
        return true;
    }
}
