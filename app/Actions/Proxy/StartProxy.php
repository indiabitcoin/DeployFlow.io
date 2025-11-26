<?php

namespace App\Actions\Proxy;

use App\Enums\ProxyTypes;
use App\Events\ProxyStatusChanged;
use App\Events\ProxyStatusChangedUI;
use App\Models\Server;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Activitylog\Models\Activity;

class StartProxy
{
    use AsAction;

    public function handle(Server $server, bool $async = true, bool $force = false, bool $restarting = false): string|Activity
    {
        $proxyType = $server->proxyType();
        if ((is_null($proxyType) || $proxyType === 'NONE' || $server->proxy->force_stop || $server->isBuildServer()) && $force === false) {
            return 'OK';
        }
        $server->proxy->set('status', 'starting');
        $server->save();
        $server->refresh();

        if (! $restarting) {
            ProxyStatusChangedUI::dispatch($server->team_id);
        }

        $commands = collect([]);
        $proxy_path = $server->proxyPath();
        $configuration = GetProxyConfiguration::run($server);
        if (! $configuration) {
            throw new \Exception('Configuration is not synced');
        }
        SaveProxyConfiguration::run($server, $configuration);
        $docker_compose_yml_base64 = base64_encode($configuration);
        $server->proxy->last_applied_settings = str($docker_compose_yml_base64)->pipe('md5')->value();
        $server->save();

        if ($server->isSwarmManager()) {
            $commands = $commands->merge([
                "mkdir -p $proxy_path/dynamic",
                "cd $proxy_path",
                "echo 'Creating required Docker Compose file.'",
                "echo 'Starting deployflow-proxy.'",
                'docker stack deploy --detach=true -c docker-compose.yml deployflow-proxy',
                "echo 'Successfully started deployflow-proxy.'",
            ]);
        } else {
            if (isDev()) {
                if ($proxyType === ProxyTypes::CADDY->value) {
                    $proxy_path = '/data/coolify/proxy/caddy';
                }
            }
            $caddyfile = 'import /dynamic/*.caddy';
            $commands = $commands->merge([
                "mkdir -p $proxy_path/dynamic",
                "cd $proxy_path",
                "echo '$caddyfile' > $proxy_path/dynamic/Caddyfile",
                "echo 'Creating required Docker Compose file.'",
                "echo 'Pulling docker image.'",
                'docker compose pull',
                'if docker ps -a --format "{{.Names}}" | grep -q "^deployflow-proxy$"; then',
                "    echo 'Stopping and removing existing deployflow-proxy.'",
                '    docker stop deployflow-proxy 2>/dev/null || true',
                '    docker rm -f deployflow-proxy 2>/dev/null || true',
                '    # Wait for container to be fully removed',
                '    for i in {1..10}; do',
                '        if ! docker ps -a --format "{{.Names}}" | grep -q "^deployflow-proxy$"; then',
                '            break',
                '        fi',
                '        echo "Waiting for deployflow-proxy to be removed... ($i/10)"',
                '        sleep 1',
                '    done',
                "    echo 'Successfully stopped and removed existing deployflow-proxy.'",
                'fi',
                "echo 'Starting deployflow-proxy.'",
                'docker compose up -d --wait --remove-orphans',
                "echo 'Successfully started deployflow-proxy.'",
            ]);
            $commands = $commands->merge(connectProxyToNetworks($server));
        }

        if ($async) {
            return remote_process($commands, $server, callEventOnFinish: 'ProxyStatusChanged', callEventData: $server->id);
        } else {
            instant_remote_process($commands, $server);

            $server->proxy->set('type', $proxyType);
            $server->save();
            ProxyStatusChanged::dispatch($server->id);

            return 'OK';
        }
    }
}
