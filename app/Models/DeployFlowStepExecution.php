<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeployFlowStepExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_execution_id',
        'step_id',
        'step_type',
        'step_name',
        'status',
        'started_at',
        'completed_at',
        'duration',
        'logs',
        'error_message',
        'output',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration' => 'integer', // in seconds
        'logs' => 'array',
        'output' => 'array',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'duration' => 0,
        'logs' => '[]',
        'output' => '{}',
        'metadata' => '{}',
    ];

    /**
     * Get the flow execution that owns this step execution.
     */
    public function flowExecution(): BelongsTo
    {
        return $this->belongsTo(DeployFlowExecution::class, 'flow_execution_id');
    }

    /**
     * Scope a query to only include step executions with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include step executions for a specific flow execution.
     */
    public function scopeForFlowExecution($query, int $flowExecutionId)
    {
        return $query->where('flow_execution_id', $flowExecutionId);
    }

    /**
     * Start the step execution.
     */
    public function start(): void
    {
        $this->status = 'running';
        $this->started_at = now();
        $this->save();
    }

    /**
     * Complete the step execution successfully.
     */
    public function complete(array $output = []): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->duration = $this->started_at ? $this->started_at->diffInSeconds($this->completed_at) : 0;
        $this->output = $output;
        $this->save();
    }

    /**
     * Fail the step execution.
     */
    public function fail(string $errorMessage = null, array $output = []): void
    {
        $this->status = 'failed';
        $this->completed_at = now();
        $this->duration = $this->started_at ? $this->started_at->diffInSeconds($this->completed_at) : 0;
        
        if ($errorMessage) {
            $this->error_message = $errorMessage;
        }
        
        $this->output = $output;
        $this->save();
    }

    /**
     * Skip the step execution.
     */
    public function skip(string $reason = null): void
    {
        $this->status = 'skipped';
        $this->completed_at = now();
        $this->duration = 0;
        
        if ($reason) {
            $this->error_message = $reason;
        }
        
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
            'completed' => 'green',
            'failed' => 'red',
            'running' => 'blue',
            'skipped' => 'gray',
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
            'completed' => 'Completed',
            'failed' => 'Failed',
            'running' => 'Running',
            'skipped' => 'Skipped',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    /**
     * Check if the step execution is running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the step execution is completed.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'skipped']);
    }

    /**
     * Check if the step execution was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the step execution failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the step execution was skipped.
     */
    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }
}
