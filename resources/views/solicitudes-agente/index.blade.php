{{-- resources/views/solicitudes-agente/index.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('solicitudes-agente.index') }}
@endsection


@section('breadcrumb_elements')
    <div class="d-lg-flex mb-2 mb-lg-0">
        @can('agentes.crear')
            <a href="{{ route('solicitudes-agente.create') }}" class="d-flex align-items-center text-body py-2">
                <i class="ph-lifebuoy me-2"></i>
                Nueva solicitud
            </a>    
        @endcan
        
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-3"><div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="estado" class="form-label small mb-1">Estado</label>
            <select name="estado" id="estado" class="form-select">
                <option value="">Todos</option>
                @foreach($estados as $clave => $meta)
                    <option value="{{ $clave }}" @selected($estado === $clave)>{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-simetsa flex-grow-1"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="{{ route('solicitudes-agente.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</div></div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Folio</th><th>Postulante</th><th>Cédula</th><th>Edad</th>
                    <th>Estado</th><th>Solicitud</th><th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($solicitudes as $s)
                    <tr>
                        <td><code>{{ $s->codigo }}</code></td>
                        <td>{{ $s->nombre_completo }}</td>
                        <td>{{ $s->cedula }}</td>
                        <td>{{ $s->edad }}</td>
                        <td><span class="badge bg-{{ $s->estado_color }}">{{ $s->estado_label }}</span></td>
                        <td class="small">{{ $s->fecha_solicitud?->format('d/m/Y') }}</td>
                        <td class="text-end">
                            @can('agentes.ver')
                                <a href="{{ route('solicitudes-agente.show', $s) }}" class="btn btn-sm btn-outline-secondary" title="Ver"><i class="bi bi-eye"></i></a>
                            @endcan
                            @can('agentes.editar')
                                <a href="{{ route('solicitudes-agente.edit', $s) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('agentes.eliminar')
                                <form method="POST" action="{{ route('solicitudes-agente.destroy', $s) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar la solicitud {{ $s->codigo }}?')"><i class="bi bi-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay solicitudes con este filtro.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($solicitudes->hasPages())
        <div class="card-footer bg-white border-top-0">{{ $solicitudes->links() }}</div>
    @endif
</div>
@endsection