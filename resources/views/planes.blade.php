<x-public-layout :title="'Planes y precios — '.config('app.name')" description="Elige el plan que se ajuste a tu entrenamiento. Cancela cuando quieras.">
    <section class="mx-auto max-w-6xl px-4 pt-16 pb-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="eyebrow">Suscripciones</p>
            <h1 class="mt-3 text-4xl font-extrabold uppercase tracking-tight text-white sm:text-5xl">
                Elige tu plan
            </h1>
            <p class="mt-4 text-lg text-slate-400">
                Entrena a tu ritmo con acceso a todo el catálogo que tu plan permita.
                Mejora o cancela cuando quieras.
            </p>
        </div>

        @if ($plans->isEmpty())
            <p class="mt-16 text-center text-slate-400">
                Todavía no hay planes publicados. Vuelve pronto.
            </p>
        @else
            <div class="mt-14 grid gap-6 md:grid-cols-3">
                @foreach ($plans as $plan)
                    @php($isFeatured = $plan->level === $plans->max('level'))

                    <div @class([
                        'relative flex flex-col rounded-2xl border p-6 transition',
                        'border-volt/60 bg-ink-soft shadow-neon' => $isFeatured,
                        'border-ink-line bg-ink-soft hover:border-ember-500/60' => ! $isFeatured,
                    ])>
                        @if ($isFeatured)
                            <span class="absolute -top-3 left-6 rounded-full bg-volt px-3 py-1 font-display text-xs font-bold uppercase tracking-wider text-ink">
                                Más completo
                            </span>
                        @endif

                        <p class="font-display text-sm font-semibold uppercase tracking-[0.18em] text-slate-400">
                            Nivel {{ $plan->level }}
                        </p>

                        <h2 class="mt-2 text-3xl font-extrabold uppercase text-white">
                            {{ $plan->name }}
                        </h2>

                        <p class="mt-4 flex items-baseline gap-1">
                            <span class="font-display text-5xl font-extrabold text-ember-500">
                                {{ $plan->priceFormatted() }}
                            </span>
                            <span class="text-sm text-slate-400">CLP / mes</span>
                        </p>

                        @if ($plan->description)
                            <p class="mt-4 text-sm text-slate-400">{{ $plan->description }}</p>
                        @endif

                        <ul class="mt-6 flex-1 space-y-3 text-sm">
                            @foreach (($plan->features ?? []) as $feature)
                                <li class="flex items-start gap-3">
                                    <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-volt/15 text-xs font-bold text-volt">✓</span>
                                    <span class="text-slate-300">{{ $feature }}</span>
                                </li>
                            @endforeach
                            <li class="flex items-start gap-3">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-volt/15 text-xs font-bold text-volt">✓</span>
                                <span class="text-slate-300">Acceso a videos de nivel {{ $plan->level }} o inferior</span>
                            </li>
                        </ul>

                        @auth
                            <a href="{{ route('dashboard') }}" class="mt-8 {{ $isFeatured ? 'btn-primary' : 'btn-ghost' }} w-full">
                                Ir a mi panel
                            </a>
                        @else
                            <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="mt-8 {{ $isFeatured ? 'btn-primary' : 'btn-ghost' }} w-full">
                                Suscribirme
                            </a>
                        @endauth
                    </div>
                @endforeach
            </div>

            {{-- Comparativa rápida de niveles de acceso --}}
            <div class="mt-20 overflow-hidden rounded-2xl border border-ink-line">
                <table class="w-full text-left text-sm">
                    <caption class="sr-only">Comparativa de acceso por plan</caption>
                    <thead class="bg-ink-soft">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-display uppercase tracking-wider text-slate-300">Qué incluye</th>
                            @foreach ($plans as $plan)
                                <th scope="col" class="px-6 py-4 text-center font-display uppercase tracking-wider text-slate-300">
                                    {{ $plan->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-line">
                        @foreach (range(1, $plans->max('level')) as $nivel)
                            <tr>
                                <td class="px-6 py-4 text-slate-300">Videos de nivel {{ $nivel }}</td>
                                @foreach ($plans as $plan)
                                    <td class="px-6 py-4 text-center">
                                        @if ($plan->level >= $nivel)
                                            <span class="font-bold text-volt">✓</span>
                                        @else
                                            <span class="text-slate-600">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-public-layout>
