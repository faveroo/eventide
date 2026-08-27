<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained();

            $table->foreignId('project_id')
                ->nullable()
                ->constrained();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained();

            $table->string('type');
            $table->nullableMorphs('subject');
            $table->string('description');
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
            $table->index(['user_id', 'created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
