<?php

namespace App\Actions\Server;

use App\Models\Server;
use Lorisleiva\Actions\Concerns\AsAction;

class CleanupDocker
{
    use AsAction;

    public string $jobQueue = 'high';

    public function handle(Server $server, bool $deleteUnusedVolumes = false, bool $deleteUnusedNetworks = false)
    {
        $settings = instanceSettings();
        $realtimeImage = config('constants.deployflow.realtime_image');
        $realtimeImageVersion = config('constants.deployflow.realtime_version');
        $realtimeImageWithVersion = "$realtimeImage:$realtimeImageVersion";
        $realtimeImageWithoutPrefix = 'coollabsio/deployflow-realtime';
        $realtimeImageWithoutPrefixVersion = "coollabsio/deployflow-realtime:$realtimeImageVersion";

        $helperImageVersion = getHelperVersion();
        $helperImage = config('constants.deployflow.helper_image');
        $helperImageWithVersion = "$helperImage:$helperImageVersion";
        $helperImageWithoutPrefix = 'coollabsio/coolify-helper';
        $helperImageWithoutPrefixVersion = "coollabsio/coolify-helper:$helperImageVersion";

        $commands = [
            'docker container prune -f --filter "label=deployflow.managed=true" --filter "label!=deployflow.proxy=true"',
            'docker image prune -af --filter "label!=deployflow.managed=true"',
            'docker builder prune -af',
            "docker images --filter before=$helperImageWithVersion --filter reference=$helperImage | grep $helperImage | awk '{print $3}' | xargs -r docker rmi -f",
            "docker images --filter before=$realtimeImageWithVersion --filter reference=$realtimeImage | grep $realtimeImage | awk '{print $3}' | xargs -r docker rmi -f",
            "docker images --filter before=$helperImageWithoutPrefixVersion --filter reference=$helperImageWithoutPrefix | grep $helperImageWithoutPrefix | awk '{print $3}' | xargs -r docker rmi -f",
            "docker images --filter before=$realtimeImageWithoutPrefixVersion --filter reference=$realtimeImageWithoutPrefix | grep $realtimeImageWithoutPrefix | awk '{print $3}' | xargs -r docker rmi -f",
        ];

        if ($deleteUnusedVolumes) {
            $commands[] = 'docker volume prune -af';
        }

        if ($deleteUnusedNetworks) {
            $commands[] = 'docker network prune -f';
        }

        $cleanupLog = [];
        foreach ($commands as $command) {
            $commandOutput = instant_remote_process([$command], $server, false);
            if ($commandOutput !== null) {
                $cleanupLog[] = [
                    'command' => $command,
                    'output' => $commandOutput,
                ];
            }
        }

        return $cleanupLog;
    }
}
