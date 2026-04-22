<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function view($user, $customer): bool
    {
        return $user->id === $customer->user_id;
    }

    public function update($user, $customer): bool
    {
        return $user->id === $customer->user_id;
    }

    public function delete($user, $customer): bool
    {
        return $user->id === $customer->user_id && !$customer->invoices()->exists();
    }
}