{{-- resources/views/sesiones-parqueo/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('sesiones-parqueo.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('sesiones-parqueo.index') }}">
    <div class="col-sm-6 col-md-3">
        <select name="agente_parqueo_id" class="form-select form-select-sm">
            <option value="">Todos los agentes</option>
            @foreach($agentes as $ag)
                <option value="{{ $ag->id }}" {{ request('agente_parqueo_id') == $ag->id ? 'selected' : '' }}>
                    {{ $ag->codigo }} — {{ $ag->user?->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="zona_id" class="form-select form-select-sm">
            <option value="">Todas las zonas</option>
            @foreach($zonas as $z)
                <option value="{{ $z->id }}" {{ request('zona_id') == $z->id ? 'selected' : '' }}>
                    {{ $z->nombre }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            @foreach($estados as $val => $etiqueta)
                <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>
                    {{ $etiqueta }}
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
        <a href="{{ route('sesiones-parqueo.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-p-circle me-2"></i>Sesiones de Parqueo</span>
        <span class="text-muted small">{{ $sesiones->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Ticket</th>
                    <th>Placa</th>
                    <th>Agente</th>
                    <th>Estado</th>
                    <th>Inicio</th>
                    <th>Fin programado</th>
                    <th>Fin real</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sesiones as $s)
                <tr>
                    <td><code>{{ $s->id }}</code></td>
                    <td>
                        @if($s->ticket)
                            <a href="{{ route('tickets.show', $s->ticket) }}" class="text-decoration-none">
                                <code>{{ $s->ticket->codigo ?? $s->ticket_id }}</code>
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><strong>{{ $s->ticket?->placa ?? '—' }}</strong></td>
                    <td>{{ $s->agente?->codigo ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $s->estado->color() }}">
                            {{ $s->estado->etiqueta() }}
                        </span>
                    </td>
                    <td>{{ $s->inicio_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $s->fin_programado_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $s->fin_real_at?->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No se encontraron sesiones con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($sesiones->hasPages())
    <div class="card-footer bg-transparent">
        {{ $sesiones->links() }}
    </div>
    @endif
</div>

@endsection
