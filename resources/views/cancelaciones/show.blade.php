{{-- resources/views/cancelaciones/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('cancelaciones.show', $cancelacion) }}
@endsection

@section('content')

<div class="row g-3">
    {{-- Datos de la cancelación --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-x-circle me-2"></i>Cancelación #{{ $cancelacion->id }}
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-5">Tipo</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $cancelacion->tipo === \App\Enums\TipoCancelacion::Admin ? 'warning text-dark' : 'secondary' }}">
                            {{ $cancelacion->tipo->etiqueta() }}
                        </span>
                    </dd>

                    <dt class="col-5">Cancelado por</dt>
                    <dd class="col-7">{{ $cancelacion->canceladoPorUsuario?->name ?? '—' }}</dd>

                    <dt class="col-5">Motivo</dt>
                    <dd class="col-7">{{ $cancelacion->motivo ?: '—' }}</dd>

                    <dt class="col-5">Reembolso</dt>
                    <dd class="col-7">${{ number_format($cancelacion->monto_reembolsado, 2) }}</dd>

                    <dt class="col-5">Estado reembolso</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $cancelacion->estado_reembolso->color() }}">
                            {{ $cancelacion->estado_reembolso->etiqueta() }}
                        </span>
                    </dd>

                    <dt class="col-5">Fecha</dt>
                    <dd class="col-7">{{ $cancelacion->cancelado_en?->format('d/m/Y H:i:s') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Datos del ticket cancelado --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-ticket-perforated me-2"></i>Ticket asociado
            </div>
            <div class="card-body">
                @if($cancelacion->ticket)
                    <dl class="row mb-0 small">
                        <dt class="col-5">Código</dt>
                        <dd class="col-7">
                            <a href="{{ route('tickets.show', $cancelacion->ticket) }}">
                                <code>{{ $cancelacion->ticket->codigo }}</code>
                            </a>
                        </dd>

                        <dt class="col-5">Placa</dt>
                        <dd class="col-7"><strong>{{ $cancelacion->ticket->placa }}</strong></dd>

                        <dt class="col-5">Estado</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $cancelacion->ticket->estado->color() }}">
                                {{ $cancelacion->ticket->estado->etiqueta() }}
                            </span>
                        </dd>

                        <dt class="col-5">Conductor</dt>
                        <dd class="col-7">
                            {{ $cancelacion->ticket->conductor?->user?->name ?? '—' }}
                        </dd>

                        <dt class="col-5">Monto pagado</dt>
                        <dd class="col-7">${{ number_format($cancelacion->ticket->monto_total ?? 0, 2) }}</dd>

                        <dt class="col-5">Comprado el</dt>
                        <dd class="col-7">{{ $cancelacion->ticket->created_at?->format('d/m/Y H:i') }}</dd>
                    </dl>
                @else
                    <p class="text-muted small mb-0">Ticket no disponible.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ route('cancelaciones.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Volver al listado
    </a>
</div>

@endsection
