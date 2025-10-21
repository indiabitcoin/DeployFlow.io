<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeployFlowExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'deploy_flow_id',
        'status',
        'started_at',
        'completed_at',
        'duration',
        'triggered_by',
        'trigger_type',
        'trigger_data',
        'logs',
        'error_message',
        'step_executions',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration' => 'integer', // in seconds
        'trigger_data' => 'array',
        'logs' => 'array',
        'step_executions' => 'array',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'duration' => 0,
        'trigger_type' => 'manual',
        'trigger_data' => '{}',
        'logs' => '[]',
        'step_executions' => '[]',
        'metadata' => '{}',
    ];

    /**
     * Get the deploy flow that owns this execution.
     */
    public function deployFlow(): BelongsTo
    {
        return $this->belongsTo(DeployFlow::class);
    }

    /**
     * Get the user who triggered this execution.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Get the step executions for this flow execution.
     */
    public function stepExecutions(): HasMany
    {
        return $this->hasMany(DeployFlowStepExecution::class, 'flow_execution_id');
    }

    /**
     * Scope a query to only include executions with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include executions for a specific flow.
     */
    public function scopeForFlow($query, int $flowId)
    {
        return $query->where('deploy_flow_id', $flowId);
    }

    /**
     * Scope a query to only include executions triggered by a specific user.
     */
    public function scopeTriggeredBy($query, int $userId)
    {
        return $query->where('triggered_by', $userId);
    }

    /**
     * Scope a query to only include recent executions.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    /**
     * Start the execution.
     */
    public function start(): void
    {
        $this->status = 'running';
        $this->started_at = now();
        $this->save();
    }

    /**
     * Complete the execution successfully.
     */
    public function complete(): void
    {
        $this->status = 'success';
        $this->completed_at = now();
        $this->duration = $this->started_at ? $this->started_at->diffInSeconds($this->completed_at) : 0;
        $this->save();

        // Update flow statistics
        $this->deployFlow->recordSuccess($this->duration);
    }

    /**
     * Fail the execution.
     */
    public function fail(string $errorMessage = null): void
    {
        $this->status = 'failed';
        $this->completed_at = now();
        $this->duration = $this->started_at ? $this->started_at->diffInSeconds($this->completed_at) : 0;
        
        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }
        
        $this->save();

        // Update flow statistics
        $this->deployFlow->recordFailure($this->duration);
    }

    /**
     * Cancel the execution.
     */
    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->completed_at = now();
        $this->duration = $this->started_at ? $this->started_at->diffInSeconds($this->completed_at) : 0;
        $this->save();
    }

    /**
     * Add a log entry.
     */
    public function addLog(string $level, string $message, array $context = []): void
    {
        $logs = $this->logs ?? [];
        $logs[] = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        
        $this->logs = $logs;
        $this->save();
    }

    /**
     * Add a step execution.
     */
    public function addStepExecution(array $stepExecution): void
    {
        $stepExecutions = $this->step_executions ?? [];
        $stepExecutions[] = $stepExecution;
        $this->step_executions = $stepExecutions;
        $this->save();
    }

    /**
     * Get the formatted duration.
     */
    public function getFormattedDuration(): string
    {
        $seconds = $this->duration;
        
        if ($seconds < 60) {
            return "{$seconds}s";
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return "{$minutes}m {$remainingSeconds}s";
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'success' => 'green',
            'failed' => 'red',
            'running' => 'blue',
            'cancelled' => 'gray',
            'pending' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get the status badge text.
     */
    public function getStatusText(): string
    {
        return match ($this->status) {
            'success' => 'Success',
            'failed' => 'Failed',
            'running' => 'Running',
            'cancelled' => 'Cancelled',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    /**
     * Check if the execution is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the execution is completed.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['success', 'failed', 'cancelled']);
    }

    /**
     * Check if the execution was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the execution failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get the progress percentage based on completed steps.
     */
    public function getProgressPercentage(): int
    {
        $stepExecutions = $this->step_executions ?? [];
        $totalSteps = count($this->deployFlow->getEnabledSteps());
        
        if ($totalSteps === 0) {
            return 0;
        }
        
        $completedSteps = collect($stepExecutions)->where('status', 'completed')->count();
        return round(($completedSteps / $totalSteps) * 100);
    }
}
