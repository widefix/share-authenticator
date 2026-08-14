<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_use_email_and_password_credentials(): void
    {
        $this->assertSame([
            'id',
            'email',
            'created_at',
            'updated_at',
            'workspace_id',
            'role',
            'password',
        ], Schema::getColumnListing('users'));

        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
        $this->assertFalse(Schema::hasTable('email_login_codes'));
    }
}
