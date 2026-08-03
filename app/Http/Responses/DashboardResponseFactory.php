<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class DashboardResponseFactory
{
    /**
     * @param  Collection<int, Vault>  $vaults
     */
    public function show(User $user, Collection $vaults): View
    {
        return view('dashboard', compact('user', 'vaults'));
    }
}
