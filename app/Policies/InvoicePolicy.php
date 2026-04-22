<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    public function view($user, $invoice): bool
    {
        return $user->id === $invoice->user_id;
    }

    public function update($user, $invoice): bool
    {
        return $user->id === $invoice->user_id && $invoice->isPending();
    }

    public function delete($user, $invoice): bool
    {
        return $user->id === $invoice->user_id && $invoice->isPending();
    }
}