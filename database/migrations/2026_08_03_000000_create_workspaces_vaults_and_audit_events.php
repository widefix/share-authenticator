<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->foreignId('created_by_user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')
                ->nullable()
                ->after('email_verified_at')
                ->constrained()
                ->restrictOnDelete();
            $table->string('role', 20)->nullable()->after('workspace_id');
        });

        Schema::create('vaults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('normalized_name', 80);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'normalized_name']);
            $table->index(['workspace_id', 'archived_at']);
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->timestamp('created_at');

            $table->index(['workspace_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('vaults');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('workspaces');
    }
};
