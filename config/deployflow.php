<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DeployFlow.io Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options specific to DeployFlow.io
    | platform, including branding, features, and deployment flow settings.
    |
    */

    'branding' => [
        'name' => 'DeployFlow.io',
        'tagline' => 'Where Deployments Flow Smoothly',
        'description' => 'Visual deployment management made simple',
        'logo' => 'deployflow-logo.svg',
        'favicon' => 'deployflow-favicon.ico',
        'primary_color' => '#3B82F6', // Blue
        'secondary_color' => '#10B981', // Green
        'accent_color' => '#F59E0B', // Amber
    ],

    'features' => [
        'visual_flow_builder' => true,
        'flow_analytics' => true,
        'smart_suggestions' => true,
        'flow_templates' => true,
        'real_time_monitoring' => true,
        'ai_optimization' => false, // Future feature
    ],

    'deployment_flows' => [
        'default_steps' => [
            'build',
            'test',
            'deploy',
            'verify',
            'monitor'
        ],
        'available_steps' => [
            'build' => 'Build Application',
            'test' => 'Run Tests',
            'deploy' => 'Deploy to Server',
            'verify' => 'Health Check',
            'monitor' => 'Start Monitoring',
            'notify' => 'Send Notifications',
            'rollback' => 'Rollback on Failure',
            'scale' => 'Auto Scale',
        ],
        'flow_templates' => [
            'simple' => 'Simple Deployment',
            'production' => 'Production Ready',
            'microservices' => 'Microservices Flow',
            'static_site' => 'Static Site Flow',
        ],
    ],

    'analytics' => [
        'track_deployment_times' => true,
        'track_success_rates' => true,
        'track_resource_usage' => true,
        'flow_performance_metrics' => true,
    ],

    'ui' => [
        'theme' => 'deployflow',
        'dashboard_layout' => 'flow_based',
        'show_flow_builder' => true,
        'enable_flow_visualization' => true,
    ],
];
