{{-- resources/views/cancelaciones/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('cancelaciones.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('cancelaciones.index') }}">
    <div class="col-sm-6 col-md-2">
        <input type="text" name="placa" class="form-control form-control-sm"
               placeholder="Placa (ej: ABC1234)"
               value="{{ request('placa') }}">
    </div>
    <div class="col-sm-6 col-md-3">
        <select name="tipo" class="form-select form-select-sm">
            <option value="">Todos los tipos</option>
            @foreach($tipos as $val => $etiqueta)
                <option value="{{ $val }}" {{ request('tipo') === $val ? 'selected' : '' }}>
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
        <a href="{{ route('cancelaciones.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-x-circle me-2"></i>Cancelaciones</span>
        <span class="text-muted small">{{ $cancelaciones->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Ticket</th>
                    <th>Placa</th>
                    <th>Tipo</th>
                    <th>Cancelado por</th>
                    <th class="text-end">Reembolso</th>
                    <th>Estado reembolso</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($cancelaciones as $c)
                <tr>
                    <td><code>{{ $c->id }}</code></td>
                    <td>
                        @if($c->ticket)
                            <a href="{{ route('tickets.show', $c->ticket) }}" class="text-decoration-none">
                                <code>{{ $c->ticket->codigo ?? $c->ticket_id }}</code>
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td><strong>{{ $c->ticket?->placa ?? '—' }}</strong></td>
                    <td>
                        <span class="badge bg-{{ $c->tipo === \App\Enums\TipoCancelacion::Admin ? 'warning text-dark' : 'secondary' }}">
                            {{ $c->tipo->etiqueta() }}
                        </span>
                    </td>
                    <td>{{ $c->canceladoPorUsuario?->name ?? '—' }}</td>
                    <td class="text-end">${{ number_format($c->monto_reembolsado, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $c->estado_reembolso->color() }}">
                            {{ $c->estado_reembolso->etiqueta() }}
                        </span>
                    </td>
                    <td>{{ $c->cancelado_en?->format('d/m/Y H:i') }}</td>
                    <td>
                        <a href="{{ route('cancelaciones.show', $c) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No se encontraron cancelaciones con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cancelaciones->hasPages())
    <div class="card-footer bg-transparent">
        {{ $cancelaciones->links() }}
    </div>
    @endif
</div>

@endsection
