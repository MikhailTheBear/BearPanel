<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Files extends Component
{
    use WithFileUploads;

    public Server $server;

    #[Url(except: '')]
    public string $path = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    /** @var array<int, array{type:string,name:string,rel:string,size:?int,mtime:int}> */
    public array $items = [];

    /** @var array<int, array{label:string,path:string}> */
    public array $breadcrumbs = [];

    public ?string $selected = null;
    public string $editor = '';
    public bool $isEditing = false;

    // rename modal
    public bool $renaming = false;
    public ?string $renameRel = null;
    public string $renameTo = '';
    public string $renameError = '';

    // create modal
    public bool $creating = false;
    public string $createType = 'file'; // file|folder
    public string $createName = '';
    public string $createError = '';

    private int $maxEditSize = 512 * 1024;

    public function mount(Server $server): void
    {
        if (!$server->canAccess(auth()->user())) abort(403);

        $this->server = $server->load('node', 'owner');
        $this->path = trim((string)$this->path, '/');

        $this->loadItems();
    }

    public function setPath(string $path): void
    {
        $this->path = trim($path, '/');
        $this->updatedPath();
    }

    public function updatedPath(): void
    {
        $this->path = trim($this->path, '/');

        $this->selected = null;
        $this->editor = '';
        $this->isEditing = false;

        $this->closeRename();
        $this->closeCreate();

        $this->loadItems();
    }

    public function updatedUploads(): void
    {
        // старая логика: авто-загрузка сразу после drop/select
        $this->uploadFiles();
    }

    public function loadItems(): void
    {
        $dir = $this->absPath($this->path);

        if (!File::exists($dir)) File::ensureDirectoryExists($dir);

        $this->breadcrumbs = $this->makeBreadcrumbs($this->path);

        $list = [];

        foreach (File::directories($dir) as $d) {
            $name = basename($d);
            $rel  = ltrim(trim($this->path . '/' . $name, '/'), '/');

            $list[] = [
                'type' => 'dir',
                'name' => $name,
                'rel'  => $rel,
                'size' => null,
                'mtime'=> File::lastModified($d),
            ];
        }

        foreach (File::files($dir) as $f) {
            $name = $f->getFilename();
            $rel  = ltrim(trim($this->path . '/' . $name, '/'), '/');

            $list[] = [
                'type' => 'file',
                'name' => $name,
                'rel'  => $rel,
                'size' => $f->getSize(),
                'mtime'=> $f->getMTime(),
            ];
        }

        usort($list, function ($a, $b) {
            if ($a['type'] !== $b['type']) return $a['type'] === 'dir' ? -1 : 1;
            return strcasecmp($a['name'], $b['name']);
        });

        $this->items = $list;
    }

    public function openDir(string $rel): void
    {
        $this->path = trim($rel, '/');
        $this->updatedPath();
    }

    public function goUp(): void
    {
        if ($this->path === '') return;

        $parts = explode('/', $this->path);
        array_pop($parts);

        $this->path = trim(implode('/', $parts), '/');
        $this->updatedPath();
    }

    public function selectFile(string $rel): void
    {
        $rel = trim($rel, '/');
        $abs = $this->absPath($rel);

        if (!File::exists($abs) || File::isDirectory($abs)) {
            $this->selected = null;
            $this->editor = '';
            $this->isEditing = false;
            return;
        }

        $this->selected = $rel;

        $size = File::size($abs);
        if ($size > $this->maxEditSize) {
            $this->editor = '';
            $this->isEditing = false;
            return;
        }

        $this->editor = File::get($abs);
        $this->isEditing = true;
    }

    public function saveFile(): void
    {
        if (!$this->selected) return;

        $abs = $this->absPath($this->selected);
        if (!File::exists($abs) || File::isDirectory($abs)) return;

        File::put($abs, $this->editor);

        session()->flash('status', 'Saved.');
        $this->loadItems();
    }

    public function delete(string $rel): void
    {
        $rel = trim($rel, '/');
        $abs = $this->absPath($rel);

        if (!File::exists($abs)) return;

        if (File::isDirectory($abs)) File::deleteDirectory($abs);
        else File::delete($abs);

        if ($this->selected === $rel) {
            $this->selected = null;
            $this->editor = '';
            $this->isEditing = false;
        }

        if ($this->renameRel === $rel) $this->closeRename();

        session()->flash('status', 'Deleted.');
        $this->loadItems(); // важно: НЕ меняем path, не "кидает" наверх
    }

    public function uploadFiles(): void
    {
        if (!$this->uploads || count($this->uploads) === 0) return;

        $this->validate(
            ['uploads.*' => ['file', 'max:102400']], // 100MB
            ['uploads.*.max' => 'File is too large (max 100MB).']
        );

        $targetDir = $this->absPath($this->path);
        File::ensureDirectoryExists($targetDir);

        foreach ($this->uploads as $file) {
            $name = $file->getClientOriginalName();
            $name = str_replace(["\0", "\r", "\n"], '', $name);
            $name = trim($name);
            if ($name === '') continue;

            $dest = $targetDir . DIRECTORY_SEPARATOR . $name;
            File::copy($file->getRealPath(), $dest);
        }

        $this->uploads = [];
        session()->flash('status', 'Uploaded.');
        $this->loadItems();
    }

    /* =========================
     * Download
     * ========================= */

    public function download(string $rel): BinaryFileResponse
    {
        $rel = trim($rel, '/');
        $abs = $this->absPath($rel);

        if (!File::exists($abs) || File::isDirectory($abs)) abort(404);

        return response()->download($abs, basename($abs));
    }

    /* =========================
     * Rename
     * ========================= */

    public function beginRename(string $rel): void
    {
        $rel = trim($rel, '/');
        $abs = $this->absPath($rel);

        if (!File::exists($abs)) return;

        $this->renaming = true;
        $this->renameRel = $rel;
        $this->renameTo = basename($abs);
        $this->renameError = '';
    }

    public function closeRename(): void
    {
        $this->renaming = false;
        $this->renameRel = null;
        $this->renameTo = '';
        $this->renameError = '';
    }

    public function confirmRename(): void
    {
        $this->renameError = '';

        $rel = trim((string)$this->renameRel, '/');
        if ($rel === '') { $this->closeRename(); return; }

        $absFrom = $this->absPath($rel);
        if (!File::exists($absFrom)) {
            $this->renameError = 'Source not found.';
            return;
        }

        $newName = $this->sanitizeName($this->renameTo, $this->renameError);
        if ($newName === null) return;

        $parentRel = trim(str_replace('\\', '/', dirname($rel)), '/');
        if ($parentRel === '.') $parentRel = '';

        $absParent = $this->absPath($parentRel);
        $absTo = rtrim($absParent, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;

        if (File::exists($absTo)) {
            $this->renameError = 'Target already exists.';
            return;
        }

        if (!@rename($absFrom, $absTo)) {
            $this->renameError = 'Rename failed.';
            return;
        }

        if ($this->selected === $rel) {
            $newRel = ltrim(trim(($parentRel ? $parentRel.'/' : '') . $newName, '/'), '/');
            $this->selected = $newRel;
        }

        session()->flash('status', 'Renamed.');
        $this->closeRename();
        $this->loadItems();
    }

    /* =========================
     * Create New
     * ========================= */

    public function beginCreate(string $type): void
    {
        $this->creating = true;
        $this->createType = in_array($type, ['file', 'folder'], true) ? $type : 'file';
        $this->createName = $this->createType === 'folder' ? 'new-folder' : 'new-file.txt';
        $this->createError = '';
    }

    public function closeCreate(): void
    {
        $this->creating = false;
        $this->createType = 'file';
        $this->createName = '';
        $this->createError = '';
    }

    public function confirmCreate(): void
    {
        $this->createError = '';

        $name = $this->sanitizeName($this->createName, $this->createError);
        if ($name === null) return;

        $dirAbs = $this->absPath($this->path);
        File::ensureDirectoryExists($dirAbs);

        $abs = rtrim($dirAbs, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        if (File::exists($abs)) {
            $this->createError = 'Already exists.';
            return;
        }

        if ($this->createType === 'folder') {
            File::ensureDirectoryExists($abs);
            session()->flash('status', 'Folder created.');
            $this->closeCreate();
            $this->loadItems();
            return;
        }

        // file
        File::put($abs, '');

        $newRel = ltrim(trim(($this->path ? $this->path.'/' : '') . $name, '/'), '/');

        session()->flash('status', 'File created.');
        $this->closeCreate();
        $this->loadItems();

        // открыть в редакторе
        $this->selectFile($newRel);
    }

    private function sanitizeName(string $raw, string &$error): ?string
    {
        $name = trim($raw);
        $name = str_replace(["\0", "\r", "\n"], '', $name);

        if ($name === '' || $name === '.' || $name === '..') {
            $error = 'Invalid name.';
            return null;
        }

        if (str_contains($name, '/') || str_contains($name, '\\')) {
            $error = 'Name must not contain slashes.';
            return null;
        }

        if (preg_match('/[:]/', $name)) {
            $error = 'Invalid character: ":"';
            return null;
        }

        return $name;
    }

    /* =========================
     * Helpers
     * ========================= */

    private function makeBreadcrumbs(string $path): array
    {
        $path = trim($path, '/');
        $crumbs = [['label' => 'root', 'path' => '']];

        if ($path === '') return $crumbs;

        $parts = array_values(array_filter(explode('/', $path), fn($p) => $p !== ''));
        $acc = '';
        foreach ($parts as $p) {
            $acc = trim($acc . '/' . $p, '/');
            $crumbs[] = ['label' => $p, 'path' => $acc];
        }

        return $crumbs;
    }

    private function baseDir(): string
    {
        $base = rtrim((string)$this->server->data_path, DIRECTORY_SEPARATOR);

        if ($base === '') $base = storage_path('app/servers/' . $this->server->uuid);

        File::ensureDirectoryExists($base);
        return $base;
    }

    private function absPath(string $rel): string
    {
        $rel = trim($rel, '/');

        $parts = array_values(array_filter(explode('/', $rel), fn ($p) => $p !== '' && $p !== '.'));
        $safeParts = [];
        foreach ($parts as $p) {
            if ($p === '..') continue;
            $safeParts[] = $p;
        }

        $candidate = $this->baseDir() . (count($safeParts)
            ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $safeParts)
            : ''
        );

        $base = $this->baseDir();
        $realBase = realpath($base) ?: $base;

        $normalized = str_replace('\\', '/', $candidate);
        $normalizedBase = str_replace('\\', '/', $realBase);

        if (strpos($normalized, $normalizedBase) !== 0) abort(403);

        return $candidate;
    }

    public function render()
    {
        return view('livewire.servers.files')
            ->layout('layouts.app');
    }
}