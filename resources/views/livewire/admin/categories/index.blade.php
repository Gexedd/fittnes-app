<?php

use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public ?string $feedback = null;

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($this->editingId) {
            $category = Category::findOrFail($this->editingId);

            $category->update([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name'], $category->id),
                'description' => $data['description'] ?: null,
            ]);

            $this->feedback = 'Categoría actualizada.';
        } else {
            Category::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?: null,
            ]);

            $this->feedback = 'Categoría creada.';
        }

        $this->cancel();
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = (string) $category->description;
        $this->feedback = null;
        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        Category::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->cancel();
        }

        $this->feedback = 'Categoría eliminada.';
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'name', 'description');
        $this->resetValidation();
    }

    /**
     * Genera un slug único (si ya existe, añade -2, -3, ...).
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'categoria';
        $slug = $base;
        $suffix = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    /**
     * Datos que recibe la vista.
     */
    public function with(): array
    {
        return [
            'categories' => Category::query()->withCount('videos')->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <header>
            <p class="eyebrow">Panel de administración</p>
            <h1 class="mt-2 text-3xl font-extrabold uppercase text-white">Categorías</h1>
        </header>

        @if ($feedback)
            <p class="mt-6 rounded-xl border border-volt/40 bg-volt/10 px-4 py-3 text-sm font-medium text-volt">
                {{ $feedback }}
            </p>
        @endif

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1.3fr]">
            {{-- Formulario --}}
            <form wire:submit="save" class="card h-fit">
                <h2 class="font-display text-xl font-bold uppercase text-white">
                    {{ $editingId ? 'Editar categoría' : 'Nueva categoría' }}
                </h2>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-300">Nombre</label>
                        <input id="name" type="text" wire:model="name" placeholder="Fuerza, Cardio, Movilidad..."
                               class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500" />
                        @error('name') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-300">Descripción</label>
                        <textarea id="description" rows="3" wire:model="description"
                                  class="mt-1 w-full rounded-lg border-ink-line bg-ink text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500"></textarea>
                        @error('description') <p class="mt-1 text-sm text-ember-400">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-6 flex items-center gap-3">
                    <button type="submit" class="btn-primary">
                        {{ $editingId ? 'Guardar cambios' : 'Crear categoría' }}
                    </button>

                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="btn-ghost">Cancelar</button>
                    @endif
                </div>
            </form>

            {{-- Listado --}}
            <div class="card">
                <h2 class="font-display text-xl font-bold uppercase text-white">Existentes</h2>

                @if ($categories->isEmpty())
                    <p class="mt-4 text-sm text-slate-400">Todavía no hay categorías.</p>
                @else
                    <ul class="mt-4 divide-y divide-ink-line">
                        @foreach ($categories as $category)
                            <li class="flex items-center justify-between gap-4 py-3" wire:key="category-{{ $category->id }}">
                                <div>
                                    <p class="font-medium text-slate-100">{{ $category->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $category->slug }} · {{ $category->videos_count }} video(s)
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="button" wire:click="edit({{ $category->id }})"
                                            class="text-sm font-medium text-slate-300 transition hover:text-volt">
                                        Editar
                                    </button>
                                    <button type="button" wire:click="delete({{ $category->id }})"
                                            wire:confirm="¿Eliminar la categoría «{{ $category->name }}»?"
                                            class="text-sm font-medium text-ember-400 transition hover:text-ember-300">
                                        Eliminar
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
