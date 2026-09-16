{{-- resources/views/turnos/show.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
{{ Breadcrumbs::render('turnos.show', $turno) }}
@endsection

@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
@endpush

@section('content')

<div class="row g-4">

    {{-- Datos del turno --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-person-badge me-2"></i>Datos del turno
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Agente</dt>
                    <dd class="col-7 fw-semibold">{{ $turno->agente?->codigo ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Inicio</dt>
                    <dd class="col-7">{{ $turno->inicio_at?->format('d/m/Y H:i') }}</dd>

                    <dt class="col-5 text-muted">Fin</dt>
                    <dd class="col-7">{{ $turno->fin_at?->format('d/m/Y H:i') ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Duración</dt>
                    <dd class="col-7">
                        @if($turno->estaActivo())
                            <span class="text-success fw-semibold">
                                {{ $turno->minutosTranscurridos() }} min (activo)
                            </span>
                        @else
                            {{ $turno->duracionMinutos() ?? '—' }} min
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Estado</dt>
                    <dd class="col-7">
                        <span class="badge bg-{{ $turno->estado?->color() }}">
                            {{ $turno->estado?->etiqueta() }}
                        </span>
                    </dd>

                    <dt class="col-5 text-muted">Puntos GPS</dt>
                    <dd class="col-7">{{ $turno->recorridos->count() }}</dd>

                    @if($turno->observaciones)
                        <dt class="col-5 text-muted">Observaciones</dt>
                        <dd class="col-7">{{ $turno->observaciones }}</dd>
                    @endif
                </dl>
            </div>
            <div class="card-footer bg-transparent">
                <a href="{{ route('turnos.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Volver
                </a>
            </div>
        </div>
    </div>

    {{-- Mapa del recorrido --}}
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-map me-2"></i>Recorrido GPS
            </div>
            <div class="card-body p-0">
                <div id="mapaRecorrido" style="height: 380px; border-radius: 0 0 .5rem .5rem;"></div>
            </div>
        </div>
    </div>

    {{-- Incidentes --}}
    @if($turno->incidentes->isNotEmpty())
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                Incidentes reportados ({{ $turno->incidentes->count() }})
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Ubicación</th>
                            <th>ECU 911</th>
                            <th>Notificado</th>
                            <th>Evidencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($turno->incidentes as $inc)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $inc->tipo?->color() }}">
                                        {{ $inc->tipo?->etiqueta() }}
                                    </span>
                                </td>
                                <td>{{ $inc->descripcion }}</td>
                                <td class="text-muted">
                                    @if($inc->latitud && $inc->longitud)
                                        {{ number_format($inc->latitud, 5) }},
                                        {{ number_format($inc->longitud, 5) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($inc->reportado_ecu911)
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @else
                                        <i class="bi bi-x-circle text-danger"></i>
                                    @endif
                                </td>
                                <td>{{ $inc->notificado_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>
                                    @if($inc->foto_evidencia)
                                        <a href="{{ asset('storage/' . $inc->foto_evidencia) }}"
                                           target="_blank" class="btn btn-sm btn-outline-secondary" title="Ver foto">
                                            <i class="bi bi-image"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

@endsection

@push('scriptsFooter')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const puntos = @json($puntosGps);

    const centroEcuador = [-1.8312, -78.1834];
    const zoom = puntos.length > 0 ? 16 : 9;
    const centro = puntos.length > 0 ? puntos[0] : centroEcuador;

    const mapa = L.map('mapaRecorrido').setView(centro, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19,
    }).addTo(mapa);

    if (puntos.length > 0) {
        // Trazar la polilínea del recorrido
        const linea = L.polyline(puntos, { color: '#0d6efd', weight: 3, opacity: 0.8 }).addTo(mapa);
        mapa.fitBounds(linea.getBounds(), { padding: [20, 20] });

        // Marcador de inicio
        L.marker(puntos[0])
            .addTo(mapa)
            .bindPopup('Inicio del turno');

        // Marcador de fin (si el turno está cerrado)
        @if($turno->fin_at && count($puntosGps) > 1)
        L.marker(puntos[puntos.length - 1])
            .addTo(mapa)
            .bindPopup('Fin del turno');
        @endif

        // Marcadores de incidentes con coordenadas
        @foreach($turno->incidentes as $inc)
            @if($inc->latitud && $inc->longitud)
            L.circleMarker([{{ $inc->latitud }}, {{ $inc->longitud }}], {
                radius: 8, color: '#dc3545', fillColor: '#dc3545', fillOpacity: 0.8
            }).addTo(mapa).bindPopup('Incidente: {{ addslashes($inc->tipo?->etiqueta()) }}');
            @endif
        @endforeach
    } else {
        mapa.setView(centroEcuador, 9);
        const div = document.getElementById('mapaRecorrido');
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'center';
        div.innerHTML = '<p class="text-muted mb-0" style="z-index:999;position:relative">Sin datos GPS para este turno.</p>';
    }
});
</script>
@endpush
