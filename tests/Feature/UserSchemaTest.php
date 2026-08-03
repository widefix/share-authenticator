<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_are_identified_only_by_email(): void
    {
        $this->assertSame([
            'id',
            'email',
            'email_verified_at',
            'created_at',
            'updated_at',
            'workspace_id',
            'role',
        ], Schema::getColumnListing('users'));

        $this->assertFalse(Schema::hasTable('password_reset_tokens'));
        $this->assertTrue(Schema::hasTable('email_login_codes'));
    }
}
