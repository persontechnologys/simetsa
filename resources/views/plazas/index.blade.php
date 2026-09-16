@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('plazas.index') }}
@endsection

@section('breadcrumb_elements')
    <div class="d-lg-flex mb-3 mb-lg-0">
        @can('plazas.crear')
            <a href="{{ route('plazas.create') }}" class="btn btn-link text-dark px-0 py-2 d-flex align-items-center text-decoration-none">
                <i class="bi bi-plus-lg me-2"></i> Nueva plaza
            </a>
        @endcan
    </div>
@endsection

@section('content')

    {{-- Filtros --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('plazas.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="zona_id" class="form-label small text-secondary fw-medium mb-1">Zona</label>
                    <select name="zona_id" id="zona_id" class="form-select form-select-sm">
                        <option value="">Todas las zonas</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z->id }}" @selected((string)($filtros['zona_id'] ?? '') === (string)$z->id)>{{ $z->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="tipo_plaza_id" class="form-label small text-secondary fw-medium mb-1">Tipo de plaza</label>
                    <select name="tipo_plaza_id" id="tipo_plaza_id" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        @foreach($tiposPlaza as $t)
                            <option value="{{ $t->id }}" @selected((string)($filtros['tipo_plaza_id'] ?? '') === (string)$t->id)>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="d-flex justify-content-lg-end gap-2">
                        <a href="{{ route('plazas.index') }}" class="btn btn-sm btn-light text-secondary border">
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

    {{-- Mapa --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
            <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                <i class="bi bi-pin-map me-2 text-dark fs-5 align-middle"></i>Mapa de plazas
            </h2>
        </div>
        <div class="card-body p-4">
            <div id="mapa-plazas" style="height: 440px; border-radius: 0.5rem;" class="border"></div>
        </div>
    </div>

    {{-- Tabla de plazas --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="small text-uppercase text-muted border-bottom">
                    <tr>
                        <th class="ps-4 fw-semibold">Código</th>
                        <th class="fw-semibold">N.º</th>
                        <th class="fw-semibold">Tipo</th>
                        <th class="fw-semibold">Zona / Calle</th>
                        <th class="fw-semibold">Dimensiones</th>
                        <th class="fw-semibold">Orientación</th>
                        <th class="fw-semibold">Ubicación</th>
                        <th class="fw-semibold">Estado</th>
                        <th class="text-end pe-4 fw-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plazas as $p)
                        <tr class="border-bottom">
                            {{-- Código --}}
                            <td class="ps-4">
                                <span class="fw-semibold font-monospace">{{ $p->codigo }}</span>
                            </td>

                            {{-- Número Visible --}}
                            <td class="text-secondary">{{ $p->numero ?? '—' }}</td>

                            {{-- Tipo con indicador de color --}}
                            <td class="text-nowrap">
                                <span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background-color: {{ $p->tipoPlaza?->color_mapa }};"></span>
                                <span class="small">{{ $p->tipoPlaza?->nombre }}</span>
                            </td>

                            {{-- Zona y Calle --}}
                            <td class="small text-secondary">
                                <span class="fw-semibold d-block">{{ $p->zona?->nombre ?? '—' }}</span>
                                @if($p->calle)
                                    <span class="text-muted fs-7">{{ $p->calle->nombre }}</span>
                                @endif
                            </td>

                            {{-- Dimensiones --}}
                            <td class="text-secondary text-nowrap">{{ $p->dimensiones ?? '—' }}</td>

                            {{-- Orientación --}}
                            <td class="small text-secondary text-nowrap">{{ $p->orientacion_etiqueta }}</td>

                            {{-- Ubicación --}}
                            <td class="text-nowrap">
                                @if($p->tieneUbicacion())
                                    <span class="badge bg-success">
                                        <i class="bi bi-geo-alt-fill me-1"></i>Ubicada
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">Sin ubicar</span>
                                @endif
                            </td>

                            {{-- Estado: ícono + texto --}}
                            <td class="text-nowrap">
                                @if($p->activo)
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
                                            aria-label="Acciones de la plaza">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-1">
                                        @can('plazas.editar')
                                            <a href="{{ route('plazas.edit', $p) }}" class="dropdown-item py-1 px-3">
                                                <i class="bi bi-pencil me-2 text-muted"></i> Editar
                                            </a>
                                        @endcan
                                        @can('plazas.eliminar')
                                            <hr class="dropdown-divider my-1">
                                            <button type="button" class="dropdown-item py-1 px-3 text-danger"
                                                    data-confirm data-action="eliminar" data-method="DELETE"
                                                    data-url="{{ route('plazas.destroy', $p) }}">
                                                <i class="bi bi-trash me-2"></i> Eliminar
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-pin-map d-block mb-1 opacity-50"></i>
                                No se encontraron plazas
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($plazas->hasPages())
            <div class="card-footer bg-white border-top-0 pt-2 pb-2 px-4">
                {{ $plazas->links() }}
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
        const plazas = @json($plazasMapa);
        const zonas  = @json($zonasMapa);

        const map = L.map('mapa-plazas').setView([-1.0458, -78.5916], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '© OpenStreetMap'
        }).addTo(map);

        // Zonas de fondo
        zonas.forEach(z => {
            if (z.poligono && z.poligono.length >= 3) {
                L.polygon(z.poligono, {
                    color: z.color, weight: 1, fillOpacity: 0.04, dashArray: '4 4', interactive: false
                }).addTo(map);
            }
        });

        // Plazas como círculos coloreados por tipo
        const grupo = [];
        plazas.forEach(p => {
            const marker = L.circleMarker([p.lat, p.lng], {
                radius: 7, color: '#fff', weight: 1, fillColor: p.color, fillOpacity: 0.95
            }).addTo(map).bindPopup('<strong>' + p.codigo + '</strong><br>' + (p.tipo || ''));
            grupo.push(marker);
        });

        if (grupo.length) {
            try { map.fitBounds(L.featureGroup(grupo).getBounds().pad(0.3)); } catch (e) { /* noop */ }
        }
        setTimeout(() => map.invalidateSize(), 200);
    })();
    </script>
    @endpush

@endsection
