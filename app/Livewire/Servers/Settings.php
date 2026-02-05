<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Livewire\Component;

class Settings extends Component
{
    public Server $server;

    public string $jar_file = 'server.jar';
    public string $java_version = '21';
    public string $startup_command = 'java -Xms256M -Xmx{{RAM}}M -jar {{JAR}} nogui';

    protected function rules(): array
    {
        return [
            'jar_file' => ['required', 'string', 'max:255'],

            'java_version' => ['required', 'in:17,21'],

            'startup_command' => [
                'required',
                'string',
                'max:2000',
                function ($attr, $value, $fail) {
                    $v = (string) $value;

                    /*
                     |--------------------------------------------------------------------------
                     | JVM MEMORY RULES
                     |--------------------------------------------------------------------------
                     | -Xms  → разрешён в любом виде (числа / M / G)
                     | -Xmx  → ТОЛЬКО через {{RAM}}
                     */

                    // ❌ Запрещаем хардкод -Xmx (числа, G/M/K)
                    if (preg_match('/-Xmx\s*\d+\s*[kKmMgG]?/u', $v)) {
                        $fail('Do not hardcode -Xmx value. Use {{RAM}} variable.');
                        return;
                    }

                    // ❌ Запрещаем -Xmx без {{RAM}}
                    if (preg_match('/-Xmx(?!\s*\{\{RAM\}\})/u', $v)) {
                        $fail('-Xmx must use {{RAM}} variable.');
                        return;
                    }

                    /*
                     |--------------------------------------------------------------------------
                     | VARIABLE WHITELIST
                     |--------------------------------------------------------------------------
                     */

                    preg_match_all('/\{\{([A-Z_]+)\}\}/', $v, $m);

                    $allowed = [
                        'RAM',
                        'JAR',
                        'UUID',
                        'SERVER_NAME',
                    ];

                    foreach ($m[1] as $var) {
                        if (!in_array($var, $allowed, true)) {
                            $fail("Unknown variable {{$var}} is not allowed.");
                            return;
                        }
                    }
                },
            ],
        ];
    }

    public function mount(Server $server): void
    {
        abort_if(!$server->canAccess(auth()->user()), 403);

        $this->server = $server->load('node', 'owner');

        $this->jar_file = $server->jar_file ?: 'server.jar';
        $this->java_version = $server->java_version ?: '21';
        $this->startup_command = $server->startup_command
            ?: 'java -Xms256M -Xmx{{RAM}}M -jar {{JAR}} nogui';
    }

    public function refreshServer(): void
    {
        $this->server->refresh()->load('node', 'owner');
    }

    public function save(): void
    {
        $data = $this->validate();

        // ⚠️ ПОРТЫ И СИСТЕМНЫЕ ПОЛЯ ТУТ НЕ ТРОГАЕМ
        $this->server->update([
            'jar_file'        => $data['jar_file'],
            'java_version'    => $data['java_version'],
            'startup_command' => $data['startup_command'],
        ]);

        $this->refreshServer();

        session()->flash('status', 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.servers.settings')
            ->layout('layouts.app');
    }
}