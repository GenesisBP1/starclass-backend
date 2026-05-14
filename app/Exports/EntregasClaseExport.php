<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class EntregasClaseExport implements FromCollection, WithHeadings
{
    protected $claseId;
    protected $fechaInicio;
    protected $fechaFin;
    protected $busqueda;

    public function __construct($claseId, $fechaInicio = null, $fechaFin = null, $busqueda = null)
    {
        $this->claseId = $claseId;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->busqueda = $busqueda;
    }

    public function collection()
    {
        $clase = DB::table('clases')->where('id', $this->claseId)->first();

        if (!$clase) {
            return collect([]);
        }

        $tareas = DB::table('tareas')->where('clase_id', $this->claseId)->get();

        $alumnos = DB::table('alumnos_clases')
            ->join('usuarios', 'alumnos_clases.alumno_id', '=', 'usuarios.id')
            ->where('alumnos_clases.clase_id', $this->claseId)
            ->select('usuarios.id', 'usuarios.nombre', 'usuarios.correo')
            ->get();

        $rows = collect([]);

        $busq = $this->busqueda ? mb_strtolower($this->busqueda) : null;

        foreach ($tareas as $tarea) {
            $entregasQuery = DB::table('tareas_entregadas')->where('tarea_id', $tarea->id);

            if ($this->fechaInicio && $this->fechaFin) {
                $inicio = Carbon::parse($this->fechaInicio)->toDateString();
                $fin = Carbon::parse($this->fechaFin)->toDateString();
                $entregasQuery->whereBetween('fecha_revision', [$inicio, $fin]);
            } elseif ($this->fechaInicio) {
                $entregasQuery->where('fecha_revision', Carbon::parse($this->fechaInicio)->toDateString());
            }

            $entregas = $entregasQuery->get()->keyBy('alumno_id');

            $totalAlumnos = $alumnos->count();
            $totalEntregadas = $entregas->count();
            $totalPendientes = $totalAlumnos - $totalEntregadas;

            $tareaMatches = $busq ? (mb_stripos(mb_strtolower($tarea->titulo), $busq) !== false) : false;

            foreach ($alumnos as $alumno) {
                // apply search filter: if busqueda exists and neither task matches nor student matches, skip
                if ($busq && !$tareaMatches) {
                    $nombre = mb_strtolower($alumno->nombre);
                    $correo = mb_strtolower($alumno->correo);
                    if (mb_stripos($nombre, $busq) === false && mb_stripos($correo, $busq) === false) {
                        continue;
                    }
                }

                $entrega = $entregas->get($alumno->id);

                $rows->push([
                    'clase' => $clase->nombre,
                    'tarea' => $tarea->titulo,
                    'fecha_entrega' => $tarea->fecha_entrega ?? null,
                    'total_alumnos' => $totalAlumnos,
                    'total_entregadas' => $totalEntregadas,
                    'total_pendientes' => $totalPendientes,
                    'alumno' => $alumno->nombre,
                    'correo' => $alumno->correo,
                    'estado' => $entrega ? 'Entregada' : 'Pendiente',
                    'fecha_revision' => $entrega->fecha_revision ?? null,
                    'hora_revision' => $entrega->hora_revision ?? null,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Clase',
            'Tarea',
            'Fecha de entrega',
            'Total alumnos',
            'Total entregadas',
            'Total pendientes',
            'Alumno',
            'Correo',
            'Estado',
            'Fecha revisión',
            'Hora revisión',
        ];
    }
}
