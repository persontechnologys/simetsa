@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('tarifas.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-3 mb-lg-0">
        @can('tarifas.crear')
            <a href="{{ route('tarifas.create') }}" class="btn btn-link text-dark px-0 py-2 d-flex align-items-center text-decoration-none">
                <i class="bi bi-plus-lg me-2"></i> Nueva tarifa
            </a>
        @endcan
    </div>
@endsection

@section('content')

    {{-- Alert informativo --}}
    <div class="alert alert-info small mb-3 border-0 shadow-sm rounded-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Historial de tarifas.</strong> Cada tipo de plaza tiene un historial con su rango de vigencia. La tarifa <strong>vigente</strong> es la que se aplica a los tickets emitidos hoy. Las tarifas <strong>expiradas</strong> se conservan para reportes históricos y no se eliminan.
    </div>

    {{-- Tarjetas agrupadas por Tipo de Plaza --}}
    @foreach($tiposPlaza as $tipo)
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-transparent border-bottom pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 text-uppercase fw-bold text-muted title-tracking">
                    <span class="d-inline-block rounded-circle me-2"
                          style="width: 14px; height: 14px; background-color: {{ $tipo->color_mapa }}; vertical-align: middle;"></span>
                    @if($tipo->icono)<i class="bi {{ $tipo->icono }} me-2"></i>@endif
                    {{ $tipo->nombre }}
                </h2>
                <div class="small">
                    @if($tipo->es_pagado)
                        <span class="badge bg-warning text-dark">Tarifado</span>
                    @else
                        <span class="badge bg-success">Exonerado</span>
                    @endif
                </div>
            </div>

            @if($tipo->tarifas->isEmpty())
                <div class="card-body text-muted small text-center py-4">
                    <i class="bi bi-inbox d-block mb-1 opacity-50"></i>
                    Sin tarifas registradas para este tipo de plaza
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="small text-uppercase text-muted border-bottom">
                            <tr>
                                <th class="ps-4 fw-semibold">Nombre</th>
                                <th class="fw-semibold text-end">Valor/hora</th>
                                <th class="fw-semibold">Vigente desde</th>
                                <th class="fw-semibold">Vigente hasta</th>
                                <th class="fw-semibold text-center">Estado</th>
                                <th class="text-end pe-4 fw-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tipo->tarifas as $t)
                                <tr class="border-bottom">
                                    {{-- Nombre y Descripción --}}
                                    <td class="ps-4">
                                        <span class="fw-semibold">{{ $t->nombre }}</span>
                                        @if($t->descripcion)
                                            <span class="text-muted small d-block fs-7">{{ Str::limit($t->descripcion, 60) }}</span>
                                        @endif
                                    </td>

                                    {{-- Valor por Hora --}}
                                    <td class="text-end">
                                        <strong class="fw-semibold">
                                            $ {{ number_format((float) $t->valor_hora, 2) }}
                                        </strong>
                                    </td>

                                    {{-- Vigente Desde --}}
                                    <td class="small text-secondary">{{ $t->vigente_desde?->format('d/m/Y') }}</td>

                                    {{-- Vigente Hasta --}}
                                    <td class="small text-secondary text-nowrap">
                                        {{ $t->vigente_hasta?->format('d/m/Y') ?? '∞ sin fin' }}
                                    </td>

                                    {{-- Estado --}}
                                    <td class="text-center">
                                        <span class="badge bg-{{ $t->color_badge }}">
                                            {{ $t->estado_etiqueta }}
                                        </span>
                                    </td>

                                    {{-- Acciones dropdown --}}
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-link text-secondary p-0 border-0"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                    aria-label="Acciones de la tarifa">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-1">
                                                @can('tarifas.editar')
                                                    <a href="{{ route('tarifas.edit', $t) }}" class="dropdown-item py-1 px-3">
                                                        <i class="bi bi-pencil me-2 text-muted"></i> Editar
                                                    </a>
                                                @endcan
                                                @if($t->trashed())
                                                    @can('tarifas.editar')
                                                        <hr class="dropdown-divider my-1">
                                                        <button type="button" class="dropdown-item py-1 px-3 text-success"
                                                                data-confirm data-action="restaurar" data-method="PATCH"
                                                                data-url="{{ route('tarifas.reactivar', $t) }}">
                                                            <i class="bi bi-arrow-counterclockwise me-2"></i> Restaurar
                                                        </button>
                                                    @endcan
                                                @else
                                                    @can('tarifas.eliminar')
                                                        <hr class="dropdown-divider my-1">
                                                        <button type="button" class="dropdown-item py-1 px-3 text-danger"
                                                                data-confirm data-action="eliminar" data-method="DELETE"
                                                                data-url="{{ route('tarifas.destroy', $t) }}"
                                                                data-msg="¿Eliminar la tarifa {{ $t->nombre }}? Quedará en estado soft-deleted.">
                                                            <i class="bi bi-trash me-2"></i> Eliminar
                                                        </button>
                                                    @endcan
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach

@endsection
