<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\View\View;

class PlanController
{
    /**
     * Página pública de precios: muestra los planes activos ordenados por nivel.
     */
    public function index(): View
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('level')
            ->get();

        return view('planes', ['plans' => $plans]);
    }
}
