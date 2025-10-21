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
        Schema::create('deploy_flow_step_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_execution_id')->constrained('deploy_flow_executions')->onDelete('cascade');
            $table->string('step_id'); // UUID from flow step
            $table->string('step_type');
            $table->string('step_name');
            $table->string('status')->default('pending'); // pending, running, completed, failed, skipped
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->default(0); // in seconds
            $table->json('logs')->default('[]');
            $table->text('error_message')->nullable();
            $table->json('output')->default('{}');
            $table->json('metadata')->default('{}');
            $table->timestamps();

            $table->index(['flow_execution_id', 'status']);
            $table->index(['step_id']);
            $table->index(['step_type']);
            $table->index(['started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deploy_flow_step_executions');
    }
};
