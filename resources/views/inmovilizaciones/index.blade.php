{{-- resources/views/inmovilizaciones/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('inmovilizaciones.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('inmovilizaciones.index') }}">
    <div class="col-sm-6 col-md-2">
        <input type="text" name="placa" class="form-control form-control-sm"
               placeholder="Placa (ej: ABC1234)"
               value="{{ request('placa') }}">
    </div>
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
        <a href="{{ route('inmovilizaciones.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-lock me-2"></i>Inmovilizaciones (Art. 15)</span>
        <span class="text-muted small">{{ $inmovilizaciones->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Placa</th>
                    <th>Infracción</th>
                    <th>Agente</th>
                    <th>Estado</th>
                    <th>Inmovilizada</th>
                    <th>Liberada</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($inmovilizaciones as $inm)
                <tr>
                    <td><code>{{ $inm->id }}</code></td>
                    <td><strong>{{ $inm->infraccion?->placa ?? '—' }}</strong></td>
                    <td>
                        @if($inm->infraccion)
                            <a href="{{ route('infracciones.show', $inm->infraccion) }}" class="text-decoration-none">
                                <code>#{{ $inm->infraccion_id }}</code>
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $inm->agente?->codigo ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $inm->estado->color() }}">
                            {{ $inm->estado->etiqueta() }}
                        </span>
                    </td>
                    <td>{{ $inm->inmovilizada_en?->format('d/m/Y H:i') }}</td>
                    <td>{{ $inm->liberada_en?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('inmovilizaciones.show', $inm) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No se encontraron inmovilizaciones con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($inmovilizaciones->hasPages())
    <div class="card-footer bg-transparent">
        {{ $inmovilizaciones->links() }}
    </div>
    @endif
</div>

@endsection
