<?php

namespace App\Services\DeployFlow;

use App\Services\DeployFlow\Steps\BuildStepExecutor;
use App\Services\DeployFlow\Steps\TestStepExecutor;
use App\Services\DeployFlow\Steps\DeployStepExecutor;
use App\Services\DeployFlow\Steps\VerifyStepExecutor;
use App\Services\DeployFlow\Steps\MonitorStepExecutor;
use App\Services\DeployFlow\Steps\NotifyStepExecutor;
use App\Services\DeployFlow\Steps\RollbackStepExecutor;
use App\Services\DeployFlow\Steps\ScaleStepExecutor;
use InvalidArgumentException;

class StepExecutorFactory
{
    protected array $executors = [];

    public function __construct()
    {
        $this->registerDefaultExecutors();
    }

    /**
     * Register default step executors.
     */
    protected function registerDefaultExecutors(): void
    {
        $this->executors = [
            'build' => BuildStepExecutor::class,
            'test' => TestStepExecutor::class,
            'deploy' => DeployStepExecutor::class,
            'verify' => VerifyStepExecutor::class,
            'monitor' => MonitorStepExecutor::class,
            'notify' => NotifyStepExecutor::class,
            'rollback' => RollbackStepExecutor::class,
            'scale' => ScaleStepExecutor::class,
        ];
    }

    /**
     * Create a step executor for the given step type.
     */
    public function create(string $stepType): StepExecutorInterface
    {
        if (!isset($this->executors[$stepType])) {
            throw new InvalidArgumentException("No executor found for step type: {$stepType}");
        }

        $executorClass = $this->executors[$stepType];
        
        if (!class_exists($executorClass)) {
            throw new InvalidArgumentException("Executor class does not exist: {$executorClass}");
        }

        return app($executorClass);
    }

    /**
     * Register a custom step executor.
     */
    public function register(string $stepType, string $executorClass): void
    {
        $this->executors[$stepType] = $executorClass;
    }

    /**
     * Get all registered step types.
     */
    public function getRegisteredStepTypes(): array
    {
        return array_keys($this->executors);
    }

    /**
     * Check if a step type is registered.
     */
    public function hasExecutor(string $stepType): bool
    {
        return isset($this->executors[$stepType]);
    }
}
