<?php

namespace App\Services\DeployFlow\Steps;

class BuildStepExecutor extends BaseStepExecutor
{
    protected function performExecution(): array
    {
        $dockerfile = $this->getConfig('dockerfile', 'Dockerfile');
        $context = $this->getConfig('context', '.');
        $buildArgs = $this->getConfig('args', []);
        $imageTag = $this->getConfig('image_tag', 'latest');

        $this->log('info', "Building Docker image with Dockerfile: {$dockerfile}");
        $this->log('info', "Build context: {$context}");
        
        // Simulate build process
        $this->simulateBuildProcess();
        
        $imageName = $this->generateImageName();
        
        return [
            'image_name' => $imageName,
            'image_tag' => $imageTag,
            'build_time' => time(),
            'dockerfile' => $dockerfile,
            'context' => $context,
            'build_args' => $buildArgs,
        ];
    }

    public function getDescription(): string
    {
        return 'Build Application';
    }

    public function getConfigSchema(): array
    {
        return [
            'dockerfile' => [
                'type' => 'string',
                'required' => false,
                'default' => 'Dockerfile',
                'description' => 'Path to Dockerfile',
            ],
            'context' => [
                'type' => 'string',
                'required' => false,
                'default' => '.',
                'description' => 'Build context directory',
            ],
            'args' => [
                'type' => 'array',
                'required' => false,
                'default' => [],
                'description' => 'Build arguments',
            ],
            'image_tag' => [
                'type' => 'string',
                'required' => false,
                'default' => 'latest',
                'description' => 'Image tag',
            ],
        ];
    }

    protected function simulateBuildProcess(): void
    {
        // Simulate build steps
        $steps = [
            'Preparing build context...',
            'Reading Dockerfile...',
            'Building image layers...',
            'Installing dependencies...',
            'Copying application files...',
            'Setting up runtime environment...',
            'Finalizing image...',
        ];

        foreach ($steps as $step) {
            $this->log('info', $step);
            // Simulate processing time
            usleep(200000); // 0.2 seconds
        }
    }

    protected function generateImageName(): string
    {
        $flow = $this->getContext('flow');
        $execution = $this->getContext('execution');
        
        return sprintf(
            'deployflow/%s:%s-%d',
            strtolower(str_replace(' ', '-', $flow->name)),
            $this->getConfig('image_tag', 'latest'),
            $execution->id
        );
    }
}
