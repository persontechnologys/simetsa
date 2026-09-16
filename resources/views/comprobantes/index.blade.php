{{-- resources/views/comprobantes/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('comprobantes.index') }}
@endsection

@section('content')

{{-- Filtros --}}
<form class="row g-2 mb-3" method="GET" action="{{ route('comprobantes.index') }}">
    <div class="col-sm-6 col-md-3">
        <select name="tipo" class="form-select form-select-sm">
            <option value="">Todos los conceptos</option>
            <option value="ticket"     {{ $tipoFiltro === 'ticket' ? 'selected' : '' }}>Tickets</option>
            <option value="infraccion" {{ $tipoFiltro === 'infraccion' ? 'selected' : '' }}>Infracciones</option>
        </select>
    </div>
    <div class="col-sm-6 col-md-2">
        <input type="date" name="desde" class="form-control form-control-sm"
               value="{{ $desde }}" title="Desde">
    </div>
    <div class="col-sm-6 col-md-2">
        <input type="date" name="hasta" class="form-control form-control-sm"
               value="{{ $hasta }}" title="Hasta">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        <a href="{{ route('comprobantes.index') }}" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-file-earmark-check me-2"></i>Comprobantes de Pago</span>
        <span class="text-muted small">{{ $comprobantes->total() }} registro(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr>
                    <th>Número</th>
                    <th>Concepto</th>
                    <th>Referencia</th>
                    <th class="text-end">Monto</th>
                    <th>Fecha emisión</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($comprobantes as $cb)
                    <tr>
                        <td class="fw-semibold">{{ $cb->numero }}</td>
                        <td>
                            @if($cb->concepto_type === \App\Models\Ticket::class)
                                <span class="badge bg-primary">Ticket</span>
                            @else
                                <span class="badge bg-warning text-dark">Infracción</span>
                            @endif
                        </td>
                        <td>
                            @if($cb->concepto)
                                {{ $cb->concepto->descripcionCobro() }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">$ {{ number_format($cb->monto, 2) }}</td>
                        <td>{{ $cb->fecha_emision?->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('api.comprobantes.pdf', $cb) }}"
                               class="btn btn-sm btn-outline-secondary" target="_blank" title="Ver PDF">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            No se encontraron comprobantes.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($comprobantes->hasPages())
        <div class="card-footer bg-transparent">
            {{ $comprobantes->links() }}
        </div>
    @endif
</div>

@endsection
