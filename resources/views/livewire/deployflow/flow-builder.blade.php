<div>
    <x-slot:title>
        Flow Builder | DeployFlow.io
    </x-slot>
    
    <!-- DeployFlow.io Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Flow Builder
                </h1>
                <div class="text-lg text-gray-600 dark:text-gray-400">
                    Create and customize your deployment flows
                </div>
            </div>
            <div class="flex space-x-3">
                <button wire:click="testFlow" 
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md font-medium transition-colors">
                    Test Flow
                </button>
                <button wire:click="saveFlow" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium transition-colors">
                    Save Flow
                </button>
            </div>
        </div>
    </div>

    <!-- Flow Configuration -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Flow Settings -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Flow Settings</h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Flow Name
                        </label>
                        <input wire:model="flowName" 
                               type="text" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="Enter flow name">
                        @error('flowName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <textarea wire:model="flowDescription" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="Describe your deployment flow"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Template
                        </label>
                        <select wire:model="flowTemplate" 
                                wire:change="applyTemplate($event.target.value)"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="simple">Simple Deployment</option>
                            <option value="production">Production Ready</option>
                            <option value="microservices">Microservices Flow</option>
                            <option value="static_site">Static Site Flow</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Available Steps -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mt-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Available Steps</h2>
                
                <div class="space-y-2">
                    @foreach ($availableSteps as $stepType => $stepName)
                        <button wire:click="addStep('{{ $stepType }}')" 
                                class="w-full text-left px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 transition-colors">
                            + {{ $stepName }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Flow Visualization -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Deployment Flow</h2>
                
                @if (count($flowSteps) > 0)
                    <div class="space-y-4">
                        @foreach ($flowSteps as $index => $step)
                            <div class="relative">
                                <!-- Flow Step Card -->
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border-2 border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <!-- Step Number -->
                                            <div class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-medium">
                                                {{ $step['position'] + 1 }}
                                            </div>
                                            
                                            <!-- Step Info -->
                                            <div class="flex-1">
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                                    {{ $step['name'] }}
                                                </h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ ucfirst($step['type']) }} Step
                                                </p>
                                            </div>
                                            
                                            <!-- Step Status -->
                                            <div class="flex items-center space-x-2">
                                                <label class="flex items-center">
                                                    <input type="checkbox" 
                                                           wire:click="toggleStep('{{ $step['id'] }}')"
                                                           {{ $step['enabled'] ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Enabled</span>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Step Actions -->
                                        <div class="flex items-center space-x-2">
                                            @if ($index > 0)
                                                <button wire:click="moveStepUp('{{ $step['id'] }}')" 
                                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                            
                                            @if ($index < count($flowSteps) - 1)
                                                <button wire:click="moveStepDown('{{ $step['id'] }}')" 
                                                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                            
                                            <button wire:click="removeStep('{{ $step['id'] }}')" 
                                                    class="p-1 text-red-400 hover:text-red-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Step Configuration -->
                                    <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Configuration</h4>
                                        <div class="space-y-2">
                                            @if ($step['type'] === 'build')
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-xs text-gray-500 dark:text-gray-400">Dockerfile</label>
                                                        <input type="text" 
                                                               value="{{ $step['config']['dockerfile'] ?? 'Dockerfile' }}"
                                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 dark:text-gray-400">Context</label>
                                                        <input type="text" 
                                                               value="{{ $step['config']['context'] ?? '.' }}"
                                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                    </div>
                                                </div>
                                            @elseif ($step['type'] === 'test')
                                                <div>
                                                    <label class="block text-xs text-gray-500 dark:text-gray-400">Command</label>
                                                    <input type="text" 
                                                           value="{{ $step['config']['command'] ?? 'npm test' }}"
                                                           class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                </div>
                                            @elseif ($step['type'] === 'deploy')
                                                <div class="grid grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-xs text-gray-500 dark:text-gray-400">Strategy</label>
                                                        <select class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                            <option {{ ($step['config']['strategy'] ?? 'rolling') === 'rolling' ? 'selected' : '' }}>Rolling</option>
                                                            <option {{ ($step['config']['strategy'] ?? 'rolling') === 'blue_green' ? 'selected' : '' }}>Blue-Green</option>
                                                            <option {{ ($step['config']['strategy'] ?? 'rolling') === 'canary' ? 'selected' : '' }}>Canary</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs text-gray-500 dark:text-gray-400">Replicas</label>
                                                        <input type="number" 
                                                               value="{{ $step['config']['replicas'] ?? 1 }}"
                                                               class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                    </div>
                                                </div>
                                            @elseif ($step['type'] === 'verify')
                                                <div>
                                                    <label class="block text-xs text-gray-500 dark:text-gray-400">Health Check URL</label>
                                                    <input type="text" 
                                                           value="{{ $step['config']['health_check_url'] ?? '/health' }}"
                                                           class="w-full px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    Configuration options will be available for this step type.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Flow Arrow -->
                                @if ($index < count($flowSteps) - 1)
                                    <div class="flex justify-center my-2">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No steps in flow</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add steps from the sidebar to build your deployment flow.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Flow Preview -->
    @if (count($flowSteps) > 0)
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Flow Preview</h2>
            
            <div class="flex items-center space-x-4 overflow-x-auto">
                @foreach ($flowSteps as $index => $step)
                    <div class="flex items-center">
                        <!-- Step -->
                        <div class="flex items-center space-x-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-medium">
                                {{ $step['position'] + 1 }}
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $step['name'] }}</span>
                            @if (!$step['enabled'])
                                <span class="text-xs text-gray-500 dark:text-gray-400">(Disabled)</span>
                            @endif
                        </div>
                        
                        <!-- Arrow -->
                        @if ($index < count($flowSteps) - 1)
                            <svg class="w-4 h-4 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
