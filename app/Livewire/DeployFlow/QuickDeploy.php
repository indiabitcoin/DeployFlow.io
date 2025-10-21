<?php

namespace App\Livewire\DeployFlow;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuickDeploy extends Component
{
    public bool $isDeploying = false;
    public string $deploymentStatus = '';
    public array $deploymentLogs = [];
    
    public function deploy()
    {
        $this->isDeploying = true;
        $this->deploymentStatus = 'Starting deployment...';
        $this->deploymentLogs = [];
        
        try {
            // Trigger deployment via API
            $response = Http::post(route('deploy.manual'), [
                'branch' => 'main'
            ]);
            
            if ($response->successful()) {
                $this->deploymentStatus = 'Deployment completed successfully!';
                $this->deploymentLogs = $response->json()['logs'] ?? [];
            } else {
                $this->deploymentStatus = 'Deployment failed: ' . $response->body();
            }
            
        } catch (\Exception $e) {
            $this->deploymentStatus = 'Deployment error: ' . $e->getMessage();
            Log::error('Quick deploy failed', ['error' => $e->getMessage()]);
        }
        
        $this->isDeploying = false;
    }
    
    public function checkStatus()
    {
        try {
            $response = Http::get(route('deploy.status'));
            
            if ($response->successful()) {
                $status = $response->json();
                $this->deploymentStatus = "Last deployment: {$status['last_deployment']}";
            }
            
        } catch (\Exception $e) {
            $this->deploymentStatus = 'Unable to check status';
        }
    }
    
    public function render()
    {
        return view('livewire.deployflow.quick-deploy');
    }
}
