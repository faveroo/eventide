<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('api_token', 64)->nullable()->unique();
            $table->text('github_secret')->nullable();
            $table->string('status')->default('unknown');
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->unsignedInteger('check_interval_seconds')->default(60);
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->unsignedInteger('failure_threshold')->default(3);
            $table->unsignedInteger('latency_threshold_ms')->default(2000);
        });
        Schema::create('detection_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('event_type');
            $table->unsignedInteger('threshold')->default(5);
            $table->unsignedInteger('window_seconds')->default(300);
            $table->string('severity')->default('high');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('detection_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fingerprint', 64)->index();
            $table->string('title');
            $table->string('severity')->default('high');
            $table->string('status')->default('investigating');
            $table->unsignedInteger('event_count')->default(0);
            $table->timestamp('opened_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'status']);
        });
        Schema::create('app_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 32)->default('application');
            $table->string('external_id', 128);
            $table->string('type');
            $table->string('severity')->default('error');
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['project_id', 'source', 'external_id']);
            $table->index(['project_id', 'type', 'created_at']);
        });
        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->index(['project_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_checks');
        Schema::dropIfExists('app_events');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('detection_rules');
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['api_token']);
            $table->dropColumn(['api_token', 'github_secret', 'status', 'last_checked_at', 'consecutive_failures',
                'check_interval_seconds', 'timeout_seconds', 'failure_threshold', 'latency_threshold_ms']);
        });
    }
};
