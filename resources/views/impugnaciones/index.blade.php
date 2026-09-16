{{-- resources/views/impugnaciones/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('impugnaciones.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('impugnaciones.index') }}">
    <div class="col-sm-6 col-md-3">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            @foreach($estados as $val => $etiqueta)
                <option value="{{ $val }}" {{ request('estado') === $val ? 'selected' : '' }}>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-sm-6 col-md-3">
        <select name="agente_id" class="form-select form-select-sm">
            <option value="">Todos los agentes</option>
            @foreach($agentes as $ag)
                <option value="{{ $ag->id }}" {{ request('agente_id') == $ag->id ? 'selected' : '' }}>
                    {{ $ag->codigo }}
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
        <a href="{{ route('impugnaciones.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-shield-exclamation me-2"></i>Impugnaciones</span>
        <span class="text-muted small">{{ $impugnaciones->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Infracción</th>
                    <th>Placa</th>
                    <th>Conductor</th>
                    <th>Agente</th>
                    <th>Estado</th>
                    <th>Presentada</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($impugnaciones as $imp)
                <tr>
                    <td><code>{{ $imp->id }}</code></td>
                    <td>
                        <a href="{{ route('infracciones.show', $imp->infraccion_id) }}"
                           class="text-decoration-none">
                            #{{ $imp->infraccion_id }}
                        </a>
                    </td>
                    <td><strong>{{ $imp->infraccion?->placa ?? '—' }}</strong></td>
                    <td>
                        {{ $imp->conductor?->user?->perfil?->nombres_completos ?? '—' }}
                    </td>
                    <td>{{ $imp->infraccion?->agente?->codigo ?? '—' }}</td>
                    <td>
                        <span class="badge bg-{{ $imp->colorBadge() }}">
                            {{ ucfirst($imp->estado) }}
                        </span>
                    </td>
                    <td>{{ $imp->created_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('impugnaciones.show', $imp) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No se encontraron impugnaciones con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($impugnaciones->hasPages())
    <div class="card-footer bg-transparent">
        {{ $impugnaciones->links() }}
    </div>
    @endif
</div>

@endsection
