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
        Schema::create('deploy_flow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deploy_flow_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, running, success, failed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->default(0); // in seconds
            $table->foreignId('triggered_by')->constrained('users')->onDelete('cascade');
            $table->string('trigger_type')->default('manual'); // manual, webhook, scheduled, api
            $table->json('trigger_data')->default('{}');
            $table->json('logs')->default('[]');
            $table->text('error_message')->nullable();
            $table->json('step_executions')->default('[]');
            $table->json('metadata')->default('{}');
            $table->timestamps();

            $table->index(['deploy_flow_id', 'status']);
            $table->index(['triggered_by']);
            $table->index(['started_at']);
            $table->index(['status', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deploy_flow_executions');
    }
};
