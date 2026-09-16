{{-- resources/views/zonas/_form.blade.php --}}
@php
    $z = $zona ?? null;
    $modo = $modo ?? 'crear';
@endphp

<div class="row g-4">

    {{-- =========================================
         Sección 1: Datos de la zona
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-info-circle me-2 text-dark fs-5 align-middle"></i>Datos de la zona
                </h2>
            </div>
            <div class="card-body p-4">

                {{-- Código --}}
                <div class="mb-3">
                    <label for="codigo" class="form-label small fw-medium text-dark">Código <span class="text-danger">*</span></label>
                    <input type="text" name="codigo" id="codigo"
                           class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $z?->codigo) }}" placeholder="snake_case (ej: centro)" required>
                    <span class="form-text text-muted fs-7">Identificador único en formato snake_case.</span>
                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Nombre --}}
                <div class="mb-3">
                    <label for="nombre" class="form-label small fw-medium text-dark">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $z?->nombre) }}" placeholder="Ej. Centro histórico" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Descripción --}}
                <div class="mb-3">
                    <label for="descripcion" class="form-label small fw-medium text-dark">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3"
                              class="form-control @error('descripcion') is-invalid @enderror"
                              placeholder="Detalles sobre la zona">{{ old('descripcion', $z?->descripcion) }}</textarea>
                    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Centro (Latitud y Longitud) --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="centro_lat" class="form-label small fw-medium text-dark">Latitud centro <span class="text-danger">*</span></label>
                        <input type="number" step="0.0000001" name="centro_lat" id="centro_lat"
                               class="form-control font-monospace @error('centro_lat') is-invalid @enderror"
                               value="{{ old('centro_lat', $z?->centro_lat ?? -1.0458) }}" required>
                        @error('centro_lat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="centro_lng" class="form-label small fw-medium text-dark">Longitud centro <span class="text-danger">*</span></label>
                        <input type="number" step="0.0000001" name="centro_lng" id="centro_lng"
                               class="form-control font-monospace @error('centro_lng') is-invalid @enderror"
                               value="{{ old('centro_lng', $z?->centro_lng ?? -78.5916) }}" required>
                        @error('centro_lng')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Zoom, Color, Estado --}}
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label for="zoom" class="form-label small fw-medium text-dark">Nivel de zoom <span class="text-danger">*</span></label>
                        <input type="number" name="zoom" id="zoom" min="1" max="20"
                               class="form-control @error('zoom') is-invalid @enderror"
                               value="{{ old('zoom', $z?->zoom ?? 16) }}" required>
                        <span class="form-text text-muted fs-7">Entre 1 (muy alejado) y 20 (muy cerca).</span>
                        @error('zoom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="color" class="form-label small fw-medium text-dark">Color <span class="text-danger">*</span></label>
                        <input type="color" name="color" id="color"
                               class="form-control form-control-color @error('color') is-invalid @enderror"
                               value="{{ old('color', $z?->color ?? '#0d4a8f') }}" required>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Estado --}}
                <div class="mb-0 mt-3">
                    <label class="form-label small fw-medium text-dark d-block mb-2">Estado</label>
                    <div class="form-check form-switch">
                        <input type="hidden" name="activo" value="0">
                        <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                               @checked(old('activo', $z?->activo ?? true))>
                        <label for="activo" class="form-check-label small">Zona activa</label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- =========================================
         Sección 2: Editor de geometría (Polígono)
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-pin-map me-2 text-dark fs-5 align-middle"></i>Polígono de la zona
                </h2>
            </div>
            <div class="card-body p-4">
                @include('partials.mapa-editor', [
                    'id'          => 'zona',
                    'tipo'        => 'poligono',
                    'inputName'   => 'poligono',
                    'valorActual' => old('poligono') ? json_decode(old('poligono'), true) : ($z?->poligono ?? []),
                    'centro'      => [$z?->centro_lat ?? -1.0458, $z?->centro_lng ?? -78.5916],
                    'zoom'        => $z?->zoom ?? 16,
                    'color'       => $z?->color ?? '#0d4a8f',
                ])
                @error('poligono')<div class="text-danger small mt-3 fs-7"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                <span class="form-text text-muted fs-7 d-block mt-2">
                    <i class="bi bi-info-circle me-1"></i> Dibuja el polígono de la zona directamente en el mapa.
                </span>
            </div>
        </div>
    </div>

</div>

{{-- 3. Botones de acción / Footer --}}
<div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border border-light-subtle">
    <a href="{{ route('zonas.index') }}" class="btn btn-light text-secondary border-light-subtle" title="Cancelar y regresar al listado">
        <i class="bi bi-arrow-left me-1"></i> Cancelar y volver
    </a>
    <button type="submit" class="btn btn-dark px-4" title="{{ $modo === 'crear' ? 'Confirmar creación' : 'Guardar actualizaciones' }}">
        <i class="bi bi-floppy me-1"></i>
        {{ $modo === 'crear' ? 'Crear zona' : 'Guardar cambios' }}
    </button>
</div>