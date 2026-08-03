<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;

final class WorkspaceResponseFactory
{
    public function created(): RedirectResponse
    {
        return redirect()->route('dashboard')
            ->with('status', 'Workspace created. Your General vault is ready.');
    }
}
