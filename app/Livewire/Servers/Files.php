<?php

namespace App\Livewire\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Files extends Component
{
    use WithFileUploads;

    public Server $server;

    #[Url(except: '')]
    public string $path = '';

    /** @var array<int, TemporaryUploadedFile> */
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
    public string $createType = 'file';
    public string $createName = '';
    public string $createError = '';

    // upload
    public bool $uploading = false;
    public int $uploadProgress = 0;
    public string $uploadStatus = '';
    public array $uploadConflicts = [];
    public array $uploadErrors = [];
    public ?string $uploadError = null;

    private int $maxEditSize = 512 * 1024;
    private int $maxUploadSize = 100 * 1024 * 1024; // 100MB

    public function mount(Server $server): void
    {
        if (!$server->canAccess(auth()->user())) {
            abort(403, __('Access denied'));
        }

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
        $this->resetUploadState();
        $this->loadItems();
    }

    public function updatedUploads(): void
    {
        if (empty($this->uploads)) {
            return;
        }

        // Проверка размера на серверной стороне
        foreach ($this->uploads as $file) {
            $size = $file->getSize();
            
            if ($size === 0) {
                $this->uploadError = __('File is empty');
                $this->uploads = [];
                return;
            }
            
            if ($size > $this->maxUploadSize) {
                $maxSizeMB = number_format($this->maxUploadSize / 1024 / 1024, 1);
                $this->uploadError = __('File size exceeds :size MB limit', ['size' => $maxSizeMB]);
                $this->uploads = [];
                return;
            }
        }
        
        $this->uploadFiles();
    }

    public function loadItems(): void
    {
        $dir = $this->absPath($this->path);

        if (!File::exists($dir)) {
            File::ensureDirectoryExists($dir);
        }

        $this->breadcrumbs = $this->makeBreadcrumbs($this->path);
        $list = [];

        foreach (File::directories($dir) as $d) {
            $name = basename($d);
            $rel = ltrim(trim($this->path . '/' . $name, '/'), '/');
            $list[] = [
                'type' => 'dir',
                'name' => $name,
                'rel' => $rel,
                'size' => null,
                'mtime' => File::lastModified($d),
            ];
        }

        foreach (File::files($dir) as $f) {
            $name = $f->getFilename();
            $rel = ltrim(trim($this->path . '/' . $name, '/'), '/');
            $list[] = [
                'type' => 'file',
                'name' => $name,
                'rel' => $rel,
                'size' => $f->getSize(),
                'mtime' => $f->getMTime(),
            ];
        }

        usort($list, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }
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
            $this->uploadError = __('File is too large to edit (max :size MB)', [
                'size' => number_format($this->maxEditSize / 1024 / 1024, 1)
            ]);
            return;
        }

        $this->editor = File::get($abs);
        $this->isEditing = true;
        $this->uploadError = null;
    }

    public function saveFile(): void
    {
        if (!$this->selected) return;

        $abs = $this->absPath($this->selected);
        if (!File::exists($abs) || File::isDirectory($abs)) return;

        try {
            File::put($abs, $this->editor);
            $this->uploadError = null;
            session()->flash('status', __('Saved.'));
            $this->loadItems();
        } catch (\Exception $e) {
            $this->uploadError = __('Failed to save file: :error', ['error' => $e->getMessage()]);
        }
    }

    public function delete(string $rel): void
    {
        $rel = trim($rel, '/');
        $abs = $this->absPath($rel);

        if (!File::exists($abs)) return;

        try {
            if (File::isDirectory($abs)) {
                File::deleteDirectory($abs);
            } else {
                File::delete($abs);
            }

            if ($this->selected === $rel) {
                $this->selected = null;
                $this->editor = '';
                $this->isEditing = false;
            }

            if ($this->renameRel === $rel) {
                $this->closeRename();
            }

            $this->uploadError = null;
            session()->flash('status', __('Deleted.'));
            $this->loadItems();
        } catch (\Exception $e) {
            $this->uploadError = __('Failed to delete: :error', ['error' => $e->getMessage()]);
        }
    }

    public function uploadFiles(): void
    {
        if (empty($this->uploads)) {
            return;
        }

        $this->uploading = true;
        $this->uploadProgress = 0;
        $this->uploadErrors = [];
        $this->uploadConflicts = [];
        $this->uploadStatus = __('Validating files...');
        $this->uploadError = null;

        $targetDir = $this->absPath($this->path);
        File::ensureDirectoryExists($targetDir);

        $totalFiles = count($this->uploads);
        $processed = 0;
        $conflicts = [];

        // Check for conflicts first
        foreach ($this->uploads as $index => $file) {
            $originalName = $file->getClientOriginalName();
            $cleanName = $this->sanitizeUploadName($originalName);
            $destPath = $targetDir . DIRECTORY_SEPARATOR . $cleanName;
            
            if (File::exists($destPath)) {
                $conflicts[] = [
                    'index' => $index,
                    'original' => $originalName,
                    'clean' => $cleanName,
                ];
            }
        }

        if (!empty($conflicts)) {
            $this->uploadConflicts = $conflicts;
            $this->uploading = false;
            $this->uploadStatus = '';
            $this->uploadError = __('Some files already exist');
            return;
        }

        $this->uploadStatus = __('Uploading files...');

        foreach ($this->uploads as $index => $file) {
            try {
                $this->uploadProgress = (int)(($processed / $totalFiles) * 100);
                
                $cleanName = $this->sanitizeUploadName($file->getClientOriginalName());
                $destPath = $targetDir . DIRECTORY_SEPARATOR . $cleanName;
                
                File::copy($file->getRealPath(), $destPath);
                chmod($destPath, 0644);
                
                $processed++;
                $this->uploadProgress = (int)(($processed / $totalFiles) * 100);
                $this->uploadStatus = __('Uploading :current of :total', [
                    'current' => $processed,
                    'total' => $totalFiles
                ]);
            } catch (\Exception $e) {
                $this->uploadErrors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->uploads = [];
        
        if (empty($this->uploadErrors)) {
            $this->uploadStatus = '';
            $this->uploading = false;
            $this->uploadError = null;
            session()->flash('status', __('Uploaded. (:count)', ['count' => $processed]));
            $this->loadItems();
        } else {
            $this->uploadStatus = '';
            $this->uploadError = __('Upload completed with :count error(s)', ['count' => count($this->uploadErrors)]);
        }
        
        $this->uploadProgress = 0;
    }

    public function overwriteConflict(int $index): void
    {
        if (!isset($this->uploadConflicts[$index])) {
            return;
        }

        $conflict = $this->uploadConflicts[$index];
        $file = $this->uploads[$conflict['index']] ?? null;
        
        if (!$file) {
            unset($this->uploadConflicts[$index]);
            $this->uploadConflicts = array_values($this->uploadConflicts);
            return;
        }

        $targetDir = $this->absPath($this->path);
        $destPath = $targetDir . DIRECTORY_SEPARATOR . $conflict['clean'];
        
        if (File::exists($destPath)) {
            File::delete($destPath);
        }
        
        File::copy($file->getRealPath(), $destPath);
        chmod($destPath, 0644);
        
        unset($this->uploadConflicts[$index]);
        $this->uploadConflicts = array_values($this->uploadConflicts);
        
        if (empty($this->uploadConflicts)) {
            $this->uploads = [];
            $this->uploadError = null;
            session()->flash('status', __('Files uploaded successfully'));
            $this->loadItems();
        }
    }

    public function renameAndUploadConflict(int $index, string $newName): void
    {
        if (!isset($this->uploadConflicts[$index])) {
            return;
        }

        $conflict = $this->uploadConflicts[$index];
        $file = $this->uploads[$conflict['index']] ?? null;
        
        if (!$file) {
            unset($this->uploadConflicts[$index]);
            $this->uploadConflicts = array_values($this->uploadConflicts);
            return;
        }

        $newName = $this->sanitizeUploadName($newName);
        if (empty($newName)) {
            $this->uploadError = __('Invalid name');
            return;
        }

        $targetDir = $this->absPath($this->path);
        $destPath = $targetDir . DIRECTORY_SEPARATOR . $newName;
        
        File::copy($file->getRealPath(), $destPath);
        chmod($destPath, 0644);
        
        unset($this->uploadConflicts[$index]);
        $this->uploadConflicts = array_values($this->uploadConflicts);
        
        if (empty($this->uploadConflicts)) {
            $this->uploads = [];
            $this->uploadError = null;
            session()->flash('status', __('Files uploaded successfully'));
            $this->loadItems();
        }
    }

    public function skipConflict(int $index): void
    {
        if (isset($this->uploadConflicts[$index])) {
            unset($this->uploadConflicts[$index]);
            $this->uploadConflicts = array_values($this->uploadConflicts);
            
            if (empty($this->uploadConflicts)) {
                $this->uploads = [];
                $this->uploadError = null;
                $this->loadItems();
            }
        }
    }

    private function sanitizeUploadName(string $name): string
    {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $basename = pathinfo($name, PATHINFO_FILENAME);
        
        $basename = str_replace(["\0", "\r", "\n", '..', './', '.\\'], '', $basename);
        $basename = trim($basename);
        $basename = str_replace(' ', '_', $basename);
        $basename = preg_replace('/[^\w\-]/u', '', $basename);
        
        if (empty($basename)) {
            $basename = 'unnamed_' . time();
        }
        
        $cleanName = $basename . ($ext ? '.' . $ext : '');
        
        $counter = 1;
        $originalName = $cleanName;
        $targetDir = $this->absPath($this->path);
        
        while (File::exists($targetDir . DIRECTORY_SEPARATOR . $cleanName) && $counter < 100) {
            $cleanName = $basename . '_' . $counter . ($ext ? '.' . $ext : '');
            $counter++;
        }
        
        return $cleanName;
    }

    private function resetUploadState(): void
    {
        $this->uploading = false;
        $this->uploadProgress = 0;
        $this->uploadStatus = '';
        $this->uploadConflicts = [];
        $this->uploadErrors = [];
        $this->uploadError = null;
        $this->uploads = [];
    }

    public function download(string $rel): BinaryFileResponse
    {
        $rel = trim($rel, '/');
        $abs = $this->absPath($rel);

        if (!File::exists($abs) || File::isDirectory($abs)) {
            abort(404, __('File not found'));
        }

        return response()->download($abs, basename($abs));
    }

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
        if ($rel === '') {
            $this->closeRename();
            return;
        }

        $absFrom = $this->absPath($rel);
        if (!File::exists($absFrom)) {
            $this->renameError = __('Source not found');
            return;
        }

        $newName = $this->sanitizeName($this->renameTo, $this->renameError);
        if ($newName === null) return;

        $parentRel = trim(str_replace('\\', '/', dirname($rel)), '/');
        if ($parentRel === '.') $parentRel = '';

        $absParent = $this->absPath($parentRel);
        $absTo = rtrim($absParent, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;

        if (File::exists($absTo)) {
            $this->renameError = __('Target already exists');
            return;
        }

        if (!@rename($absFrom, $absTo)) {
            $this->renameError = __('Rename failed');
            return;
        }

        if ($this->selected === $rel) {
            $newRel = ltrim(trim(($parentRel ? $parentRel.'/' : '') . $newName, '/'), '/');
            $this->selected = $newRel;
        }

        $this->uploadError = null;
        session()->flash('status', __('Renamed.'));
        $this->closeRename();
        $this->loadItems();
    }

    public function beginCreate(string $type): void
    {
        $this->creating = true;
        $this->createType = in_array($type, ['file', 'folder'], true) ? $type : 'file';
        $this->createName = $this->createType === 'folder' ? __('New folder') : __('New file.txt');
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
            $this->createError = __('A file or folder with this name already exists');
            return;
        }

        if ($this->createType === 'folder') {
            File::ensureDirectoryExists($abs);
            $this->uploadError = null;
            session()->flash('status', __('Folder created.'));
            $this->closeCreate();
            $this->loadItems();
            return;
        }

        File::put($abs, '');
        $newRel = ltrim(trim(($this->path ? $this->path.'/' : '') . $name, '/'), '/');

        $this->uploadError = null;
        session()->flash('status', __('File created.'));
        $this->closeCreate();
        $this->loadItems();
        $this->selectFile($newRel);
    }

    private function sanitizeName(string $raw, string &$error): ?string
    {
        $name = trim($raw);
        $name = str_replace(["\0", "\r", "\n"], '', $name);

        if ($name === '' || $name === '.' || $name === '..') {
            $error = __('Invalid name');
            return null;
        }

        if (strlen($name) > 255) {
            $error = __('Name is too long (maximum 255 characters)');
            return null;
        }

        if (str_contains($name, '/') || str_contains($name, '\\')) {
            $error = __('Name must not contain slashes.');
            return null;
        }

        if (preg_match('/[<>:"|?*]/', $name)) {
            $error = __('Name contains invalid characters');
            return null;
        }

        return $name;
    }

    private function makeBreadcrumbs(string $path): array
    {
        $path = trim($path, '/');
        $crumbs = [['label' => __('Server'), 'path' => '']];

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

        if ($base === '') {
            $base = storage_path('app/servers/' . $this->server->uuid);
        }

        File::ensureDirectoryExists($base);
        return $base;
    }

    private function absPath(string $rel): string
    {
        $rel = trim($rel, '/');
        $parts = array_values(array_filter(explode('/', $rel), fn($p) => $p !== '' && $p !== '.'));
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

        if (strpos($normalized, $normalizedBase) !== 0) {
            abort(403, __('Access denied'));
        }

        return $candidate;
    }

    public function getFileSizeForHumans(int $bytes): string
    {
        $units = [__('B'), __('KB'), __('MB'), __('GB'), __('TB')];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function render()
    {
        return view('livewire.servers.files')
            ->layout('layouts.app');
    }
}