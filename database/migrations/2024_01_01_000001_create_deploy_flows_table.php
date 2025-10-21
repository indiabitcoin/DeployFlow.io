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
        Schema::create('deploy_flows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('template')->default('simple');
            $table->json('steps')->default('[]');
            $table->boolean('is_active')->default(true);
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('last_run_at')->nullable();
            $table->decimal('success_rate', 5, 2)->default(0.00);
            $table->integer('total_runs')->default(0);
            $table->integer('successful_runs')->default(0);
            $table->integer('failed_runs')->default(0);
            $table->integer('average_duration')->default(0); // in seconds
            $table->json('settings')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'is_active']);
            $table->index(['created_by']);
            $table->index(['template']);
            $table->index(['last_run_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deploy_flows');
    }
};
