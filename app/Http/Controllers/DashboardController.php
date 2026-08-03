<?php

namespace App\Http\Controllers;

use App\Http\Responses\DashboardResponseFactory;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardResponseFactory $responses) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('workspace');
        $vaults = $user->isWorkspaceAdmin()
            ? $user->workspace?->vaults()->active()->orderBy('name')->get() ?? collect()
            : collect();

        return $this->responses->show($user, $vaults);
    }
}
