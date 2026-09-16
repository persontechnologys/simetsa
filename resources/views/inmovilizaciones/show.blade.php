{{-- resources/views/inmovilizaciones/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('inmovilizaciones.show', $inmovilizacion) }}
@endsection

@section('content')

<div class="row g-3">

    {{-- Datos de la inmovilización --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-lock me-2"></i>Inmovilización #{{ $inmovilizacion->id }}
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5">Estado</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $inmovilizacion->estado->color() }}">
                            {{ $inmovilizacion->estado->etiqueta() }}
                        </span>
                    </dd>

                    <dt class="col-5">Agente</dt>
                    <dd class="col-7">
                        {{ $inmovilizacion->agente?->codigo ?? '—' }}
                        {{ $inmovilizacion->agente?->user?->name ? '— ' . $inmovilizacion->agente->user->name : '' }}
                    </dd>

                    <dt class="col-5">Inmovilizada</dt>
                    <dd class="col-7">{{ $inmovilizacion->inmovilizada_en?->format('d/m/Y H:i:s') }}</dd>

                    <dt class="col-5">Liberada</dt>
                    <dd class="col-7">{{ $inmovilizacion->liberada_en?->format('d/m/Y H:i:s') ?? '—' }}</dd>

                    @if($inmovilizacion->notas)
                    <dt class="col-5">Notas</dt>
                    <dd class="col-7">{{ $inmovilizacion->notas }}</dd>
                    @endif

                    @if($inmovilizacion->anuladaPor)
                    <dt class="col-5">Anulada por</dt>
                    <dd class="col-7">{{ $inmovilizacion->anuladaPor->name }}</dd>
                    @endif
                </dl>
            </div>

            {{-- Acción de liberación administrativa --}}
            @if($inmovilizacion->estaActiva())
            @can('inmovilizaciones.retirar')
            <div class="card-footer bg-transparent">
                <button class="btn btn-sm btn-warning"
                        data-bs-toggle="collapse"
                        data-bs-target="#formLiberar">
                    <i class="bi bi-unlock me-1"></i>Liberar administrativamente
                </button>

                <div class="collapse mt-3" id="formLiberar">
                    <form method="POST"
                          action="{{ route('inmovilizaciones.liberar', $inmovilizacion) }}">
                        @csrf
                        @method('PATCH')

                        <div class="mb-2">
                            <label for="motivo" class="form-label small fw-semibold">
                                Motivo de liberación administrativa
                            </label>
                            <textarea class="form-control form-control-sm @error('motivo') is-invalid @enderror"
                                      id="motivo" name="motivo" rows="3"
                                      placeholder="Describa el motivo de la liberación forzada (mínimo 10 caracteres)">{{ old('motivo') }}</textarea>
                            @error('motivo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-sm btn-warning"
                                data-confirm
                                data-action="liberar"
                                data-msg="¿Liberar el vehículo inmovilizado #{{ $inmovilizacion->id }}? Esta acción no se puede deshacer.">
                            <i class="bi bi-unlock me-1"></i>Confirmar liberación
                        </button>
                    </form>
                </div>
            </div>
            @endcan
            @endif
        </div>
    </div>

    {{-- Datos de la infracción asociada --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-exclamation-triangle me-2"></i>Infracción asociada
            </div>
            <div class="card-body">
                @if($inmovilizacion->infraccion)
                    <dl class="row mb-0 small">
                        <dt class="col-5">ID</dt>
                        <dd class="col-7">
                            <a href="{{ route('infracciones.show', $inmovilizacion->infraccion) }}">
                                <code>#{{ $inmovilizacion->infraccion_id }}</code>
                            </a>
                        </dd>

                        <dt class="col-5">Placa</dt>
                        <dd class="col-7"><strong>{{ $inmovilizacion->infraccion->placa }}</strong></dd>

                        <dt class="col-5">Tipo</dt>
                        <dd class="col-7">{{ $inmovilizacion->infraccion->tipo_infraccion->etiqueta() }}</dd>

                        <dt class="col-5">Estado</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $inmovilizacion->infraccion->estado->color() }}">
                                {{ $inmovilizacion->infraccion->estado->etiqueta() }}
                            </span>
                        </dd>

                        <dt class="col-5">Zona</dt>
                        <dd class="col-7">{{ $inmovilizacion->infraccion->zona?->nombre ?? '—' }}</dd>

                        <dt class="col-5">Multa</dt>
                        <dd class="col-7">${{ number_format($inmovilizacion->infraccion->monto_multa, 2) }}</dd>
                    </dl>

                    {{-- Transacciones de pago --}}
                    @if($inmovilizacion->infraccion->transacciones->isNotEmpty())
                    <hr class="my-2">
                    <p class="small fw-semibold mb-1">Transacciones de pago</p>
                    <ul class="list-unstyled small mb-0">
                        @foreach($inmovilizacion->infraccion->transacciones as $tx)
                        <li class="d-flex justify-content-between border-bottom py-1">
                            <span>{{ $tx->proveedor->value }} — {{ $tx->created_at?->format('d/m/Y') }}</span>
                            <span>
                                <span class="badge bg-{{ $tx->estado->color() }}">{{ $tx->estado->etiqueta() }}</span>
                                ${{ number_format($tx->monto, 2) }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                @else
                    <p class="text-muted small mb-0">Infracción no disponible.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('inmovilizaciones.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver al listado
    </a>
</div>

@endsection
