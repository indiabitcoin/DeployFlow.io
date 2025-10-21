<div>
    <div class="deployflow-quick-deploy">
        <h3 class="deployflow-section-title">Quick Deploy</h3>
        
        <div class="deployflow-deploy-controls">
            <x-forms.button 
                wire:click="deploy" 
                :disabled="$isDeploying"
                class="deployflow-button-primary">
                @if($isDeploying)
                    <span class="animate-spin">⏳</span> Deploying...
                @else
                    🚀 Deploy Now
                @endif
            </x-forms.button>
            
            <x-forms.button 
                wire:click="checkStatus" 
                class="deployflow-button-secondary">
                📊 Check Status
            </x-forms.button>
        </div>
        
        @if($deploymentStatus)
            <div class="deployflow-deployment-status">
                <div class="deployflow-status-message">
                    {{ $deploymentStatus }}
                </div>
            </div>
        @endif
        
        @if(!empty($deploymentLogs))
            <div class="deployflow-deployment-logs">
                <h4 class="deployflow-logs-title">Deployment Logs</h4>
                <div class="deployflow-logs-content">
                    @foreach($deploymentLogs as $log)
                        <div class="deployflow-log-entry">
                            {{ $log }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <div class="deployflow-deploy-info">
            <h4 class="deployflow-info-title">Deployment Options</h4>
            <div class="deployflow-info-grid">
                <div class="deployflow-info-item">
                    <strong>GitHub Webhook:</strong>
                    <code>{{ route('webhooks.deploy') }}</code>
                </div>
                <div class="deployflow-info-item">
                    <strong>Manual Deploy:</strong>
                    <code>./deploy.sh railway</code>
                </div>
                <div class="deployflow-info-item">
                    <strong>VPS Deploy:</strong>
                    <code>./deploy.sh vps</code>
                </div>
                <div class="deployflow-info-item">
                    <strong>Docker Deploy:</strong>
                    <code>./deploy.sh docker</code>
                </div>
            </div>
        </div>
    </div>
</div>
