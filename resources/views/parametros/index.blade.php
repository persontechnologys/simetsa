{{-- resources/views/parametros/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('parametros.index') }}
@endsection

@section('content')

    {{-- Alert informativo --}}
    <div class="alert alert-info small mb-3 border-0 shadow-sm rounded-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Parámetros del sistema.</strong> Estos valores gobiernan el comportamiento del SIMETSA y derivan de la Ordenanza vigente. Se aplican automáticamente a tickets, multas y liquidaciones. Los cambios quedan registrados con timestamp y usuario.
    </div>

    {{-- Tarjetas agrupadas por Categoría --}}
    @foreach($parametrosPorCategoria as $categoria => $grupo)
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-transparent border-bottom pt-4 px-4 pb-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 text-uppercase fw-bold text-muted title-tracking">
                    @switch($categoria)
                        @case('institucion')    <i class="bi bi-building me-2 text-dark fs-5"></i>Institución @break
                        @case('operacion')      <i class="bi bi-gear me-2 text-dark fs-5"></i>Operación @break
                        @case('agentes')        <i class="bi bi-person me-2 text-dark fs-5"></i>Agentes de parqueo @break
                        @case('puntos_venta')   <i class="bi bi-shop me-2 text-dark fs-5"></i>Puntos de venta @break
                        @case('app_movil')      <i class="bi bi-phone me-2 text-dark fs-5"></i>Aplicación móvil @break
                        @case('liquidaciones')  <i class="bi bi-cash-coin me-2 text-dark fs-5"></i>Liquidaciones @break
                        @case('multas')         <i class="bi bi-exclamation-circle me-2 text-dark fs-5"></i>Multas y sanciones @break
                        @case('sanciones')      <i class="bi bi-shield-lock me-2 text-dark fs-5"></i>Sanciones administrativas @break
                        @default                <i class="bi bi-tag me-2 text-dark fs-5"></i>{{ ucfirst($categoria) }}
                    @endswitch
                </h2>
                <span class="badge bg-secondary">{{ $grupo->count() }} parámetros</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="small text-uppercase text-muted border-bottom">
                        <tr>
                            <th class="ps-4 fw-semibold">Clave</th>
                            <th class="fw-semibold">Descripción</th>
                            <th class="fw-semibold">Valor</th>
                            <th class="fw-semibold">Artículo</th>
                            <th class="fw-semibold">Última modificación</th>
                            <th class="text-end pe-4 fw-semibold">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grupo as $p)
                            <tr class="border-bottom">
                                <td class="ps-4">
                                    <code class="small font-monospace">{{ $p->clave }}</code>
                                </td>
                                <td class="small text-secondary">{{ $p->descripcion ?? '—' }}</td>
                                <td>
                                    <strong class="fw-semibold">{{ $p->valor_formateado }}</strong>
                                </td>
                                <td>
                                    @if($p->articulo_ordenanza)
                                        <span class="badge bg-light text-dark border fs-7">{{ $p->articulo_ordenanza }}</span>
                                    @endif
                                </td>
                                <td class="small text-secondary">
                                    @if($p->ultimaBitacora)
                                        <div class="fw-semibold">{{ $p->ultimaBitacora->ocurrido_en?->format('d/m/Y H:i') }}</div>
                                        <div class="text-muted fs-7">
                                            <i class="bi bi-person"></i>
                                            {{ $p->ultimaBitacora->user?->name ?? 'Sistema' }}
                                        </div>
                                    @else
                                        <span class="text-muted">— Sin cambios —</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @can('parametros.editar')
                                        @if($p->editable)
                                            <a href="{{ route('parametros.edit', $p) }}"
                                               class="btn btn-link text-secondary p-0 border-0"
                                               title="Editar parámetro">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @else
                                            <span class="text-muted" title="Parámetro bloqueado">
                                                <i class="bi bi-lock"></i>
                                            </span>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

@endsection
