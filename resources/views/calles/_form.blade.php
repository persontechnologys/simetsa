{{-- resources/views/calles/_form.blade.php --}}
@php
    $c = $calle ?? null;
    $modo = $modo ?? 'crear';
    $zonaC = $c?->zona ?? $zonas->first();
    $centroMapa = $zonaC ? [$zonaC->centro_lat, $zonaC->centro_lng] : [-1.0458, -78.5916];
    $zoomMapa = $zonaC?->zoom ?? 16;
@endphp

<div class="row g-4">

    {{-- =========================================
         Sección 1: Datos de la calle
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-info-circle me-2 text-dark fs-5 align-middle"></i>Datos de la calle
                </h2>
            </div>
            <div class="card-body p-4">

                {{-- Zona --}}
                <div class="mb-3">
                    <label for="zona_id" class="form-label small fw-medium text-dark">Zona <span class="text-danger">*</span></label>
                    <select name="zona_id" id="zona_id" class="form-select @error('zona_id') is-invalid @enderror" required>
                        <option value="">— Seleccione una zona —</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z->id }}" @selected(old('zona_id', $c?->zona_id) == $z->id)>{{ $z->nombre }}</option>
                        @endforeach
                    </select>
                    @error('zona_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Código --}}
                <div class="mb-3">
                    <label for="codigo" class="form-label small fw-medium text-dark">Código <span class="text-danger">*</span></label>
                    <input type="text" name="codigo" id="codigo"
                           class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $c?->codigo) }}" placeholder="Ej. vicente_leon" required>
                    <span class="form-text text-muted fs-7">Identificador único en formato snake_case.</span>
                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Nombre --}}
                <div class="mb-3">
                    <label for="nombre" class="form-label small fw-medium text-dark">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $c?->nombre) }}" placeholder="Ej. Avenida Vicente León" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Tramo (Desde / Hasta) --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="desde" class="form-label small fw-medium text-dark">Desde</label>
                        <input type="text" name="desde" id="desde"
                               class="form-control @error('desde') is-invalid @enderror"
                               value="{{ old('desde', $c?->desde) }}" placeholder="Calle inicial">
                        @error('desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="hasta" class="form-label small fw-medium text-dark">Hasta</label>
                        <input type="text" name="hasta" id="hasta"
                               class="form-control @error('hasta') is-invalid @enderror"
                               value="{{ old('hasta', $c?->hasta) }}" placeholder="Calle final">
                        @error('hasta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <span class="form-text text-muted fs-7 d-block mb-3">
                    <i class="bi bi-info-circle me-1"></i> Tramo o segmento de la calle según Art. 16.
                </span>

                {{-- Sentido --}}
                <div class="mb-3">
                    <label for="sentido" class="form-label small fw-medium text-dark">Sentido <span class="text-danger">*</span></label>
                    <select name="sentido" id="sentido" class="form-select @error('sentido') is-invalid @enderror" required>
                        @foreach($sentidos as $v => $e)
                            <option value="{{ $v }}" @selected(old('sentido', $c?->sentido ?? 'doble') === $v)>{{ $e }}</option>
                        @endforeach
                    </select>
                    @error('sentido')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Lado de Estacionamiento --}}
                <div class="mb-3">
                    <label for="lado_estacionamiento" class="form-label small fw-medium text-dark">Costado de estacionamiento <span class="text-danger">*</span></label>
                    <select name="lado_estacionamiento" id="lado_estacionamiento"
                            class="form-select @error('lado_estacionamiento') is-invalid @enderror" required>
                        @foreach($lados as $v => $e)
                            <option value="{{ $v }}" @selected(old('lado_estacionamiento', $c?->lado_estacionamiento ?? 'derecho') === $v)>{{ $e }}</option>
                        @endforeach
                    </select>
                    <span class="form-text text-muted fs-7">Por defecto costado derecho <span class="text-secondary fw-medium">(Art. 5)</span>.</span>
                    @error('lado_estacionamiento')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Estado --}}
                <div class="mb-0 pt-2">
                    <label class="form-label small fw-medium text-dark d-block mb-2">Estado</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="activo" value="0">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                               @checked(old('activo', $c?->activo ?? true))>
                        <label for="activo" class="form-check-label small">Calle activa</label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- =========================================
         Sección 2: Editor de trazado (Polilínea)
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-pin-map me-2 text-dark fs-5 align-middle"></i>Trazado de la calle
                </h2>
            </div>
            <div class="card-body p-4">
                <span class="form-text text-muted fs-7 d-block mb-3">
                    <i class="bi bi-info-circle me-1"></i> El área tarifada de cada zona aparece sombreada como referencia.
                </span>
                @include('partials.mapa-editor', [
                    'id'          => 'calle',
                    'tipo'        => 'polilinea',
                    'inputName'   => 'polilinea',
                    'valorActual' => old('polilinea') ? json_decode(old('polilinea'), true) : ($c?->polilinea ?? []),
                    'centro'      => $centroMapa,
                    'zoom'        => $zoomMapa,
                    'color'       => '#dc3545',
                    'referencias' => $zonasReferencia,
                ])
                @error('polilinea')<div class="text-danger small mt-3 fs-7"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

</div>

{{-- 3. Botones de acción / Footer --}}
<div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border border-light-subtle">
    <a href="{{ route('calles.index') }}" class="btn btn-light text-secondary border-light-subtle" title="Cancelar y regresar al listado">
        <i class="bi bi-arrow-left me-1"></i> Cancelar y volver
    </a>
    <button type="submit" class="btn btn-dark px-4" title="{{ $modo === 'crear' ? 'Confirmar creación' : 'Guardar actualizaciones' }}">
        <i class="bi bi-floppy me-1"></i>
        {{ $modo === 'crear' ? 'Crear calle' : 'Guardar cambios' }}
    </button>
</div>