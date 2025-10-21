<?php

namespace App\Services\DeployFlow\Steps;

interface StepExecutorInterface
{
    /**
     * Execute the step.
     */
    public function execute(array $context): array;

    /**
     * Validate the step configuration.
     */
    public function validate(array $config): bool;

    /**
     * Get the step description.
     */
    public function getDescription(): string;

    /**
     * Get the step configuration schema.
     */
    public function getConfigSchema(): array;

    /**
     * Cancel the step execution (if supported).
     */
    public function cancel($stepExecution): void;
}
