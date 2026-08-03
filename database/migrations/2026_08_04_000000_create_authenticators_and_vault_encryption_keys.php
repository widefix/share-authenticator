<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vault_encryption_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vault_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->text('wrapped_key');
            $table->string('nonce', 64);
            $table->timestamps();
        });

        Schema::create('authenticators', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('vault_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('encryption_version')->default(1);
            $table->longText('encrypted_payload');
            $table->string('nonce', 64);
            $table->string('fingerprint', 64);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['vault_id', 'archived_at']);
            $table->index('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authenticators');
        Schema::dropIfExists('vault_encryption_keys');
    }
};
