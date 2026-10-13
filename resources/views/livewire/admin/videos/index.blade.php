<?php

use App\Enums\VideoStatus;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Video;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    // --- Filtros ---
    public string $search = '';

    public string $categoryFilter = '';

    public string $levelFilter = '';

    public string $statusFilter = '';

    // --- Formulario ---
    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $category_id = '';

    public string $min_plan_level = '1';

    public string $status = 'draft';

    public string $tags = '';

    public string $bunny_video_id = '';

    public ?string $feedback = null;

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'min_plan_level' => ['required', 'integer', 'min:1', 'max:3'],
            'status' => ['required', 'in:draft,processing,published'],
            'tags' => ['nullable', 'string', 'max:255'],
            'bunny_video_id' => ['nullable', 'string', 'max:255'],
        ]);

        $video = $this->editingId ? Video::findOrFail($this->editingId) : new Video;

        $video->fill([
            'title' => $data['title'],
            'slug' => $this->uniqueSlug($data['title'], $video->id),
            'description' => $data['description'] ?: null,
            'category_id' => $data['category_id'] ?: null,
            'min_plan_level' => (int) $data['min_plan_level'],
            'status' => $data['status'],
            // Si no se indica uno, se genera un ID simulado (modo demo hasta
            // conectar Bunny Stream).
            'bunny_video_id' => $data['bunny_video_id'] ?: ($video->bunny_video_id ?: 'demo-'.Str::uuid()),
            'published_at' => $data['status'] === VideoStatus::Published->value
                ? ($video->published_at ?? now())
                : null,
        ]);

        $video->save();

        $this->syncTags($video, (string) ($data['tags'] ?? ''));

        $this->feedback = $this->editingId ? 'Video actualizado.' : 'Video creado.';
        $this->cancel();
    }

    public function edit(int $id): void
    {
        $video = Video::with('tags')->findOrFail($id);

        $this->editingId = $video->id;
        $this->title = $video->title;
        $this->description = (string) $video->description;
        $this->category_id = (string) ($video->category_id ?? '');
        $this->min_plan_level = (string) $video->min_plan_level;
        $this->status = $video->status->value;
        $this->tags = $video->tags->pluck('name')->implode(', ');
        $this->bunny_video_id = $video->bunny_video_id;

        $this->feedback = null;
        $this->resetValidation();
    }

    public function togglePublished(int $id): void
    {
        $video = Video::findOrFail($id);
        $wasPublished = $video->status === VideoStatus::Published;

        $video->update([
            'status' => $wasPublished ? VideoStatus::Draft : VideoStatus::Published,
            'published_at' => $wasPublished ? null : now(),
        ]);

        $this->feedback = $wasPublished ? 'Video despublicado.' : 'Video publicado.';
    }

    public function delete(int $id): void
    {
        Video::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->cancel();
        }

        $this->feedback = 'Video eliminado.';
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'title', 'description', 'category_id', 'min_plan_level', 'status', 'tags', 'bunny_video_id');
        $this->resetValidation();
    }

    /**
     * Convierte "hipertrofia, glúteo" en etiquetas y las vincula al video.
     */
    private function syncTags(Video $video, string $tags): void
    {
        $ids = collect(explode(',', $tags))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique()
            ->map(fn (string $name) => Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            )->id)
            ->all();

        $video->tags()->sync($ids);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'video';
        $slug = $base;
        $suffix = 2;

        while (Video::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function with(): array
    {
        return [
            'videos' => Video::query()
                ->with(['category', 'tags'])
                ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->when($this->categoryFilter !== '', fn ($q) => $q->where('category_id', $this->categoryFilter))
                ->when($this->levelFilter !== '', fn ($q) => $q->where('min_plan_level', $this->levelFilter))
                ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Panel de administración</p>
                <h1 class="mt-2 text-3xl font-extrabold uppercase text-white">Videos</h1>
            </div>
            <p class="max-w-xs text-right text-xs text-slate-500">
                Modo demo: los videos se registran con un ID simulado hasta conectar Bunny Stream.
            </p>
        </header>

        @if ($feedback)
            <p class="mt-6 rounded-xl border border-volt/40 bg-volt/10 px-4 py-3 text-sm font-medium text-volt">
                {{ $feedback }}
            </p>
        @endif

        <div class="mt-8 grid gap-6 xl:grid-cols-[1fr_1.6fr]">
            {{-- Formulario --}}
            <form wire:submit="save" class="card h-fit">
                <h2 class="font-display text-xl font-bold uppercase text-white">
                    {{ $editingId ? 'Editar video' : 'Nuevo video' }}
                </h2>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-slate-300">Título</label>
                        <input id="title" type="text" wire:model="title" placeholder="Rutina de piernas #1"
                               class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500" />
                        @error('title') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-300">Descripción</label>
                        <textarea id="description" rows="3" wire:model="description"
                                  class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500"></textarea>
                        @error('description') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="category" class="block text-sm font-medium text-slate-300">Categoría</label>
                            <select id="category" wire:model="category_id"
                                    class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 focus:border-ember-500 focus:ring-ember-500">
                                <option value="">Sin categoría</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="level" class="block text-sm font-medium text-slate-300">Nivel mínimo</label>
                            <select id="level" wire:model="min_plan_level"
                                    class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 focus:border-ember-500 focus:ring-ember-500">
                                <option value="1">1 — Básico</option>
                                <option value="2">2 — Medio</option>
                                <option value="3">3 — Premium</option>
                            </select>
                            @error('min_plan_level') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-slate-300">Estado</label>
                            <select id="status" wire:model="status"
                                    class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 focus:border-ember-500 focus:ring-ember-500">
                                <option value="draft">Borrador</option>
                                <option value="processing">Procesando</option>
                                <option value="published">Publicado</option>
                            </select>
                            @error('status') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="tags" class="block text-sm font-medium text-slate-300">Etiquetas</label>
                            <input id="tags" type="text" wire:model="tags" placeholder="fuerza, glúteo"
                                   class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500" />
                            @error('tags') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="bunny" class="block text-sm font-medium text-slate-300">
                            ID en Bunny <span class="text-slate-500">(opcional)</span>
                        </label>
                        <input id="bunny" type="text" wire:model="bunny_video_id" placeholder="Se genera uno simulado si lo dejas vacío"
                               class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500" />
                        @error('bunny_video_id') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="btn-primary">
                        {{ $editingId ? 'Guardar cambios' : 'Crear video' }}
                    </button>

                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="btn-ghost">Cancelar</button>
                    @endif
                </div>
            </form>

            {{-- Listado + filtros --}}
            <div class="space-y-4">
                <div class="card">
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar título..."
                               class="w-full rounded-lg border-ink-line bg-ink text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500" />

                        <select wire:model.live="categoryFilter"
                                class="w-full rounded-lg border-ink-line bg-ink text-slate-100 focus:border-ember-500 focus:ring-ember-500">
                            <option value="">Todas las categorías</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="levelFilter"
                                class="w-full rounded-lg border-ink-line bg-ink text-slate-100 focus:border-ember-500 focus:ring-ember-500">
                            <option value="">Todos los niveles</option>
                            <option value="1">Nivel 1</option>
                            <option value="2">Nivel 2</option>
                            <option value="3">Nivel 3</option>
                        </select>

                        <select wire:model.live="statusFilter"
                                class="w-full rounded-lg border-ink-line bg-ink text-slate-100 focus:border-ember-500 focus:ring-ember-500">
                            <option value="">Todos los estados</option>
                            <option value="draft">Borrador</option>
                            <option value="processing">Procesando</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>
                </div>

                <div class="card overflow-hidden">
                    @if ($videos->isEmpty())
                        <p class="text-sm text-slate-400">No hay videos con esos filtros.</p>
                    @else
                        <div class="-mx-6 -mb-6 overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-ink">
                                    <tr class="text-xs uppercase tracking-wider text-slate-400">
                                        <th scope="col" class="px-6 py-3 font-semibold">Video</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">Nivel</th>
                                        <th scope="col" class="px-4 py-3 font-semibold">Estado</th>
                                        <th scope="col" class="px-6 py-3 text-right font-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink-line">
                                    @foreach ($videos as $video)
                                        <tr wire:key="video-{{ $video->id }}">
                                            <td class="px-6 py-4">
                                                <p class="font-medium text-slate-100">{{ $video->title }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">
                                                    {{ $video->category?->name ?? 'Sin categoría' }}
                                                    @if ($video->tags->isNotEmpty())
                                                        · {{ $video->tags->pluck('name')->implode(', ') }}
                                                    @endif
                                                </p>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="rounded-md bg-ink px-2 py-1 text-xs font-semibold text-slate-300">
                                                    N{{ $video->min_plan_level }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                @if ($video->status === \App\Enums\VideoStatus::Published)
                                                    <span class="text-xs font-semibold text-volt">● Publicado</span>
                                                @else
                                                    <span class="text-xs font-semibold text-slate-400">● {{ $video->status->label() }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center justify-end gap-3">
                                                    <button type="button" wire:click="edit({{ $video->id }})"
                                                            class="text-xs font-medium text-slate-300 transition hover:text-volt">
                                                        Editar
                                                    </button>
                                                    <button type="button" wire:click="togglePublished({{ $video->id }})"
                                                            class="text-xs font-medium text-slate-300 transition hover:text-electric">
                                                        {{ $video->status === \App\Enums\VideoStatus::Published ? 'Despublicar' : 'Publicar' }}
                                                    </button>
                                                    <button type="button" wire:click="delete({{ $video->id }})"
                                                            wire:confirm="¿Eliminar el video «{{ $video->title }}»?"
                                                            class="text-xs font-medium text-ember-400 transition hover:text-ember-300">
                                                        Eliminar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
