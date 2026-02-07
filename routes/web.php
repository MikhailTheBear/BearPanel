<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Servers\Index as UserServersIndex;
use App\Livewire\Servers\Show as ServerShow;
use App\Livewire\Servers\Console as ServerConsole;
use App\Livewire\Servers\Files as ServerFiles;
use App\Livewire\Servers\Settings as ServerSettings;

use App\Livewire\Admin\Overview as AdminOverview;
use App\Livewire\Admin\Nodes\Index as AdminNodesIndex;
use App\Livewire\Admin\Servers\Index as AdminServersIndex;
use App\Livewire\Admin\Servers\Show as AdminServerShow;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // User
    Route::get('/servers', UserServersIndex::class)->name('servers.index');
    Route::get('/servers/{server}', ServerShow::class)->name('servers.show');
    Route::get('/servers/{server}/console', ServerConsole::class)->name('servers.console');
    Route::get('/servers/{server}/files', ServerFiles::class)->name('servers.files');
    Route::get('/servers/{server}/settings', ServerSettings::class)->name('servers.settings');

    // Admin
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/', AdminOverview::class)->name('admin.overview');
        Route::get('/nodes', AdminNodesIndex::class)->name('admin.nodes.index');
        Route::get('/servers', AdminServersIndex::class)->name('admin.servers.index');
        Route::get('/servers/{server}', AdminServerShow::class)->name('admin.servers.show');
    });
});