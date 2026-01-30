<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    protected $fillable = [
        'name',
        'fqdn',
        'scheme',
        'daemon_port',
        'token',
        'is_public',
        'is_active',
    ];
}
