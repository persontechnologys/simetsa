{{-- resources/views/liquidaciones/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('liquidaciones.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('liquidaciones.index') }}">
    <div class="col-sm-6 col-md-3">
        <select name="tipo" class="form-select form-select-sm">
            <option value="agente"      {{ $tipoFiltro === 'agente' ? 'selected' : '' }}>Agentes de Parqueo</option>
            <option value="punto_venta" {{ $tipoFiltro === 'punto_venta' ? 'selected' : '' }}>Puntos de Venta</option>
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <input type="month" name="periodo" class="form-control form-control-sm"
               value="{{ $periodoFiltro }}" title="Periodo (año-mes)">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        <a href="{{ route('liquidaciones.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold">
            <i class="bi bi-cash-stack me-2"></i>
            Liquidaciones — {{ $tipoFiltro === 'punto_venta' ? 'Puntos de Venta (90%)' : 'Agentes de Parqueo (60%)' }}
        </span>
        <span class="text-muted small">{{ $liquidaciones->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Periodo</th>
                    <th>{{ $tipoFiltro === 'punto_venta' ? 'Punto de Venta' : 'Agente' }}</th>
                    <th class="text-end">Monto Bruto</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-end">Monto Neto</th>
                    <th>Calculado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($liquidaciones as $liq)
                    <tr>
                        <td class="fw-semibold">
                            {{ \Carbon\Carbon::parse($liq->periodo_mes)->isoFormat('MMMM YYYY') }}
                        </td>
                        <td>
                            @if($tipoFiltro === 'punto_venta')
                                {{ $liq->puntoVenta?->perfilUsuario?->nombres_completos ?? '—' }}
                                <span class="text-muted small d-block">{{ $liq->puntoVenta?->codigo }}</span>
                            @else
                                {{ $liq->agente?->perfilUsuario?->nombres_completos ?? '—' }}
                                <span class="text-muted small d-block">{{ $liq->agente?->codigo }}</span>
                            @endif
                        </td>
                        <td class="text-end">$ {{ number_format($liq->monto_bruto, 2) }}</td>
                        <td class="text-center">{{ $liq->porcentaje }} %</td>
                        <td class="text-end fw-semibold">$ {{ number_format($liq->monto_neto, 2) }}</td>
                        <td>{{ $liq->created_at?->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            No se encontraron liquidaciones para los filtros aplicados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($liquidaciones->hasPages())
        <div class="card-footer bg-transparent">
            {{ $liquidaciones->links() }}
        </div>
    @endif
</div>

@endsection
