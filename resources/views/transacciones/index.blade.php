{{-- resources/views/transacciones/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('transacciones.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('transacciones.index') }}">
    <div class="col-sm-6 col-md-2">
        <input type="text" name="external_reference" class="form-control form-control-sm"
               placeholder="Referencia externa"
               value="{{ request('external_reference') }}">
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
        <select name="proveedor" class="form-select form-select-sm">
            <option value="">Todos los proveedores</option>
            @foreach($proveedores as $val => $etiqueta)
                <option value="{{ $val }}" {{ request('proveedor') === $val ? 'selected' : '' }}>
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
        <a href="{{ route('transacciones.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-credit-card me-2"></i>Transacciones de Pago (Art. 21)</span>
        <span class="text-muted small">{{ $transacciones->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Concepto</th>
                    <th>Proveedor</th>
                    <th class="text-end">Monto</th>
                    <th>Estado</th>
                    <th>Referencia</th>
                    <th>Callback</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transacciones as $tx)
                <tr>
                    <td><code>{{ $tx->id }}</code></td>
                    <td>
                        <span class="text-muted" title="{{ $tx->concepto_type }}">
                            {{ class_basename($tx->concepto_type) }}
                            #{{ $tx->concepto_id }}
                        </span>
                    </td>
                    <td><span class="badge bg-secondary">{{ $tx->proveedor->value }}</span></td>
                    <td class="text-end fw-semibold">${{ number_format($tx->monto, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $tx->estado->color() }}">
                            {{ $tx->estado->etiqueta() }}
                        </span>
                    </td>
                    <td>
                        <span class="text-truncate d-inline-block" style="max-width: 140px"
                              title="{{ $tx->external_reference }}">
                            {{ $tx->external_reference ?? '—' }}
                        </span>
                    </td>
                    <td>{{ $tx->callback_recibido_en?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ $tx->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No se encontraron transacciones con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transacciones->hasPages())
    <div class="card-footer bg-transparent">
        {{ $transacciones->links() }}
    </div>
    @endif
</div>

@endsection
