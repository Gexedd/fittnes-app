<x-public-layout :title="config('app.name').' — Entrena con los mejores'">
    <section class="relative overflow-hidden">
        {{-- Rejilla de fondo sutil --}}
        <div class="pointer-events-none absolute inset-0 bg-grid-dark bg-[size:44px_44px] opacity-40"></div>
        <div class="pointer-events-none absolute -top-40 right-0 h-96 w-96 rounded-full bg-ember-500/20 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-6xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-28 lg:px-8">
            <div>
                <p class="eyebrow">Entrenamiento en video</p>
                <h1 class="mt-4 text-5xl font-extrabold uppercase leading-[0.95] tracking-tight text-white sm:text-6xl">
                    Entrena fuerte.<br>
                    <span class="text-ember-500">Rinde</span> más.
                </h1>
                <p class="mt-6 max-w-lg text-lg text-slate-400">
                    Programa completo de entrenamientos en video, con rutinas para todos los niveles.
                    Elige tu plan y empieza hoy mismo.
                </p>

                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ route('planes') }}" class="btn-primary">Ver planes y precios</a>
                    <a href="{{ route('register') }}" class="btn-ghost">Crear cuenta gratis</a>
                </div>

                <dl class="mt-12 grid max-w-md grid-cols-3 gap-6">
                    <div>
                        <dt class="font-display text-3xl font-extrabold text-volt">3</dt>
                        <dd class="text-sm text-slate-400">Niveles de plan</dd>
                    </div>
                    <div>
                        <dt class="font-display text-3xl font-extrabold text-volt">HD</dt>
                        <dd class="text-sm text-slate-400">Video adaptativo</dd>
                    </div>
                    <div>
                        <dt class="font-display text-3xl font-extrabold text-volt">24/7</dt>
                        <dd class="text-sm text-slate-400">Acceso a tu ritmo</dd>
                    </div>
                </dl>
            </div>

            <div class="relative">
                <div class="card border-ink-line/80 bg-ink-soft/90 p-8">
                    <p class="font-display text-4xl font-extrabold uppercase text-white">
                        Empieza hoy
                    </p>
                    <p class="mt-3 text-slate-400">
                        Sin permanencia. Cancela cuando quieras desde tu panel.
                    </p>

                    <ul class="mt-8 space-y-4 text-sm">
                        <li class="flex items-center gap-3 text-slate-300">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-volt/15 font-bold text-volt">✓</span>
                            Catálogo en crecimiento constante
                        </li>
                        <li class="flex items-center gap-3 text-slate-300">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-volt/15 font-bold text-volt">✓</span>
                            Sigue tu progreso de cada rutina
                        </li>
                        <li class="flex items-center gap-3 text-slate-300">
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-volt/15 font-bold text-volt">✓</span>
                            Pago seguro en pesos chilenos
                        </li>
                    </ul>

                    <a href="{{ route('planes') }}" class="btn-primary mt-8 w-full">Elegir mi plan</a>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
