<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Responses\WorkspaceResponseFactory;
use App\Services\Workspaces\WorkspaceService;
use Illuminate\Http\RedirectResponse;

final class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceService $workspaces,
        private readonly WorkspaceResponseFactory $responses,
    ) {}

    public function store(StoreWorkspaceRequest $request): RedirectResponse
    {
        $this->workspaces->create(
            $request->user(),
            $request->string('name')->toString(),
        );

        return $this->responses->created();
    }
}
