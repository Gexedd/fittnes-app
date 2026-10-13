<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-ink-line/70 bg-ink/90 backdrop-blur">
    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-ember-500 font-display text-lg font-extrabold text-white">F</span>
                        <span class="hidden font-display text-2xl font-extrabold uppercase tracking-wide text-white sm:block">
                            Fit<span class="text-volt">nes</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                @php
                    $links = array_values(array_filter([
                        ['route' => 'dashboard', 'label' => 'Panel'],
                        auth()->user()->isAdmin() ? ['route' => 'admin.videos.index', 'label' => 'Videos'] : null,
                        auth()->user()->isAdmin() ? ['route' => 'admin.categories.index', 'label' => 'Categorías'] : null,
                        auth()->user()->isAdmin() ? null : ['route' => 'planes', 'label' => 'Planes'],
                    ]));
                @endphp

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @foreach ($links as $link)
                        @php($active = request()->routeIs($link['route']))
                        <a href="{{ route($link['route']) }}" wire:navigate
                           @class([
                               'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium transition',
                               'border-ember-500 text-white' => $active,
                               'border-transparent text-slate-400 hover:border-ink-line hover:text-volt' => ! $active,
                           ])>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center rounded-lg border border-ink-line bg-ink-soft px-3 py-2 text-sm font-medium leading-4 text-slate-300 transition hover:text-volt focus:outline-none">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="space-y-1 pt-2 pb-3">
            @foreach ($links as $link)
                @php($active = request()->routeIs($link['route']))
                <a href="{{ route($link['route']) }}" wire:navigate
                   @class([
                       'block border-l-4 py-2 ps-3 pe-4 text-base font-medium transition',
                       'border-ember-500 bg-ink-soft text-white' => $active,
                       'border-transparent text-slate-400 hover:border-ink-line hover:bg-ink-soft hover:text-volt' => ! $active,
                   ])>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-ink-line/70 pt-4 pb-1">
            <div class="px-4">
                <div class="font-display text-base font-bold text-white" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="text-sm font-medium text-slate-500">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
