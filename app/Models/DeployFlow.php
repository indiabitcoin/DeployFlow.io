<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeployFlow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'template',
        'steps',
        'is_active',
        'team_id',
        'created_by',
        'last_run_at',
        'success_rate',
        'total_runs',
        'successful_runs',
        'failed_runs',
        'average_duration',
        'settings',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'success_rate' => 'decimal:2',
        'total_runs' => 'integer',
        'successful_runs' => 'integer',
        'failed_runs' => 'integer',
        'average_duration' => 'integer', // in seconds
        'settings' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'success_rate' => 0.00,
        'total_runs' => 0,
        'successful_runs' => 0,
        'failed_runs' => 0,
        'average_duration' => 0,
        'steps' => '[]',
        'settings' => '{}',
    ];

    /**
     * Get the team that owns the deploy flow.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the deploy flow.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the flow executions for this deploy flow.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(DeployFlowExecution::class);
    }

    /**
     * Get the latest execution for this deploy flow.
     */
    public function latestExecution(): HasMany
    {
        return $this->hasMany(DeployFlowExecution::class)->latest();
    }

    /**
     * Scope a query to only include active flows.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include flows for a specific team.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope a query to only include flows by a specific template.
     */
    public function scopeByTemplate($query, $template)
    {
        return $query->where('template', $template);
    }

    /**
     * Get the flow steps as a collection.
     */
    public function getStepsCollection()
    {
        return collect($this->steps);
    }

    /**
     * Add a step to the flow.
     */
    public function addStep(array $step): void
    {
        $steps = $this->steps ?? [];
        $steps[] = $step;
        $this->steps = $steps;
    }

    /**
     * Remove a step from the flow by ID.
     */
    public function removeStep(string $stepId): void
    {
        $steps = collect($this->steps)->reject(function ($step) use ($stepId) {
            return ($step['id'] ?? '') === $stepId;
        })->values()->toArray();
        
        $this->steps = $steps;
    }

    /**
     * Update a step in the flow by ID.
     */
    public function updateStep(string $stepId, array $stepData): void
    {
        $steps = collect($this->steps)->map(function ($step) use ($stepId, $stepData) {
            if (($step['id'] ?? '') === $stepId) {
                return array_merge($step, $stepData);
            }
            return $step;
        })->toArray();
        
        $this->steps = $steps;
    }

    /**
     * Reorder steps in the flow.
     */
    public function reorderSteps(array $stepIds): void
    {
        $steps = collect($this->steps);
        $reorderedSteps = [];
        
        foreach ($stepIds as $index => $stepId) {
            $step = $steps->firstWhere('id', $stepId);
            if ($step) {
                $step['position'] = $index;
                $reorderedSteps[] = $step;
            }
        }
        
        $this->steps = $reorderedSteps;
    }

    /**
     * Get enabled steps only.
     */
    public function getEnabledSteps()
    {
        return collect($this->steps)->filter(function ($step) {
            return ($step['enabled'] ?? true) === true;
        })->values();
    }

    /**
     * Calculate and update success rate.
     */
    public function updateSuccessRate(): void
    {
        if ($this->total_runs > 0) {
            $this->success_rate = round(($this->successful_runs / $this->total_runs) * 100, 2);
        } else {
            $this->success_rate = 0.00;
        }
    }

    /**
     * Record a successful execution.
     */
    public function recordSuccess(int $duration): void
    {
        $this->increment('successful_runs');
        $this->increment('total_runs');
        $this->last_run_at = now();
        $this->updateAverageDuration($duration);
        $this->updateSuccessRate();
        $this->save();
    }

    /**
     * Record a failed execution.
     */
    public function recordFailure(int $duration): void
    {
        $this->increment('failed_runs');
        $this->increment('total_runs');
        $this->last_run_at = now();
        $this->updateAverageDuration($duration);
        $this->updateSuccessRate();
        $this->save();
    }

    /**
     * Update the average duration.
     */
    private function updateAverageDuration(int $duration): void
    {
        $totalDuration = ($this->average_duration * ($this->total_runs - 1)) + $duration;
        $this->average_duration = round($totalDuration / $this->total_runs);
    }

    /**
     * Get the formatted average duration.
     */
    public function getFormattedAverageDuration(): string
    {
        $seconds = $this->average_duration;
        
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
     * Get the flow status based on recent executions.
     */
    public function getStatus(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->total_runs === 0) {
            return 'never_run';
        }

        $latestExecution = $this->latestExecution()->first();
        if (!$latestExecution) {
            return 'unknown';
        }

        return $latestExecution->status;
    }

    /**
     * Get the flow status badge color.
     */
    public function getStatusColor(): string
    {
        $status = $this->getStatus();
        
        return match ($status) {
            'success' => 'green',
            'failed' => 'red',
            'running' => 'blue',
            'inactive' => 'gray',
            'never_run' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Duplicate this flow.
     */
    public function duplicate(string $newName): self
    {
        return self::create([
            'name' => $newName,
            'description' => $this->description . ' (Copy)',
            'template' => $this->template,
            'steps' => $this->steps,
            'is_active' => false, // Start as inactive
            'team_id' => $this->team_id,
            'created_by' => auth()->id(),
            'settings' => $this->settings,
        ]);
    }
}
