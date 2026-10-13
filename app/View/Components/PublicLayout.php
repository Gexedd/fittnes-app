<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Layout público del sitio (cabecera + pie) para páginas sin autenticación.
 * Se usa como <x-public-layout> y renderiza resources/views/layouts/public.blade.php
 */
class PublicLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
    ) {}

    public function render(): View
    {
        return view('layouts.public');
    }
}
