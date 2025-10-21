<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;

class DeploymentController extends Controller
{
    /**
     * Handle GitHub webhook for automatic deployment
     */
    public function githubWebhook(Request $request)
    {
        // Verify GitHub webhook signature
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();
        $secret = config('deployflow.github_webhook_secret');
        
        if (!$this->verifyGitHubSignature($signature, $payload, $secret)) {
            Log::warning('Invalid GitHub webhook signature');
            return response('Unauthorized', 401);
        }
        
        $event = $request->header('X-GitHub-Event');
        
        if ($event === 'push') {
            $this->handlePushEvent($request->json()->all());
        }
        
        return response('OK', 200);
    }
    
    /**
     * Handle push event from GitHub
     */
    private function handlePushEvent(array $payload)
    {
        $ref = $payload['ref'] ?? '';
        $branch = str_replace('refs/heads/', '', $ref);
        
        Log::info("GitHub push event received for branch: {$branch}");
        
        // Only deploy from main/production branches
        if (!in_array($branch, ['main', 'production'])) {
            Log::info("Skipping deployment for branch: {$branch}");
            return;
        }
        
        $this->deploy($branch);
    }
    
    /**
     * Execute deployment
     */
    private function deploy(string $branch)
    {
        try {
            Log::info("Starting deployment for branch: {$branch}");
            
            // Pull latest changes
            $this->runCommand('git pull origin ' . $branch);
            
            // Install/update dependencies
            $this->runCommand('composer install --no-dev --optimize-autoloader');
            $this->runCommand('npm ci');
            $this->runCommand('npm run build');
            
            // Run migrations
            $this->runCommand('php artisan migrate --force');
            
            // Clear caches
            $this->runCommand('php artisan config:cache');
            $this->runCommand('php artisan route:cache');
            $this->runCommand('php artisan view:cache');
            
            // Restart queue workers
            $this->runCommand('php artisan queue:restart');
            
            Log::info("Deployment completed successfully for branch: {$branch}");
            
        } catch (\Exception $e) {
            Log::error("Deployment failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Run shell command
     */
    private function runCommand(string $command)
    {
        Log::info("Running command: {$command}");
        
        $result = Process::run($command);
        
        if (!$result->successful()) {
            throw new \Exception("Command failed: {$command} - " . $result->errorOutput());
        }
        
        Log::info("Command completed: {$command}");
        return $result->output();
    }
    
    /**
     * Verify GitHub webhook signature
     */
    private function verifyGitHubSignature(string $signature, string $payload, string $secret): bool
    {
        if (!$signature || !$secret) {
            return false;
        }
        
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Manual deployment trigger
     */
    public function manualDeploy(Request $request)
    {
        $this->authorize('deploy', auth()->user());
        
        $branch = $request->input('branch', 'main');
        
        try {
            $this->deploy($branch);
            
            return response()->json([
                'success' => true,
                'message' => 'Deployment completed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deployment failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get deployment status
     */
    public function deploymentStatus()
    {
        $status = [
            'last_deployment' => $this->getLastDeploymentTime(),
            'current_branch' => $this->getCurrentBranch(),
            'git_status' => $this->getGitStatus(),
            'server_status' => $this->getServerStatus(),
        ];
        
        return response()->json($status);
    }
    
    /**
     * Get last deployment time
     */
    private function getLastDeploymentTime(): ?string
    {
        $deploymentFile = storage_path('app/deployment.log');
        
        if (file_exists($deploymentFile)) {
            return date('Y-m-d H:i:s', filemtime($deploymentFile));
        }
        
        return null;
    }
    
    /**
     * Get current git branch
     */
    private function getCurrentBranch(): string
    {
        $result = Process::run('git branch --show-current');
        return trim($result->output());
    }
    
    /**
     * Get git status
     */
    private function getGitStatus(): array
    {
        $status = Process::run('git status --porcelain');
        $output = $status->output();
        
        return [
            'has_changes' => !empty(trim($output)),
            'changes' => $output ? explode("\n", trim($output)) : []
        ];
    }
    
    /**
     * Get server status
     */
    private function getServerStatus(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_usage' => memory_get_usage(true),
            'disk_free' => disk_free_space(base_path()),
            'uptime' => $this->getUptime(),
        ];
    }
    
    /**
     * Get server uptime
     */
    private function getUptime(): string
    {
        $result = Process::run('uptime');
        return trim($result->output());
    }
}
