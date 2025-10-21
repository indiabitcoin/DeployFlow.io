<?php

namespace App\Livewire\DeployFlow;

use App\Models\DeployFlow;
use App\Models\DeployFlowExecution;
use App\Services\DeployFlow\FlowExecutionEngine;
use Livewire\Component;
use Illuminate\Support\Str;

class FlowBuilder extends Component
{
    public array $flowSteps = [];
    public array $availableSteps = [];
    public string $flowName = '';
    public string $flowDescription = '';
    public string $flowTemplate = 'simple';
    public bool $isEditing = false;
    public ?int $editingFlowId = null;

    public function mount(?int $flowId = null)
    {
        $this->loadAvailableSteps();
        
        if ($flowId) {
            $this->loadExistingFlow($flowId);
        } else {
            $this->initializeDefaultFlow();
        }
    }

    public function loadAvailableSteps()
    {
        $this->availableSteps = config('deployflow.deployment_flows.available_steps');
    }

    public function initializeDefaultFlow()
    {
        $defaultSteps = config('deployflow.deployment_flows.default_steps');
        
        foreach ($defaultSteps as $index => $stepType) {
            $this->flowSteps[] = [
                'id' => Str::uuid(),
                'type' => $stepType,
                'name' => $this->availableSteps[$stepType] ?? ucfirst($stepType),
                'position' => $index,
                'config' => $this->getDefaultStepConfig($stepType),
                'enabled' => true,
            ];
        }
    }

    public function loadExistingFlow(int $flowId)
    {
        // This will be implemented when we have actual flow models
        $this->isEditing = true;
        $this->editingFlowId = $flowId;
        
        // For now, use default flow
        $this->initializeDefaultFlow();
    }

    public function getDefaultStepConfig(string $stepType): array
    {
        $configs = [
            'build' => [
                'dockerfile' => 'Dockerfile',
                'context' => '.',
                'args' => [],
            ],
            'test' => [
                'command' => 'npm test',
                'timeout' => 300,
            ],
            'deploy' => [
                'strategy' => 'rolling',
                'replicas' => 1,
            ],
            'verify' => [
                'health_check_url' => '/health',
                'timeout' => 30,
            ],
            'monitor' => [
                'metrics_enabled' => true,
                'alerts_enabled' => true,
            ],
        ];

        return $configs[$stepType] ?? [];
    }

    public function addStep(string $stepType)
    {
        $newStep = [
            'id' => Str::uuid(),
            'type' => $stepType,
            'name' => $this->availableSteps[$stepType] ?? ucfirst($stepType),
            'position' => count($this->flowSteps),
            'config' => $this->getDefaultStepConfig($stepType),
            'enabled' => true,
        ];

        $this->flowSteps[] = $newStep;
    }

    public function removeStep(string $stepId)
    {
        $this->flowSteps = collect($this->flowSteps)
            ->reject(fn($step) => $step['id'] === $stepId)
            ->values()
            ->toArray();

        $this->reorderSteps();
    }

    public function moveStepUp(string $stepId)
    {
        $this->reorderStep($stepId, -1);
    }

    public function moveStepDown(string $stepId)
    {
        $this->reorderStep($stepId, 1);
    }

    public function reorderStep(string $stepId, int $direction)
    {
        $steps = collect($this->flowSteps);
        $stepIndex = $steps->search(fn($step) => $step['id'] === $stepId);
        
        if ($stepIndex === false) return;
        
        $newIndex = $stepIndex + $direction;
        
        if ($newIndex < 0 || $newIndex >= count($this->flowSteps)) return;
        
        $step = $this->flowSteps[$stepIndex];
        unset($this->flowSteps[$stepIndex]);
        
        array_splice($this->flowSteps, $newIndex, 0, [$step]);
        
        $this->reorderSteps();
    }

    public function reorderSteps()
    {
        foreach ($this->flowSteps as $index => &$step) {
            $step['position'] = $index;
        }
    }

    public function toggleStep(string $stepId)
    {
        foreach ($this->flowSteps as &$step) {
            if ($step['id'] === $stepId) {
                $step['enabled'] = !$step['enabled'];
                break;
            }
        }
    }

    public function updateStepConfig(string $stepId, array $config)
    {
        foreach ($this->flowSteps as &$step) {
            if ($step['id'] === $stepId) {
                $step['config'] = array_merge($step['config'], $config);
                break;
            }
        }
    }

    public function applyTemplate(string $template)
    {
        $this->flowTemplate = $template;
        
        // Clear existing steps
        $this->flowSteps = [];
        
        // Apply template-specific steps
        switch ($template) {
            case 'production':
                $this->flowSteps = $this->getProductionTemplate();
                break;
            case 'microservices':
                $this->flowSteps = $this->getMicroservicesTemplate();
                break;
            case 'static_site':
                $this->flowSteps = $this->getStaticSiteTemplate();
                break;
            default:
                $this->initializeDefaultFlow();
        }
    }

    public function getProductionTemplate(): array
    {
        return [
            [
                'id' => Str::uuid(),
                'type' => 'build',
                'name' => 'Build Application',
                'position' => 0,
                'config' => $this->getDefaultStepConfig('build'),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'test',
                'name' => 'Run Tests',
                'position' => 1,
                'config' => $this->getDefaultStepConfig('test'),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'deploy',
                'name' => 'Deploy to Production',
                'position' => 2,
                'config' => array_merge($this->getDefaultStepConfig('deploy'), [
                    'strategy' => 'blue_green',
                    'replicas' => 3,
                ]),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'verify',
                'name' => 'Health Check',
                'position' => 3,
                'config' => $this->getDefaultStepConfig('verify'),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'monitor',
                'name' => 'Start Monitoring',
                'position' => 4,
                'config' => $this->getDefaultStepConfig('monitor'),
                'enabled' => true,
            ],
        ];
    }

    public function getMicroservicesTemplate(): array
    {
        return [
            [
                'id' => Str::uuid(),
                'type' => 'build',
                'name' => 'Build Services',
                'position' => 0,
                'config' => $this->getDefaultStepConfig('build'),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'test',
                'name' => 'Integration Tests',
                'position' => 1,
                'config' => $this->getDefaultStepConfig('test'),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'deploy',
                'name' => 'Deploy Services',
                'position' => 2,
                'config' => array_merge($this->getDefaultStepConfig('deploy'), [
                    'strategy' => 'canary',
                ]),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'scale',
                'name' => 'Auto Scale',
                'position' => 3,
                'config' => [
                    'min_replicas' => 2,
                    'max_replicas' => 10,
                    'cpu_threshold' => 70,
                ],
                'enabled' => true,
            ],
        ];
    }

    public function getStaticSiteTemplate(): array
    {
        return [
            [
                'id' => Str::uuid(),
                'type' => 'build',
                'name' => 'Build Static Site',
                'position' => 0,
                'config' => array_merge($this->getDefaultStepConfig('build'), [
                    'build_command' => 'npm run build',
                ]),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'deploy',
                'name' => 'Deploy to CDN',
                'position' => 1,
                'config' => array_merge($this->getDefaultStepConfig('deploy'), [
                    'strategy' => 'static',
                    'cdn_enabled' => true,
                ]),
                'enabled' => true,
            ],
            [
                'id' => Str::uuid(),
                'type' => 'verify',
                'name' => 'Verify Deployment',
                'position' => 2,
                'config' => $this->getDefaultStepConfig('verify'),
                'enabled' => true,
            ],
        ];
    }

    public function saveFlow()
    {
        $this->validate([
            'flowName' => 'required|string|max:255',
            'flowDescription' => 'nullable|string|max:500',
            'flowSteps' => 'required|array|min:1',
        ]);

        try {
            $flowData = [
                'name' => $this->flowName,
                'description' => $this->flowDescription,
                'template' => $this->flowTemplate,
                'steps' => $this->flowSteps,
                'team_id' => auth()->user()->currentTeam->id,
                'created_by' => auth()->id(),
                'is_active' => true,
            ];

            if ($this->isEditing && $this->editingFlowId) {
                // Update existing flow
                $flow = DeployFlow::findOrFail($this->editingFlowId);
                $flow->update($flowData);
                $this->dispatch('success', 'Flow updated successfully!');
            } else {
                // Create new flow
                $flow = DeployFlow::create($flowData);
                $this->dispatch('success', 'Flow created successfully!');
            }
            
            return redirect()->route('deployflow.dashboard');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to save flow: ' . $e->getMessage());
        }
    }

    public function testFlow()
    {
        try {
            // First save the flow if it's not already saved
            if (!$this->isEditing) {
                $this->saveFlow();
                return;
            }

            // Get the flow
            $flow = DeployFlow::findOrFail($this->editingFlowId);
            
            // Execute the flow
            $executionEngine = app(FlowExecutionEngine::class);
            $execution = $executionEngine->execute($flow, [
                'trigger_type' => 'test',
                'trigger_data' => ['source' => 'flow_builder'],
                'metadata' => ['test_run' => true],
            ]);

            $this->dispatch('success', "Flow test started! Execution ID: {$execution->id}");
            
        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to test flow: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.deployflow.flow-builder')->layout('layouts.deployflow');
    }
}
