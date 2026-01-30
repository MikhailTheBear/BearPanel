<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <div class="text-sm text-gray-500">Server</div>
            <h1 class="text-2xl font-semibold">{{ $server->name }} — Files</h1>

            <div class="mt-2 text-sm text-gray-600 flex flex-wrap items-center gap-1">
                <span class="text-gray-500">Path:</span>

                @foreach($breadcrumbs as $i => $bc)
                    @if($i > 0)
                        <span class="text-gray-400">/</span>
                    @endif

                    @if($i === count($breadcrumbs) - 1)
                        <span class="font-mono">{{ $bc['label'] }}</span>
                    @else
                        <button type="button"
                                wire:click="setPath(@js($bc['path']))"
                                class="font-mono underline text-gray-900">
                            {{ $bc['label'] }}
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="text-right flex items-center gap-2">
            <a class="text-sm underline" href="{{ route('servers.show', $server) }}">Back</a>
        </div>
    </div>

    @include('servers._tabs', ['server' => $server])

    @if (session('status'))
        <div class="mb-4 p-3 rounded bg-green-50 border border-green-200 text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @error('uploads.*')
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-800">
            {{ $message }}
        </div>
    @enderror

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT -->
        <div class="lg:col-span-1 bg-white shadow rounded-lg p-4"
             x-data="{ isDropping:false, p:0 }"
             x-on:livewire-upload-start="p=0"
             x-on:livewire-upload-progress="p=$event.detail.progress"
             x-on:livewire-upload-finish="p=100"
             x-on:livewire-upload-error="p=0"
        >
            <div class="flex items-center justify-between mb-3">
                <div class="font-semibold">Browser</div>

                <div class="flex gap-2">
                    <button type="button" wire:click="goUp" class="text-sm px-2 py-1 rounded border">Up</button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 mb-4">
                <button type="button"
                        wire:click="beginCreate('folder')"
                        class="text-sm px-2 py-1 rounded border">
                    New folder
                </button>
                <button type="button"
                        wire:click="beginCreate('file')"
                        class="text-sm px-2 py-1 rounded border">
                    New file
                </button>
            </div>

            <!-- Upload + drag&drop FIX -->
            <div class="border rounded p-3 mb-4"
                 :class="isDropping ? 'ring-2 ring-gray-900' : ''"
                 x-on:dragenter.prevent="isDropping=true"
                 x-on:dragover.prevent="isDropping=true"
                 x-on:dragleave.prevent="isDropping=false"
                 x-on:drop.prevent="
                    isDropping=false;
                    if ($event.dataTransfer && $event.dataTransfer.files && $event.dataTransfer.files.length) {
                        $refs.uploader.files = $event.dataTransfer.files;
                        $refs.uploader.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                 "
            >
                <div class="text-sm font-medium mb-2">Upload (drag & drop)</div>

                <input
                    x-ref="uploader"
                    type="file"
                    multiple
                    wire:model="uploads"
                    class="block w-full text-sm"
                />

                <div class="mt-2">
                    <div class="h-2 bg-gray-100 rounded overflow-hidden">
                        <div class="h-2 bg-gray-900" :style="`width:${p}%`"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1" x-text="p ? `Uploading: ${p}%` : ''"></div>
                </div>

                <div class="text-xs text-gray-500 mt-2">
                    Upload starts automatically after drop/select.
                </div>
            </div>

            <div class="divide-y">
                @forelse($items as $it)
                    <div class="py-2 flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            @if($it['type'] === 'dir')
                                <button type="button"
                                        wire:click="openDir(@js($it['rel']))"
                                        class="text-left w-full text-gray-900">
                                    📁 <span class="truncate">{{ $it['name'] }}</span>
                                </button>
                            @else
                                <button type="button"
                                        wire:click="selectFile(@js($it['rel']))"
                                        class="text-left w-full {{ $selected === $it['rel'] ? 'font-semibold' : '' }}">
                                    📄 <span class="truncate">{{ $it['name'] }}</span>
                                </button>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if($it['type'] === 'file')
                                <button type="button"
                                        wire:click="download(@js($it['rel']))"
                                        class="text-sm text-gray-700 underline">
                                    Download
                                </button>
                            @endif

                            <button type="button"
                                    wire:click="beginRename(@js($it['rel']))"
                                    class="text-sm text-gray-700 underline">
                                Rename
                            </button>

                            <button type="button"
                                    wire:click="delete(@js($it['rel']))"
                                    onclick="return confirm('Delete?')"
                                    class="text-sm text-red-600">
                                Delete
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-sm text-gray-500">Empty directory.</div>
                @endforelse
            </div>
        </div>

        <!-- RIGHT -->
        <div class="lg:col-span-2 bg-white shadow rounded-lg p-4">
            <div class="flex items-center justify-between mb-3">
                <div class="font-semibold">Editor</div>
                @if($selected)
                    <div class="text-xs text-gray-500 font-mono truncate">{{ $selected }}</div>
                @endif
            </div>

            @if(!$selected)
                <div class="text-sm text-gray-500">Select a file to view/edit.</div>
            @else
                @if(!$isEditing)
                    <div class="text-sm text-gray-500">
                        This file can’t be edited here (maybe too large). Edit locally.
                    </div>
                @else
                    <textarea wire:model.defer="editor"
                              class="w-full h-[520px] rounded border-gray-300 font-mono text-sm"></textarea>

                    <div class="mt-3 flex gap-2">
                        <button type="button" wire:click="saveFile" class="px-3 py-2 rounded bg-gray-900 text-white">
                            Save
                        </button>
                        <button type="button"
                                wire:click="$set('selected', null); $set('editor',''); $set('isEditing', false)"
                                class="px-3 py-2 rounded border">
                            Close
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Rename modal -->
    <div x-data x-cloak x-show="$wire.renaming" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" x-on:click="$wire.closeRename()"></div>

        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="font-semibold">Rename</div>
                <button type="button" class="text-gray-500" x-on:click="$wire.closeRename()">✕</button>
            </div>

            <div class="text-xs text-gray-500 font-mono mb-2">
                {{ $renameRel ?? '' }}
            </div>

            <input type="text"
                   class="w-full rounded border-gray-300"
                   wire:model.defer="renameTo"
                   placeholder="New name" />

            @if(!empty($renameError))
                <div class="mt-2 text-sm text-red-600">{{ $renameError }}</div>
            @endif

            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" class="px-3 py-2 rounded border" x-on:click="$wire.closeRename()">Cancel</button>
                <button type="button" class="px-3 py-2 rounded bg-gray-900 text-white" wire:click="confirmRename">Rename</button>
            </div>
        </div>
    </div>

    <!-- Create modal -->
    <div x-data x-cloak x-show="$wire.creating" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" x-on:click="$wire.closeCreate()"></div>

        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="font-semibold">
                    New {{ $createType === 'folder' ? 'folder' : 'file' }}
                </div>
                <button type="button" class="text-gray-500" x-on:click="$wire.closeCreate()">✕</button>
            </div>

            <input type="text"
                   class="w-full rounded border-gray-300"
                   wire:model.defer="createName"
                   placeholder="Name" />

            @if(!empty($createError))
                <div class="mt-2 text-sm text-red-600">{{ $createError }}</div>
            @endif

            <div class="mt-4 flex gap-2 justify-end">
                <button type="button" class="px-3 py-2 rounded border" x-on:click="$wire.closeCreate()">Cancel</button>
                <button type="button" class="px-3 py-2 rounded bg-gray-900 text-white" wire:click="confirmCreate">Create</button>
            </div>
        </div>
    </div>
</div>