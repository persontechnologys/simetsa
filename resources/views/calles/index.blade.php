@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('calles.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-3 mb-lg-0">
        @can('calles.crear')
            <a href="{{ route('calles.create') }}" class="btn btn-link text-dark px-0 py-2 d-flex align-items-center text-decoration-none">
                <i class="bi bi-plus-lg me-2"></i> Nueva calle
            </a>
        @endcan
    </div>
@endsection

@section('content')

    {{-- Filtro por zona --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('calles.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-4">
                    <label for="zona_id" class="form-label small text-secondary fw-medium mb-1">Zona</label>
                    <select name="zona_id" id="zona_id" class="form-select form-select-sm">
                        <option value="">Todas las zonas</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z->id }}" @selected((string) $zonaId === (string) $z->id)>{{ $z->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label for="activo" class="form-label small text-secondary fw-medium mb-1">Estado</label>
                    <select name="activo" id="activo" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="1" @selected(($filtros['activo'] ?? '') === '1')>Activas</option>
                        <option value="0" @selected(($filtros['activo'] ?? '') === '0')>Inactivas</option>
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="d-flex justify-content-lg-end gap-2">
                        <a href="{{ route('calles.index') }}" class="btn btn-sm btn-light text-secondary border">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
                        </a>
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-sliders me-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Mapa con polilíneas de calles + polígonos de zonas de fondo --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
            <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                <i class="bi bi-pin-map me-2 text-dark fs-5 align-middle"></i>Mapa de calles
            </h2>
        </div>
        <div class="card-body p-4">
            <div id="mapa-calles" style="height: 440px; border-radius: 0.5rem;" class="border"></div>
        </div>
    </div>

    {{-- Tabla de calles --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="small text-uppercase text-muted border-bottom">
                    <tr>
                        <th class="ps-4 fw-semibold">Calle</th>
                        <th class="fw-semibold">Tramo (Art. 16)</th>
                        <th class="fw-semibold">Zona</th>
                        <th class="fw-semibold">Sentido</th>
                        <th class="fw-semibold">Costado</th>
                        <th class="fw-semibold">Trazado</th>
                        <th class="fw-semibold">Estado</th>
                        <th class="text-end pe-4 fw-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($calles as $c)
                        <tr class="border-bottom">
                            {{-- Nombre y código --}}
                            <td class="ps-4">
                                <span class="fw-semibold">{{ $c->nombre }}</span>
                                <span class="text-muted small ms-2 font-monospace">{{ $c->codigo }}</span>
                            </td>

                            {{-- Tramo --}}
                            <td class="small text-secondary">
                                @if($c->desde || $c->hasta)
                                    {{ $c->desde ?? '—' }} <i class="bi bi-arrow-right text-muted" style="font-size: 0.75rem;"></i> {{ $c->hasta ?? '—' }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Zona con color --}}
                            <td>
                                <span class="badge text-dark text-nowrap" style="background-color: {{ $c->zona?->color ?? '#ccc' }};">
                                    {{ $c->zona?->nombre ?? '—' }}
                                </span>
                            </td>

                            {{-- Sentido --}}
                            <td class="small text-secondary text-nowrap">{{ $c->sentido_etiqueta }}</td>

                            {{-- Costado --}}
                            <td class="small text-secondary text-nowrap">{{ $c->lado_etiqueta }}</td>

                            {{-- Trazado --}}
                            <td class="text-nowrap">
                                @if($c->tieneGeometria())
                                    <span class="badge bg-success">{{ count($c->polilinea) }} puntos</span>
                                @else
                                    <span class="badge bg-warning text-dark">Sin trazar</span>
                                @endif
                            </td>

                            {{-- Estado: ícono + texto --}}
                            <td class="text-nowrap">
                                @if($c->activo)
                                    <span class="text-success">
                                        <i class="bi bi-circle-fill small me-1" style="font-size: 0.6rem;"></i> Activa
                                    </span>
                                @else
                                    <span class="text-secondary">
                                        <i class="bi bi-circle-fill small me-1" style="font-size: 0.6rem;"></i> Inactiva
                                    </span>
                                @endif
                            </td>

                            {{-- Acciones dropdown --}}
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-link text-secondary p-0 border-0"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false"
                                            aria-label="Acciones de la calle">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-1">
                                        @can('calles.editar')
                                            <a href="{{ route('calles.edit', $c) }}" class="dropdown-item py-1 px-3">
                                                <i class="bi bi-pencil me-2 text-muted"></i> Editar
                                            </a>
                                        @endcan
                                        @can('calles.eliminar')
                                            <hr class="dropdown-divider my-1">
                                            <button type="button" class="dropdown-item py-1 px-3 text-danger"
                                                    data-confirm data-action="eliminar" data-method="DELETE"
                                                    data-url="{{ route('calles.destroy', $c) }}">
                                                <i class="bi bi-trash me-2"></i> Eliminar
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-pin-map d-block mb-1 opacity-50"></i>
                                No se encontraron calles
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($calles->hasPages())
            <div class="card-footer bg-white border-top-0 pt-2 pb-2 px-4">
                {{ $calles->links() }}
            </div>
        @endif
    </div>

    @push('scriptsHeader')
    <script src="{{ asset('assets/js/vendor/maps/leaflet/leaflet.min.js') }}"></script>
    <script src="{{ asset('assets/js/vendor/maps/leaflet/plugins/markercluster.min.js') }}"></script>
    @endpush

    @push('scriptsFooter')
    <script>
    (function () {
        const calles = @json($callesMapa);
        const zonas  = @json($zonasMapa);

        const map = L.map('mapa-calles').setView([-1.0458, -78.5916], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap'
        }).addTo(map);

        const grupo = [];

        // Zonas de fondo (tenues)
        zonas.forEach(z => {
            if (z.poligono && z.poligono.length >= 3) {
                L.polygon(z.poligono, {
                    color: z.color, weight: 1, fillOpacity: 0.05, dashArray: '4 4', interactive: false
                }).addTo(map);
            }
        });

        // Polilíneas de calles
        calles.forEach(c => {
            if (c.polilinea && c.polilinea.length >= 2) {
                const linea = L.polyline(c.polilinea, { color: c.color, weight: 4 })
                               .addTo(map)
                               .bindPopup(c.nombre);
                grupo.push(linea);
            }
        });

        if (grupo.length) {
            try { map.fitBounds(L.featureGroup(grupo).getBounds().pad(0.2)); } catch (e) { /* noop */ }
        }
        setTimeout(() => map.invalidateSize(), 200);
    })();
    </script>
    @endpush

@endsection
