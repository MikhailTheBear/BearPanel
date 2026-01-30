<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Server extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'owner_id',
        'node_id',
        'status',
        'limits',
        'container_id',
        'allocation_port',
        'host_port',
        'data_path',
        'jar_file',
        'java_version',
        'startup_command',
    ];

    protected $casts = [
        'limits' => 'array',
        'allocation_port' => 'integer',
        'host_port' => 'integer',
    ];

    // ✅ чтобы /servers/{server} искал по uuid, а не по id
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    // ✅ админ имеет доступ везде
    public function canAccess(?User $user): bool
    {
        if (!$user) return false;
        if (!empty($user->is_admin)) return true;
        return (int)$this->owner_id === (int)$user->id;
    }

    /**
     * Что показывать пользователю в "Connect".
     * dev/local: localhost
     * prod: fqdn ноды (если есть) иначе текущий домен
     */
    public function connectHost(): string
    {
        // local/dev — всегда localhost
        if (app()->isLocal()) return '127.0.0.1';

        // если загружена нода/есть нода — fqdn
        $fqdn = $this->node?->fqdn;
        if (!$fqdn) {
            try {
                $fqdn = $this->node()->value('fqdn');
            } catch (\Throwable $e) {
                $fqdn = null;
            }
        }
        if ($fqdn) return $fqdn;

        // fallback: текущий домен
        try {
            return request()->getHost();
        } catch (\Throwable $e) {
            return '127.0.0.1';
        }
    }

    public function connectPort(): ?int
    {
        $p = (int)($this->host_port ?? 0);
        return $p > 0 ? $p : null;
    }

    public function connectAddress(): string
    {
        $host = $this->connectHost();
        $port = $this->connectPort();

        return $port ? "{$host}:{$port}" : $host;
    }
}