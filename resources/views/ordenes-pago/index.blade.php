{{-- resources/views/ordenes-pago/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('ordenes-pago.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('ordenes-pago.index') }}">
    <div class="col-sm-6 col-md-3">
        <input type="text" name="placa" class="form-control form-control-sm"
               placeholder="Placa (ej: ABC1234)"
               value="{{ $placaFiltro }}">
    </div>
    <div class="col-sm-6 col-md-2">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            @foreach($estados as $estado)
                <option value="{{ $estado->value }}" {{ $estadoFiltro === $estado->value ? 'selected' : '' }}>
                    {{ $estado->etiqueta() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        <a href="{{ route('ordenes-pago.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-receipt me-2"></i>Órdenes de Pago</span>
        <span class="text-muted small">{{ $ordenes->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>N° Orden</th>
                    <th>Placa</th>
                    <th>Tipo Infracción</th>
                    <th class="text-end">Monto</th>
                    <th>Estado</th>
                    <th>Vence</th>
                    <th>Generada por</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $orden)
                    <tr>
                        <td class="fw-semibold">{{ $orden->numero_orden }}</td>
                        <td>{{ $orden->infraccion?->placa ?? '—' }}</td>
                        <td>{{ $orden->infraccion?->tipo_infraccion?->value ?? '—' }}</td>
                        <td class="text-end">$ {{ number_format($orden->monto, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $orden->estado->claseBadge() }}">
                                {{ $orden->estado->etiqueta() }}
                            </span>
                        </td>
                        <td>{{ $orden->vence_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $orden->generadaPor?->name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('ordenes-pago.show', $orden) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">
                            No se encontraron órdenes de pago.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($ordenes->hasPages())
        <div class="card-footer bg-transparent">
            {{ $ordenes->links() }}
        </div>
    @endif
</div>

@endsection
