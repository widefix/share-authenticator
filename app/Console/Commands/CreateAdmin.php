<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Auth\RegistrationService;
use App\Services\Workspaces\WorkspaceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'user:create-admin {email} {--workspace= : Name of the initial workspace}';

    protected $description = 'Create the first administrator and workspace (empty installations only)';

    public function handle(RegistrationService $registration, WorkspaceService $workspaces): int
    {
        if (User::query()->exists()) {
            $this->error('An account already exists. Use workspace invitations to add users.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));
        $workspace = trim((string) ($this->option('workspace') ?? $this->ask('Workspace name')));
        $password = (string) $this->secret('Password (at least 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');
        $validator = Validator::make([
            'email' => $email,
            'workspace' => $workspace,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
            'workspace' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($registration, $workspaces, $email, $password, $workspace): void {
            $user = $registration->register($email, $password);
            $workspaces->create($user, $workspace);
        });
        $this->info('Initial administrator and workspace created. Sign in at /login.');

        return self::SUCCESS;
    }
}
