{{-- resources/views/credenciales-discapacidad/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('credenciales-discapacidad.index') }}
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('credenciales-discapacidad.index') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-1">Estado</label>
                <select name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($estados as $e)
                        <option value="{{ $e }}" @selected(request('estado') === $e)>
                            {{ ucfirst($e) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filtrar</button>
                @if(request('estado'))
                    <a href="{{ route('credenciales-discapacidad.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>N° CONADIS</th>
                    <th>Beneficiario</th>
                    <th>Conductor</th>
                    <th class="text-center">Discapacidad</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($credenciales as $c)
                    <tr>
                        <td class="fw-semibold">{{ $c->numero_conadis }}</td>
                        <td>{{ $c->nombre_beneficiario }}</td>
                        <td class="small text-muted">
                            {{ $c->conductor->user->name ?? '—' }}
                        </td>
                        <td class="text-center">{{ $c->porcentaje_discapacidad }}%</td>
                        <td class="small text-muted">{{ $c->fecha_emision?->format('d/m/Y') }}</td>
                        <td class="small text-muted">{{ $c->fecha_vencimiento?->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @switch($c->estado)
                                @case('pendiente')
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                    @break
                                @case('aprobada')
                                    <span class="badge bg-success">Aprobada</span>
                                    @break
                                @case('rechazada')
                                    <span class="badge bg-danger">Rechazada</span>
                                    @break
                                @case('vencida')
                                    <span class="badge bg-secondary">Vencida</span>
                                    @break
                                @default
                                    <span class="badge bg-light text-dark">{{ $c->estado }}</span>
                            @endswitch
                        </td>
                        <td class="text-end">
                            @can('credenciales_discapacidad.aprobar')
                                @if($c->estado === 'pendiente')
                                    <form method="POST" action="{{ route('credenciales-discapacidad.aprobar', $c) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Aprobar"
                                                data-confirm data-action="aprobar"
                                                data-msg="¿Aprobar la credencial CONADIS {{ $c->numero_conadis }}?">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger btn-rechazar-credencial"
                                            title="Rechazar"
                                            data-bs-toggle="modal" data-bs-target="#modalRechazar"
                                            data-id="{{ $c->id }}">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @endif
                            @endcan
                            @if($c->observaciones)
                                <span class="text-muted small ms-1" title="{{ $c->observaciones }}">
                                    <i class="bi bi-chat-left-text"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No hay credenciales CONADIS registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($credenciales->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $credenciales->links() }}</div>
    @endif
</div>

{{-- Modal rechazar credencial CONADIS --}}
@can('credenciales_discapacidad.aprobar')
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formRechazar" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title" id="modalRechazarLabel">Rechazar credencial CONADIS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label for="observacionesRechazar" class="form-label">Motivo del rechazo <span class="text-danger">*</span></label>
                <textarea id="observacionesRechazar" name="observaciones" class="form-control" rows="3"
                          placeholder="Ingrese el motivo..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Rechazar credencial</button>
            </div>
        </form>
    </div>
</div>
@endcan

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-rechazar-credencial').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.id;
            document.getElementById('formRechazar').action = '/credenciales-discapacidad/' + id + '/rechazar';
            document.getElementById('observacionesRechazar').value = '';
        });
    });
});
</script>
@endpush
@endsection
