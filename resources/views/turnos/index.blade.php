{{-- resources/views/turnos/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('turnos.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('turnos.index') }}">
    <div class="col-sm-6 col-md-3">
        <select name="agente_id" class="form-select form-select-sm">
            <option value="">Todos los agentes</option>
            @foreach($agentes as $a)
                <option value="{{ $a->id }}" {{ request('agente_id') == $a->id ? 'selected' : '' }}>
                    {{ $a->codigo }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            @foreach($estados as $e)
                <option value="{{ $e->value }}" {{ request('estado') === $e->value ? 'selected' : '' }}>
                    {{ $e->etiqueta() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <input type="date" name="fecha_desde" class="form-control form-control-sm"
               value="{{ request('fecha_desde') }}" title="Desde">
    </div>
    <div class="col-sm-6 col-md-2">
        <input type="date" name="fecha_hasta" class="form-control form-control-sm"
               value="{{ request('fecha_hasta') }}" title="Hasta">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        <a href="{{ route('turnos.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Turnos de Agentes</span>
        <span class="text-muted small">{{ $turnos->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Agente</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th class="text-end">Duración</th>
                    <th>Estado</th>
                    <th>Incidentes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($turnos as $turno)
                    <tr>
                        <td class="text-muted">{{ $turno->id }}</td>
                        <td>
                            <span class="fw-semibold">{{ $turno->agente?->codigo ?? '—' }}</span>
                        </td>
                        <td>{{ $turno->inicio_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $turno->fin_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            @if($turno->estaActivo())
                                <span class="text-success fw-semibold">
                                    {{ $turno->minutosTranscurridos() }} min
                                    <i class="bi bi-circle-fill ms-1" style="font-size:.55rem"></i>
                                </span>
                            @else
                                {{ $turno->duracionMinutos() ?? '—' }} min
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $turno->estado?->color() }}">
                                {{ $turno->estado?->etiqueta() }}
                            </span>
                        </td>
                        <td class="text-center">
                            {{ $turno->incidentes_count ?? '—' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('turnos.show', $turno) }}"
                               class="btn btn-sm btn-outline-secondary" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay turnos registrados con los filtros actuales.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($turnos->hasPages())
        <div class="card-footer bg-transparent">
            {{ $turnos->links() }}
        </div>
    @endif
</div>

@endsection
