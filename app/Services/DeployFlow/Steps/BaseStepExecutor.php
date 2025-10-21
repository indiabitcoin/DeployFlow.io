<?php

namespace App\Services\DeployFlow\Steps;

use App\Models\DeployFlowStepExecution;

abstract class BaseStepExecutor implements StepExecutorInterface
{
    protected array $config;
    protected array $context;
    protected ?DeployFlowStepExecution $stepExecution = null;

    /**
     * Execute the step.
     */
    public function execute(array $context): array
    {
        $this->context = $context;
        $this->config = $context['step']['config'] ?? [];
        $this->stepExecution = $context['step_execution'] ?? null;

        // Validate configuration
        if (!$this->validate($this->config)) {
            throw new \InvalidArgumentException('Invalid step configuration');
        }

        // Add log entry
        $this->log('info', "Starting {$this->getDescription()}");

        try {
            // Execute the step
            $result = $this->performExecution();
            
            // Add success log
            $this->log('info', "Completed {$this->getDescription()}");
            
            return $result;

        } catch (\Exception $e) {
            // Add error log
            $this->log('error', "Failed {$this->getDescription()}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Perform the actual step execution.
     */
    abstract protected function performExecution(): array;

    /**
     * Validate the step configuration.
     */
    public function validate(array $config): bool
    {
        $schema = $this->getConfigSchema();
        
        foreach ($schema as $field => $rules) {
            if (isset($rules['required']) && $rules['required'] && !isset($config[$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get the step description.
     */
    abstract public function getDescription(): string;

    /**
     * Get the step configuration schema.
     */
    abstract public function getConfigSchema(): array;

    /**
     * Cancel the step execution.
     */
    public function cancel($stepExecution): void
    {
        $this->log('warning', "Cancelling {$this->getDescription()}");
        // Override in subclasses if cancellation is supported
    }

    /**
     * Add a log entry.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if ($this->stepExecution) {
            $this->stepExecution->addLog($level, $message, $context);
        }
    }

    /**
     * Get configuration value with default.
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get context value.
     */
    protected function getContext(string $key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Get output from previous steps.
     */
    protected function getPreviousStepsOutput(): array
    {
        return $this->getContext('previous_steps_output', []);
    }

    /**
     * Get output from a specific previous step.
     */
    protected function getPreviousStepOutput(string $stepId): ?array
    {
        $outputs = $this->getPreviousStepsOutput();
        return $outputs[$stepId] ?? null;
    }
}
