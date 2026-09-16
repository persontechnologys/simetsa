@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('vehiculos-exonerados.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-2 mb-lg-0">
        @can('vehiculos_exonerados.crear')
            <a href="{{ route('vehiculos-exonerados.create') }}" class="d-flex align-items-center text-body py-2">
                <i class="ph ph-plus me-1"></i> Registrar vehículo exonerado
            </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Placa</th>
                    <th>Institución</th>
                    <th>Tipo</th>
                    <th>Funcionario</th>
                    <th>Tiempo máx.</th>
                    <th>Activo</th>
                    <th>Fecha registro</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehiculos as $v)
                    <tr>
                        <td><strong>{{ $v->placa }}</strong></td>
                        <td>{{ $v->institucion }}</td>
                        <td><span class="badge bg-info text-dark">{{ $tipos[$v->tipo_exoneracion] ?? $v->tipo_exoneracion }}</span></td>
                        <td class="text-muted small">{{ $v->nombre_funcionario ?? '—' }}</td>
                        <td class="text-center">{{ $v->tiempo_maximo_horas }}h</td>
                        <td>
                            @if($v->activo)
                                <span class="badge bg-success">Sí</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $v->fecha_registro?->format('d/m/Y') }}</td>
                        <td class="text-end">
                            @can('vehiculos_exonerados.editar')
                                <a href="{{ route('vehiculos-exonerados.edit', $v) }}"
                                   class="btn btn-sm btn-outline-primary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('vehiculos-exonerados.toggle', $v) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                            class="btn btn-sm {{ $v->activo ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                            title="{{ $v->activo ? 'Suspender' : 'Activar' }}"
                                            data-confirm
                                            data-action="{{ $v->activo ? 'suspender' : 'activar' }}"
                                            data-msg="{{ $v->activo ? '¿Suspender la exoneración de la placa '.$v->placa.'?' : '¿Activar la exoneración de la placa '.$v->placa.'?' }}">
                                        <i class="bi bi-{{ $v->activo ? 'pause-circle' : 'play-circle' }}"></i>
                                    </button>
                                </form>
                            @endcan
                            @can('vehiculos_exonerados.eliminar')
                                <form method="POST" action="{{ route('vehiculos-exonerados.destroy', $v) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Eliminar"
                                            data-confirm
                                            data-action="eliminar"
                                            data-msg="¿Eliminar la exoneración de la placa {{ $v->placa }}?">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay vehículos exonerados registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($vehiculos->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $vehiculos->links() }}</div>
    @endif
</div>
@endsection
