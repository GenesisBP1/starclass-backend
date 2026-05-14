<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EntregaController extends Controller
{
    public function entregar(Request $request)
    {
        $request->validate([
            'tarea_id' => 'required|exists:tareas,id',
            'alumno_id' => 'required|exists:usuarios,id',
        ]);

        $existe = DB::table('tareas_entregadas')
            ->where('tarea_id', $request->tarea_id)
            ->where('alumno_id', $request->alumno_id)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Esta tarea ya fue entregada'
            ], 400);
        }

        DB::table('tareas_entregadas')->insert([
            'tarea_id' => $request->tarea_id,
            'alumno_id' => $request->alumno_id,
            'fecha_revision' => now()->toDateString(),
            'hora_revision' => now()->toTimeString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tarea entregada correctamente'
        ]);
    }

    public function entregasPorTarea($id)
    {
        $entregas = DB::table('tareas_entregadas')
            ->join('usuarios', 'tareas_entregadas.alumno_id', '=', 'usuarios.id')
            ->where('tareas_entregadas.tarea_id', $id)
            ->select(
                'tareas_entregadas.id',
                'usuarios.nombre',
                'usuarios.correo',
                'tareas_entregadas.fecha_revision',
                'tareas_entregadas.hora_revision'
            )
            ->get();

        return response()->json([
            'entregas' => $entregas
        ]);
    }

    public function reporteTarea($id)
    {
        $tarea = DB::table('tareas')->where('id', $id)->first();

        if (!$tarea) {
            return response()->json([
                'message' => 'Tarea no encontrada'
            ], 404);
        }

        $alumnos = DB::table('alumnos_clases')
            ->join('usuarios', 'alumnos_clases.alumno_id', '=', 'usuarios.id')
            ->where('alumnos_clases.clase_id', $tarea->clase_id)
            ->select(
                'usuarios.id',
                'usuarios.nombre',
                'usuarios.correo'
            )
            ->get();

        if ($alumnos->isEmpty()) {
            return response()->json([
                'total_alumnos' => 0,
                'total_entregadas' => 0,
                'total_pendientes' => 0,
                'reporte' => []
            ]);
        }

        $entregasQuery = DB::table('tareas_entregadas')
            ->where('tarea_id', $id);

        if (request()->filled('fecha')) {
            $entregasQuery->where('fecha_revision', request()->fecha);
        }

        $entregas = $entregasQuery->get()->keyBy('alumno_id');

        $reporte = $alumnos->map(function ($alumno) use ($entregas) {
            $entrega = $entregas->get($alumno->id);
            return [
                'alumno_id' => $alumno->id,
                'nombre' => $alumno->nombre,
                'correo' => $alumno->correo,
                'estado' => $entrega ? 'Entregada' : 'Pendiente',
                'fecha_revision' => $entrega->fecha_revision ?? null,
                'hora_revision' => $entrega->hora_revision ?? null,
            ];
        });

        return response()->json([
            'total_alumnos' => $alumnos->count(),
            'total_entregadas' => $reporte->where('estado', 'Entregada')->count(),
            'total_pendientes' => $reporte->where('estado', 'Pendiente')->count(),
            'reporte' => $reporte->values()
        ]);
    }

    public function reporteEntregasPorClase($id)
    {
        $clase = DB::table('clases')->where('id', $id)->first();

        if (!$clase) {
            return response()->json([
                'message' => 'Clase no encontrada'
            ], 404);
        }

        $fechaInicio = request()->query('fecha_inicio');
        $fechaFin = request()->query('fecha_fin');
        $busqueda = request()->query('busqueda');

        $tareas = DB::table('tareas')->where('clase_id', $id)->get();

        $alumnos = DB::table('alumnos_clases')
            ->join('usuarios', 'alumnos_clases.alumno_id', '=', 'usuarios.id')
            ->where('alumnos_clases.clase_id', $id)
            ->select('usuarios.id', 'usuarios.nombre', 'usuarios.correo')
            ->get();

        $reporte = $tareas->map(function ($tarea) use ($alumnos, $fechaInicio, $fechaFin, $busqueda) {
            $entregasQuery = DB::table('tareas_entregadas')->where('tarea_id', $tarea->id);

            if ($fechaInicio && $fechaFin) {
                $inicio = Carbon::parse($fechaInicio)->toDateString();
                $fin = Carbon::parse($fechaFin)->toDateString();
                $entregasQuery->whereBetween('fecha_revision', [$inicio, $fin]);
            } elseif ($fechaInicio) {
                $entregasQuery->where('fecha_revision', Carbon::parse($fechaInicio)->toDateString());
            }

            $entregas = $entregasQuery->get()->keyBy('alumno_id');

            $totalAlumnos = $alumnos->count();
            $totalEntregadas = $entregas->count();
            $totalPendientes = $totalAlumnos - $totalEntregadas;

            $busq = $busqueda ? mb_strtolower($busqueda) : null;
            $tareaMatches = $busq ? (mb_stripos(mb_strtolower($tarea->titulo), $busq) !== false) : false;

            $alumnosListado = $alumnos->filter(function ($alumno) use ($entregas, $busq, $tareaMatches) {
                if (!$busq) return true;
                if ($tareaMatches) return true;
                $nombre = mb_strtolower($alumno->nombre);
                $correo = mb_strtolower($alumno->correo);
                return (mb_stripos($nombre, $busq) !== false) || (mb_stripos($correo, $busq) !== false);
            })->map(function ($alumno) use ($entregas) {
                $entrega = $entregas->get($alumno->id);
                return [
                    'alumno_id' => $alumno->id,
                    'nombre' => $alumno->nombre,
                    'correo' => $alumno->correo,
                    'estado' => $entrega ? 'Entregada' : 'Pendiente',
                    'fecha_revision' => $entrega->fecha_revision ?? null,
                    'hora_revision' => $entrega->hora_revision ?? null,
                ];
            })->values();

            return [
                'tarea_id' => $tarea->id,
                'titulo' => $tarea->titulo,
                'descripcion' => $tarea->descripcion ?? null,
                'fecha_entrega' => $tarea->fecha_entrega ?? null,
                'total_alumnos' => $totalAlumnos,
                'total_entregadas' => $totalEntregadas,
                'total_pendientes' => $totalPendientes,
                'alumnos' => $alumnosListado,
            ];
        })->values();

        return response()->json([
            'clase' => $clase,
            'total_tareas' => $tareas->count(),
            'reporte' => $reporte
        ]);
    }
}