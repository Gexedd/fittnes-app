<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-extrabold uppercase tracking-wide text-white">
            {{ $video->title }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1.6fr_1fr]">
                <div>
                    {{-- Reproductor o paywall --}}
                    @if ($canWatch)
                        @if ($playbackUrl)
                            <div class="overflow-hidden rounded-2xl border border-ink-line bg-black">
                                <iframe
                                    src="{{ $playbackUrl }}"
                                    title="{{ $video->title }}"
                                    class="aspect-video w-full"
                                    loading="lazy"
                                    allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        @else
                            {{-- Sin credenciales de Bunny: el contenedor queda listo. --}}
                            <div class="grid aspect-video w-full place-items-center rounded-2xl border border-dashed border-ink-line bg-ink-soft text-center">
                                <div class="px-6">
                                    <p class="font-display text-2xl font-bold uppercase text-slate-300">Reproductor listo</p>
                                    <p class="mt-2 max-w-sm text-sm text-slate-500">
                                        Tienes acceso a este video. Al conectar las credenciales de
                                        Bunny Stream se reproducirá aquí con una URL firmada.
                                    </p>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="grid aspect-video w-full place-items-center rounded-2xl border border-ember-500/40 bg-ink-soft text-center">
                            <div class="px-6">
                                <p class="font-display text-3xl font-extrabold uppercase text-white">Contenido bloqueado</p>
                                <p class="mt-2 text-slate-400">
                                    Este video requiere el plan
                                    <strong class="text-volt">{{ $requiredPlan?->name ?? 'superior' }}</strong>
                                    y el tuyo no alcanza ese nivel.
                                </p>
                                <a href="{{ route('planes') }}" class="btn-primary mt-6">Mejorar mi plan</a>
                            </div>
                        </div>
                    @endif

                    @if ($video->description)
                        <p class="mt-6 leading-relaxed text-slate-300">{{ $video->description }}</p>
                    @endif
                </div>

                {{-- Ficha del video --}}
                <aside class="card h-fit">
                    <p class="eyebrow">Detalles</p>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Categoría</dt>
                            <dd class="text-slate-200">{{ $video->category?->name ?? 'Sin categoría' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Nivel requerido</dt>
                            <dd class="text-slate-200">Nivel {{ $video->min_plan_level }}</dd>
                        </div>
                        @if ($video->duration_seconds)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500">Duración</dt>
                                <dd class="text-slate-200">{{ $video->durationFormatted() }}</dd>
                            </div>
                        @endif
                        @if ($video->published_at)
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500">Publicado</dt>
                                <dd class="text-slate-200">{{ $video->published_at->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($video->tags->isNotEmpty())
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($video->tags as $tag)
                                <a href="{{ route('videos.index', ['etiqueta' => $tag->slug]) }}"
                                   wire:navigate
                                   class="rounded-full border border-ink-line px-3 py-1 text-xs text-slate-300 transition hover:border-volt hover:text-volt">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @unless ($canWatch)
                        <div class="mt-6 rounded-xl border border-ink-line bg-ink p-4 text-sm">
                            <p class="text-slate-400">Tu plan actual:</p>
                            <p class="mt-1 font-display text-xl font-bold uppercase text-white">
                                {{ auth()->user()->planLevel() > 0 ? 'Nivel '.\auth()->user()->planLevel() : 'Sin suscripción' }}
                            </p>
                            <a href="{{ route('planes') }}" class="btn-ghost mt-4 w-full text-sm">Ver planes</a>
                        </div>
                    @endunless
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
