<?php

namespace App\Services\DeployFlow;

use App\Models\DeployFlow;
use App\Models\DeployFlowExecution;
use App\Models\DeployFlowStepExecution;
use App\Services\DeployFlow\Steps\StepExecutorFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class FlowExecutionEngine
{
    protected StepExecutorFactory $stepExecutorFactory;
    protected array $executionContext = [];

    public function __construct(StepExecutorFactory $stepExecutorFactory)
    {
        $this->stepExecutorFactory = $stepExecutorFactory;
    }

    /**
     * Execute a deploy flow.
     */
    public function execute(DeployFlow $flow, array $context = []): DeployFlowExecution
    {
        // Create execution record
        $execution = DeployFlowExecution::create([
            'deploy_flow_id' => $flow->id,
            'triggered_by' => auth()->id(),
            'trigger_type' => $context['trigger_type'] ?? 'manual',
            'trigger_data' => $context['trigger_data'] ?? [],
            'metadata' => $context['metadata'] ?? [],
        ]);

        try {
            // Start execution
            $execution->start();
            $this->executionContext = $context;

            // Execute steps
            $this->executeSteps($flow, $execution);

            // Complete execution
            $execution->complete();

            // Dispatch success event
            Event::dispatch('deployflow.execution.completed', $execution);

            return $execution;

        } catch (\Exception $e) {
            // Handle execution failure
            $execution->fail($e->getMessage());
            
            Log::error('DeployFlow execution failed', [
                'flow_id' => $flow->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Dispatch failure event
            Event::dispatch('deployflow.execution.failed', $execution);

            return $execution;
        }
    }

    /**
     * Execute all steps in the flow.
     */
    protected function executeSteps(DeployFlow $flow, DeployFlowExecution $execution): void
    {
        $enabledSteps = $flow->getEnabledSteps();
        
        foreach ($enabledSteps as $stepIndex => $step) {
            try {
                $this->executeStep($step, $execution, $stepIndex);
            } catch (\Exception $e) {
                // Log step failure
                $execution->addLog('error', "Step '{$step['name']}' failed: " . $e->getMessage());
                
                // If step is critical, fail the entire execution
                if ($this->isCriticalStep($step)) {
                    throw $e;
                }
                
                // Otherwise, continue with next step
                continue;
            }
        }
    }

    /**
     * Execute a single step.
     */
    protected function executeStep(array $step, DeployFlowExecution $execution, int $stepIndex): void
    {
        // Create step execution record
        $stepExecution = DeployFlowStepExecution::create([
            'flow_execution_id' => $execution->id,
            'step_id' => $step['id'],
            'step_type' => $step['type'],
            'step_name' => $step['name'],
        ]);

        try {
            // Start step execution
            $stepExecution->start();
            $execution->addLog('info', "Starting step: {$step['name']}");

            // Get step executor
            $executor = $this->stepExecutorFactory->create($step['type']);
            
            // Prepare step context
            $stepContext = $this->prepareStepContext($step, $execution);
            
            // Execute the step
            $output = $executor->execute($stepContext);
            
            // Complete step execution
            $stepExecution->complete($output);
            $execution->addLog('info', "Completed step: {$step['name']}");

            // Add step execution to flow execution
            $execution->addStepExecution([
                'step_id' => $step['id'],
                'step_name' => $step['name'],
                'status' => 'completed',
                'duration' => $stepExecution->duration,
                'output' => $output,
            ]);

        } catch (\Exception $e) {
            // Handle step failure
            $stepExecution->fail($e->getMessage());
            $execution->addLog('error', "Step '{$step['name']}' failed: " . $e->getMessage());

            // Add failed step execution to flow execution
            $execution->addStepExecution([
                'step_id' => $step['id'],
                'step_name' => $step['name'],
                'status' => 'failed',
                'duration' => $stepExecution->duration,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Prepare context for step execution.
     */
    protected function prepareStepContext(array $step, DeployFlowExecution $execution): array
    {
        return [
            'step' => $step,
            'execution' => $execution,
            'flow' => $execution->deployFlow,
            'context' => $this->executionContext,
            'previous_steps_output' => $this->getPreviousStepsOutput($execution),
        ];
    }

    /**
     * Get output from previous steps.
     */
    protected function getPreviousStepsOutput(DeployFlowExecution $execution): array
    {
        $stepExecutions = $execution->step_executions ?? [];
        $outputs = [];

        foreach ($stepExecutions as $stepExecution) {
            if (isset($stepExecution['output'])) {
                $outputs[$stepExecution['step_id']] = $stepExecution['output'];
            }
        }

        return $outputs;
    }

    /**
     * Check if a step is critical (failure should stop the entire flow).
     */
    protected function isCriticalStep(array $step): bool
    {
        // By default, all steps are critical unless marked otherwise
        return !isset($step['config']['critical']) || $step['config']['critical'] !== false;
    }

    /**
     * Cancel a running execution.
     */
    public function cancel(DeployFlowExecution $execution): bool
    {
        if (!$execution->isRunning()) {
            return false;
        }

        try {
            // Cancel any running step executions
            $runningSteps = $execution->stepExecutions()->where('status', 'running')->get();
            
            foreach ($runningSteps as $stepExecution) {
                $this->cancelStepExecution($stepExecution);
            }

            // Cancel the main execution
            $execution->cancel();
            
            // Dispatch cancellation event
            Event::dispatch('deployflow.execution.cancelled', $execution);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to cancel DeployFlow execution', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cancel a step execution.
     */
    protected function cancelStepExecution(DeployFlowStepExecution $stepExecution): void
    {
        try {
            // Get step executor and attempt to cancel
            $executor = $this->stepExecutorFactory->create($stepExecution->step_type);
            
            if (method_exists($executor, 'cancel')) {
                $executor->cancel($stepExecution);
            }

            // Mark as cancelled
            $stepExecution->skip('Cancelled by user');

        } catch (\Exception $e) {
            Log::warning('Failed to cancel step execution', [
                'step_execution_id' => $stepExecution->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get execution status.
     */
    public function getExecutionStatus(DeployFlowExecution $execution): array
    {
        $stepExecutions = $execution->stepExecutions()->orderBy('created_at')->get();
        
        return [
            'execution_id' => $execution->id,
            'status' => $execution->status,
            'progress' => $execution->getProgressPercentage(),
            'duration' => $execution->getFormattedDuration(),
            'steps' => $stepExecutions->map(function ($stepExecution) {
                return [
                    'id' => $stepExecution->step_id,
                    'name' => $stepExecution->step_name,
                    'type' => $stepExecution->step_type,
                    'status' => $stepExecution->status,
                    'duration' => $stepExecution->getFormattedDuration(),
                    'error' => $stepExecution->error_message,
                ];
            }),
            'logs' => $execution->logs ?? [],
        ];
    }
}
