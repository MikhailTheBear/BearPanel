<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DockerRuntime
{
    public function start(Server $server): void
    {
        $server->refresh();
        $this->prepareServer($server);

        // если контейнер есть, но порт не совпадает — пересоздаём
        if ($server->container_id && $this->containerExists($server->container_id)) {
            if (!$this->containerHasHostPort($server->container_id, (int)$server->host_port, (int)$server->allocation_port)) {
                $this->recreate($server);
            }
        }

        $containerId = $this->ensureContainer($server);

        if (!$this->isContainerRunning($containerId)) {
            $this->run(['docker', 'start', $containerId]);
        }

        $server->update(['status' => 'running', 'container_id' => $containerId]);
    }

    public function stop(Server $server): void
    {
        $server->refresh();

        if ($server->container_id) {
            $this->runQuiet(['docker', 'stop', $server->container_id]);
        }

        $server->update(['status' => 'stopped']);
    }

    public function restart(Server $server): void
    {
        $server->refresh();
        $this->prepareServer($server);

        $containerId = $this->ensureContainer($server);

        if ($this->isContainerRunning($containerId)) {
            $this->run(['docker', 'restart', $containerId]);
        } else {
            $this->run(['docker', 'start', $containerId]);
        }

        $server->update(['status' => 'running', 'container_id' => $containerId]);
    }

    public function recreate(Server $server): void
    {
        $server->refresh();
        $this->prepareServer($server);

        if ($server->container_id) {
            $this->runQuiet(['docker', 'rm', '-f', $server->container_id]);
        }
        $this->runQuiet(['docker', 'rm', '-f', $this->containerName($server)]);

        $id = $this->createContainer($server);

        $server->update([
            'container_id' => $id,
            'status' => 'stopped',
        ]);
    }

    /** pterodactyl-like: отправить команду в stdin java-процесса */
    public function sendCommand(Server $server, string $command): void
    {
        $server->refresh();

        if (($server->status ?? 'stopped') !== 'running') {
            throw new \RuntimeException('Server is not running.');
        }

        $id = $server->container_id ?: $this->findContainerIdByName($this->containerName($server));
        if (!$id) throw new \RuntimeException('Container not found.');

        $safe = str_replace(["\\", "\"", "\n", "\r"], ["\\\\", "\\\"", " ", " "], $command);

        $this->run([
            'docker', 'exec', '-i', $id,
            'sh', '-lc',
            "printf \"%s\\n\" \"$safe\" > /proc/1/fd/0"
        ]);
    }

    public function ensureRunning(Server $server): void
    {
        $server->refresh();
        if (($server->status ?? 'stopped') !== 'running') return;

        $this->prepareServer($server);

        $containerId = $this->ensureContainer($server);
        if ($this->isContainerRunning($containerId)) return;

        $this->run(['docker', 'start', $containerId]);
    }

    /* =========================
     * Preparation
     * ========================= */

    private function prepareServer(Server $server): void
    {
        $this->ensureDataPath($server);
        $this->ensureEula($server);
        $this->ensureJar($server);

        $this->ensureAllocationPort($server);
        $this->ensureHostPort($server);

        // ✅ САМОЕ ВАЖНОЕ: порт в server.properties
        $this->ensureServerPropertiesPort($server);

        if (!$server->startup_command) {
            $server->update([
                'startup_command' => 'java -Xms256M -Xmx{{RAM}}M -jar {{JAR}} nogui',
            ]);
            $server->refresh();
        }
    }

    private function ensureDataPath(Server $server): void
    {
        if (!$server->data_path) {
            $server->update(['data_path' => storage_path('app/servers/' . $server->uuid)]);
            $server->refresh();
        }

        File::ensureDirectoryExists($server->data_path);
    }

    private function ensureEula(Server $server): void
    {
        $eula = rtrim($server->data_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'eula.txt';
        if (!File::exists($eula)) File::put($eula, "eula=true\n");
    }

    private function ensureJar(Server $server): void
    {
        $jarName = $server->jar_file ?: 'server.jar';
        $jar = rtrim($server->data_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $jarName;

        if (!File::exists($jar)) {
            throw new \RuntimeException($jarName . ' not found in server root. Upload it to Files root.');
        }
    }

    private function ensureAllocationPort(Server $server): void
    {
        if ((int)($server->allocation_port ?? 0) <= 0) {
            // внутренняя “игровая” портовка контейнера
            $server->update(['allocation_port' => 25565]);
            $server->refresh();
        }
    }

    private function ensureHostPort(Server $server): void
    {
        $current = (int)($server->host_port ?? 0);
        if ($current > 0 && $this->isHostPortFree($current)) return;

        $port = 55000 + (int)$server->id;
        while (!$this->isHostPortFree($port)) {
            $port++;
            if ($port > 65000) throw new \RuntimeException('No free host port found.');
        }

        $server->update(['host_port' => $port]);
        $server->refresh();
    }

    private function ensureServerPropertiesPort(Server $server): void
    {
        $file = rtrim($server->data_path, '/').'/server.properties';
        if (!File::exists($file)) return;

        $port = (int)($server->allocation_port ?? 25565);

        $lines = File::lines($file)->toArray(); // contains "\n"
        $out = [];
        $found = false;

        foreach ($lines as $line) {
            $raw = rtrim($line, "\r\n");

            if (str_starts_with($raw, 'server-port=')) {
                $out[] = "server-port={$port}";
                $found = true;
                continue;
            }

            $out[] = $raw;
        }

        if (!$found) {
            $out[] = "server-port={$port}";
        }

        File::put($file, implode("\n", $out) . "\n");
    }

    private function isHostPortFree(int $port): bool
    {
        $p = new Process(['bash', '-lc', "lsof -iTCP:$port -sTCP:LISTEN >/dev/null 2>&1; echo $?"]);
        $p->run();
        return ((int)trim($p->getOutput())) !== 0;
    }

    /* =========================
     * Container lifecycle
     * ========================= */

    private function ensureContainer(Server $server): string
    {
        if ($server->container_id && $this->containerExists($server->container_id)) {
            return $server->container_id;
        }

        $idByName = $this->findContainerIdByName($this->containerName($server));
        if ($idByName) {
            $server->update(['container_id' => $idByName]);
            return $idByName;
        }

        $id = $this->createContainer($server);
        $server->update(['container_id' => $id]);

        return $id;
    }

    private function createContainer(Server $server): string
    {
        $server->refresh();

        $hostPort = (int)($server->host_port ?? 0);
        if ($hostPort <= 0) throw new \RuntimeException('Host port is not set (host_port <= 0).');

        $internalPort = (int)($server->allocation_port ?? 25565);

        $limits = $server->limits ?? [];
        $ramMb = (int)($limits['ram'] ?? 1024);
        if ($ramMb < 256) $ramMb = 256;

        $cpu = $limits['cpu'] ?? null;
        $cpusArg = [];
        if ($cpu !== null) {
            $cpus = max(0.1, ((float)$cpu) / 100.0);
            $cpusArg = ['--cpus', (string)$cpus];
        }

        $image = $this->javaImage($server->java_version ?: '21');

        // ✅ логика старта с переменными
        $startup = $this->renderStartup($server);

        $cmd = array_merge(
            [
                'docker', 'run', '-d',
                '-i', // keep stdin open for sendCommand
                '--name', $this->containerName($server),
                '-p', $hostPort . ':' . $internalPort,
                '-v', $server->data_path . ':/data',
                '-w', '/data',
                '--restart', 'unless-stopped',
            ],
            $cpusArg,
            [
                $image,
                'sh', '-lc',
                $startup,
            ]
        );

        $out = $this->run($cmd);
        $id = trim($out);
        if ($id === '') throw new \RuntimeException('Docker did not return container id.');

        return $id;
    }

    private function javaImage(string $version): string
    {
        $v = trim($version);
        if (!in_array($v, ['17', '21'], true)) $v = '21';
        return "eclipse-temurin:{$v}-jre";
    }

    private function renderStartup(Server $server): string
    {
        $limits = $server->limits ?? [];
        $ramMb = (int)($limits['ram'] ?? 1024);
        if ($ramMb < 256) $ramMb = 256;

        $vars = [
            '{{SERVER_NAME}}' => $server->name,
            '{{UUID}}'        => $server->uuid,
            '{{RAM}}'         => (string)$ramMb,
            '{{JAR}}'         => $server->jar_file ?: 'server.jar',
            '{{PORT}}'        => (string)($server->allocation_port ?? 25565),
        ];

        $tpl = $server->startup_command ?: 'java -Xms256M -Xmx{{RAM}}M -jar {{JAR}} nogui';
        return strtr($tpl, $vars);
    }

    private function containerName(Server $server): string
    {
        return 'bearpanel-' . $server->uuid;
    }

    private function containerExists(string $idOrName): bool
    {
        $p = new Process(['docker', 'inspect', $idOrName]);
        $p->run();
        return $p->isSuccessful();
    }

    private function isContainerRunning(string $idOrName): bool
    {
        $p = new Process(['bash', '-lc', "docker inspect -f '{{.State.Running}}' " . escapeshellarg($idOrName) . " 2>/dev/null || echo false"]);
        $p->run();
        return trim($p->getOutput()) === 'true';
    }

    private function findContainerIdByName(string $name): ?string
    {
        $p = new Process(['bash', '-lc', "docker ps -a --filter name=^/${name}$ --format '{{.ID}}'"]);
        $p->run();
        if (!$p->isSuccessful()) return null;
        $id = trim($p->getOutput());
        return $id !== '' ? $id : null;
    }

    private function containerHasHostPort(string $id, int $hostPort, int $containerPort): bool
    {
        if ($hostPort <= 0) return false;

        $p = new Process([
            'bash', '-lc',
            "docker inspect -f '{{json .NetworkSettings.Ports}}' " . escapeshellarg($id) . " 2>/dev/null"
        ]);
        $p->run();
        if (!$p->isSuccessful()) return false;

        $json = trim($p->getOutput());
        if ($json === '' || $json === 'null') return false;

        $ports = json_decode($json, true);
        if (!is_array($ports)) return false;

        $key = $containerPort . '/tcp';
        if (!isset($ports[$key]) || !is_array($ports[$key])) return false;

        foreach ($ports[$key] as $bind) {
            if (($bind['HostPort'] ?? null) == (string)$hostPort) return true;
        }
        return false;
    }

    /* =========================
     * Runner
     * ========================= */

    private function run(array $cmd): string
    {
        $p = new Process($cmd);
        $p->setTimeout(180);
        $p->run();

        if (!$p->isSuccessful()) {
            throw new \RuntimeException("Command failed: " . implode(' ', $cmd) . " " . $p->getErrorOutput());
        }

        return $p->getOutput();
    }

    private function runQuiet(array $cmd): void
    {
        $p = new Process($cmd);
        $p->setTimeout(60);
        $p->run();
    }
}