{{-- resources/views/ordenes-pago/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('ordenes-pago.show', $ordenPago) }}
@endsection

@section('content')

<div class="row g-3">
    {{-- Datos principales --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-receipt me-2"></i>{{ $ordenPago->numero_orden }}
                </span>
                <span class="badge bg-{{ $ordenPago->estado->claseBadge() }} fs-6">
                    {{ $ordenPago->estado->etiqueta() }}
                </span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Número de orden</dt>
                    <dd class="col-sm-8 fw-semibold">{{ $ordenPago->numero_orden }}</dd>

                    <dt class="col-sm-4">Monto</dt>
                    <dd class="col-sm-8">$ {{ number_format($ordenPago->monto, 2) }}</dd>

                    <dt class="col-sm-4">Estado</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-{{ $ordenPago->estado->claseBadge() }}">
                            {{ $ordenPago->estado->etiqueta() }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Vence</dt>
                    <dd class="col-sm-8">{{ $ordenPago->vence_at?->format('d/m/Y H:i') ?? '—' }}</dd>

                    <dt class="col-sm-4">Generada por</dt>
                    <dd class="col-sm-8">{{ $ordenPago->generadaPor?->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Fecha generación</dt>
                    <dd class="col-sm-8">{{ $ordenPago->created_at?->format('d/m/Y H:i') }}</dd>

                    @if($ordenPago->motivo_anulacion)
                        <dt class="col-sm-4">Motivo anulación</dt>
                        <dd class="col-sm-8 text-danger">{{ $ordenPago->motivo_anulacion }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Datos de la infracción --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-exclamation-triangle me-2"></i>Infracción asociada
            </div>
            <div class="card-body small">
                @if($ordenPago->infraccion)
                    <dl class="row mb-0">
                        <dt class="col-5">Placa</dt>
                        <dd class="col-7 fw-semibold">{{ $ordenPago->infraccion->placa }}</dd>

                        <dt class="col-5">Tipo</dt>
                        <dd class="col-7">{{ $ordenPago->infraccion->tipo_infraccion?->value }}</dd>

                        <dt class="col-5">Multa</dt>
                        <dd class="col-7">$ {{ number_format($ordenPago->infraccion->monto_multa, 2) }}</dd>

                        <dt class="col-5">Agente</dt>
                        <dd class="col-7">
                            {{ $ordenPago->infraccion->agente?->perfilUsuario?->nombres_completos ?? '—' }}
                        </dd>
                    </dl>
                    <a href="{{ route('infracciones.show', $ordenPago->infraccion) }}"
                       class="btn btn-sm btn-outline-secondary mt-2">
                        Ver infracción
                    </a>
                @else
                    <p class="text-muted mb-0">Sin infracción vinculada.</p>
                @endif
            </div>
        </div>

        {{-- Acción anular --}}
        @can('ordenes_pago.generar')
            @if($ordenPago->estado->value === 'pendiente')
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-transparent fw-semibold text-danger">
                        <i class="bi bi-x-circle me-2"></i>Anular orden
                    </div>
                    <div class="card-body">
                        <form method="POST"
                              action="{{ route('ordenes-pago.anular', $ordenPago) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Motivo de anulación</label>
                                <textarea name="motivo_anulacion" rows="3"
                                          class="form-control form-control-sm @error('motivo_anulacion') is-invalid @enderror"
                                          required maxlength="500"
                                          placeholder="Explique el motivo...">{{ old('motivo_anulacion') }}</textarea>
                                @error('motivo_anulacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit"
                                    class="btn btn-sm btn-danger"
                                    data-confirm
                                    data-action="anular"
                                    data-msg="¿Anular la orden {{ $ordenPago->numero_orden }}?">
                                Anular orden
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan
    </div>
</div>

@endsection
