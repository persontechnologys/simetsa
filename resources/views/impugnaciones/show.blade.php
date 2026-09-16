{{-- resources/views/impugnaciones/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('impugnaciones.show', $impugnacion) }}
@endsection

@section('content')

<div class="row g-3">

    {{-- Columna principal --}}
    <div class="col-lg-8">

        {{-- Datos de la impugnación --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-shield-exclamation me-2"></i>
                    Impugnación #{{ $impugnacion->id }}
                </span>
                <span class="badge bg-{{ $impugnacion->colorBadge() }} fs-6">
                    {{ ucfirst($impugnacion->estado) }}
                </span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Infracción</dt>
                    <dd class="col-sm-8">
                        <a href="{{ route('infracciones.show', $impugnacion->infraccion_id) }}">
                            #{{ $impugnacion->infraccion_id }}
                            — {{ $impugnacion->infraccion?->tipo_infraccion?->etiqueta() ?? '—' }}
                        </a>
                    </dd>

                    <dt class="col-sm-4">Placa</dt>
                    <dd class="col-sm-8"><strong>{{ $impugnacion->infraccion?->placa ?? '—' }}</strong></dd>

                    <dt class="col-sm-4">Zona</dt>
                    <dd class="col-sm-8">{{ $impugnacion->infraccion?->zona?->nombre ?? '—' }}</dd>

                    <dt class="col-sm-4">Agente</dt>
                    <dd class="col-sm-8">{{ $impugnacion->infraccion?->agente?->codigo ?? '—' }}</dd>

                    <dt class="col-sm-4">Conductor</dt>
                    <dd class="col-sm-8">
                        {{ $impugnacion->conductor?->user?->perfil?->nombres_completos ?? '—' }}
                    </dd>

                    <dt class="col-sm-4">Presentada</dt>
                    <dd class="col-sm-8">{{ $impugnacion->created_at?->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>

        {{-- Motivo --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent">
                <span class="fw-semibold"><i class="bi bi-chat-text me-2"></i>Motivo de la impugnación</span>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;">{{ $impugnacion->motivo }}</p>
            </div>
        </div>

        {{-- Resolución (si existe) --}}
        @if($impugnacion->resolucion)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent">
                <span class="fw-semibold"><i class="bi bi-check-circle me-2"></i>Resolución</span>
            </div>
            <div class="card-body">
                <p class="mb-2" style="white-space: pre-wrap;">{{ $impugnacion->resolucion }}</p>
                <small class="text-muted">
                    Resuelto por <strong>{{ $impugnacion->resolutor?->name ?? '—' }}</strong>
                    el {{ $impugnacion->resuelto_at?->format('d/m/Y H:i') }}
                </small>
            </div>
        </div>
        @endif

    </div>

    {{-- Panel de acciones --}}
    <div class="col-lg-4">
        @can('impugnaciones.resolver')

        {{-- Admitir --}}
        @if($impugnacion->estado === 'pendiente')
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold">Admitir impugnación</div>
            <div class="card-body">
                <p class="small text-muted mb-3">
                    Acepta la impugnación para análisis formal. El conductor verá el estado
                    actualizado en la app.
                </p>
                <form method="POST" action="{{ route('impugnaciones.admitir', $impugnacion) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-info w-100"
                            data-confirm
                            data-action="admitir"
                            data-msg="¿Admitir esta impugnación para análisis?">
                        <i class="bi bi-check2-circle me-1"></i> Admitir
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Rechazar --}}
        @if(in_array($impugnacion->estado, ['pendiente', 'admitida']))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold">Rechazar impugnación</div>
            <div class="card-body">
                <form method="POST" action="{{ route('impugnaciones.rechazar', $impugnacion) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label small">Resolución del rechazo <span class="text-danger">*</span></label>
                        <textarea name="resolucion" class="form-control form-control-sm @error('resolucion') is-invalid @enderror"
                                  rows="4" placeholder="Fundamento legal o técnico del rechazo..."
                                  required minlength="10">{{ old('resolucion') }}</textarea>
                        @error('resolucion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-x-circle me-1"></i> Rechazar
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Resolver a favor --}}
        @if($impugnacion->esResoluble())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-transparent fw-semibold">Resolver a favor del conductor</div>
            <div class="card-body">
                <form method="POST" action="{{ route('impugnaciones.resolver', $impugnacion) }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label small">Resolución favorable <span class="text-danger">*</span></label>
                        <textarea name="resolucion" class="form-control form-control-sm @error('resolucion') is-invalid @enderror"
                                  rows="4" placeholder="Fundamento de la resolución favorable..."
                                  required minlength="10">{{ old('resolucion') }}</textarea>
                        @error('resolucion')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle me-1"></i> Resolver favorablemente
                    </button>
                </form>
            </div>
        </div>
        @endif

        @endcan

        {{-- Enlace volver --}}
        <a href="{{ route('impugnaciones.index') }}" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left me-1"></i> Volver al listado
        </a>
    </div>

</div>

@endsection
