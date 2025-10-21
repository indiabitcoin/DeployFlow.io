<?php

namespace App\Livewire\DeployFlow;

use App\Models\Project;
use App\Models\Server;
use App\Models\DeployFlow;
use App\Models\DeployFlowExecution;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public Collection $projects;
    public Collection $servers;
    public Collection $deploymentFlows;
    public array $flowMetrics = [];
    public array $recentDeployments = [];
    public bool $showFlowBuilder = false;

    public function mount()
    {
        $this->loadResources();
        $this->loadDeploymentFlows();
        $this->calculateFlowMetrics();
        $this->loadRecentDeployments();
    }

    public function loadResources()
    {
        $this->projects = Project::ownedByCurrentTeam()->with(['environments'])->get();
        $this->servers = Server::ownedByCurrentTeam()->get();
    }

    public function loadDeploymentFlows()
    {
        $this->deploymentFlows = DeployFlow::forTeam(auth()->user()->currentTeam->id)
            ->active()
            ->with(['latestExecution'])
            ->get()
            ->map(function ($flow) {
                return [
                    'id' => $flow->id,
                    'name' => $flow->name,
                    'description' => $flow->description,
                    'status' => $flow->getStatus(),
                    'last_run' => $flow->last_run_at,
                    'success_rate' => $flow->success_rate,
                    'steps' => count($flow->getEnabledSteps()),
                    'total_runs' => $flow->total_runs,
                    'average_duration' => $flow->getFormattedAverageDuration(),
                ];
            });
    }

    public function calculateFlowMetrics()
    {
        $this->flowMetrics = [
            'total_flows' => $this->deploymentFlows->count(),
            'active_flows' => $this->deploymentFlows->where('status', 'active')->count(),
            'success_rate' => $this->deploymentFlows->avg('success_rate'),
            'total_deployments' => $this->recentDeployments ? count($this->recentDeployments) : 0,
        ];
    }

    public function loadRecentDeployments()
    {
        $this->recentDeployments = DeployFlowExecution::with(['deployFlow'])
            ->whereHas('deployFlow', function ($query) {
                $query->where('team_id', auth()->user()->currentTeam->id);
            })
            ->recent(7)
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($execution) {
                return [
                    'id' => $execution->id,
                    'flow_name' => $execution->deployFlow->name,
                    'status' => $execution->status,
                    'duration' => $execution->getFormattedDuration(),
                    'timestamp' => $execution->started_at,
                ];
            })
            ->toArray();
    }

    public function createNewFlow()
    {
        return redirect()->route('deployflow.builder');
    }

    public function toggleFlowBuilder()
    {
        $this->showFlowBuilder = !$this->showFlowBuilder;
    }

    public function getListeners()
    {
        $teamId = auth()->user()->currentTeam()->id;

        return [
            "echo-private:team.{$teamId},DeploymentStatusChanged" => 'refreshDeployments',
            "echo-private:team.{$teamId},FlowStatusChanged" => 'refreshFlows',
        ];
    }

    public function refreshDeployments()
    {
        $this->loadRecentDeployments();
        $this->calculateFlowMetrics();
    }

    public function refreshFlows()
    {
        $this->loadDeploymentFlows();
        $this->calculateFlowMetrics();
    }

    public function render()
    {
        return view('livewire.deployflow.dashboard')->layout('layouts.deployflow');
    }
}
