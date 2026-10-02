<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function clientesPorZona()
    {
        // 1. Consulta con Query Builder
        $zonas = DB::table('clients') // ← HUECO A
            ->select(
                'zona_geografica', // ← HUECO B
                DB::raw('COUNT(*) as total') // ← HUECO C
            )
            ->groupBy('zona_geografica') // ← HUECO D
            ->orderByDesc('total') // ← HUECO E
            ->get(); // ← HUECO F

        // 2. Total general
        $totalGeneral = $zonas->sum('total'); // ← HUECO G

        // 3. Agregar porcentaje
        $zonasConPorcentaje = $zonas->map(function ($zona) use ($totalGeneral) { // ← HUECO H
            $zona->porcentaje = $totalGeneral > 0
                ? round(($zona->total / $totalGeneral) * 100, 2) // ← HUECO I
                : 0;
            return $zona; // ← HUECO J
        });

        // Reto 1: Filtrar zonas con porcentaje mayor a 15%
        $zonasFiltradas = $zonasConPorcentaje->filter(function ($zona) {
            return $zona->porcentaje > 15;
        });

        // 4. Datos para gráfico
        $labels = $zonasConPorcentaje->pluck('zona_geografica')->toArray(); // ← HUECO K
        $data = $zonasConPorcentaje->pluck('total')->toArray(); // ← HUECO L

        return view('reports.zonas', compact(
            'zonasConPorcentaje', 'totalGeneral', 'labels', 'data', 'zonasFiltradas'
        ));
    }

    public function interaccionesPorAsesor()
    {
        // Reporte 2: Obtener asesores con sus conteos por tipo de interacción y total
        $asesores = DB::table('users')
            ->leftJoin('clients', 'clients.user_id', '=', 'users.id')
            ->leftJoin('interactions', 'interactions.client_id', '=', 'clients.id')
            ->select(
                'users.name',
                DB::raw("SUM(CASE WHEN interactions.tipo_interaccion = 'Llamada' THEN 1 ELSE 0 END) as llamadas"),
                DB::raw("SUM(CASE WHEN interactions.tipo_interaccion = 'Visita' THEN 1 ELSE 0 END) as visitas"),
                DB::raw("SUM(CASE WHEN interactions.tipo_interaccion = 'WhatsApp' THEN 1 ELSE 0 END) as whatsapp"),
                DB::raw("COUNT(interactions.id) as total")
            )
            ->groupBy('users.id', 'users.name')
            ->get();

        // Reto 2: Desglose por día de la semana para $interaccionesPorDia
        $interaccionesPorDia = DB::table('interactions')
            ->select(
                DB::raw('DAYNAME(created_at) as dia'), 
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('dia')
            ->get();

        // Datos para gráfico de Chart.js
        $labels = $asesores->pluck('name')->toArray();
        $llamadas = $asesores->pluck('llamadas')->toArray();
        $visitas = $asesores->pluck('visitas')->toArray();
        $whatsapp = $asesores->pluck('whatsapp')->toArray();

        return view('reports.interacciones', compact(
            'asesores', 
            'interaccionesPorDia', 
            'labels', 
            'llamadas', 
            'visitas', 
            'whatsapp'
        ));
    }
}