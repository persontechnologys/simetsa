{{-- resources/views/manzanas/_form.blade.php --}}
@php
    $m = $manzana ?? null;
    $modo = $modo ?? 'crear';
    $zonaM = $m?->zona ?? $zonas->first();
    $centroMapa = $zonaM ? [$zonaM->centro_lat, $zonaM->centro_lng] : [-1.0458, -78.5916];
    $zoomMapa = $zonaM?->zoom ?? 16;
@endphp

<div class="row g-4">

    {{-- =========================================
         Sección 1: Datos de la manzana
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-info-circle me-2 text-dark fs-5 align-middle"></i>Datos de la manzana
                </h2>
            </div>
            <div class="card-body p-4">

                {{-- Zona --}}
                <div class="mb-3">
                    <label for="zona_id" class="form-label small fw-medium text-dark">Zona <span class="text-danger">*</span></label>
                    <select name="zona_id" id="zona_id" class="form-select @error('zona_id') is-invalid @enderror" required>
                        <option value="">— Seleccione una zona —</option>
                        @foreach($zonas as $z)
                            <option value="{{ $z->id }}" @selected(old('zona_id', $m?->zona_id) == $z->id)>{{ $z->nombre }}</option>
                        @endforeach
                    </select>
                    @error('zona_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Código --}}
                <div class="mb-3">
                    <label for="codigo" class="form-label small fw-medium text-dark">Código <span class="text-danger">*</span></label>
                    <input type="text" name="codigo" id="codigo"
                           class="form-control @error('codigo') is-invalid @enderror"
                           value="{{ old('codigo', $m?->codigo) }}" placeholder="Ej. M01, MZ-03" required>
                    <span class="form-text text-muted fs-7">Codificación urbana según la trama de la zona.</span>
                    @error('codigo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Nombre --}}
                <div class="mb-3">
                    <label for="nombre" class="form-label small fw-medium text-dark">Nombre</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $m?->nombre) }}" placeholder="Ej. Cuadrante noroeste">
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Descripción --}}
                <div class="mb-3">
                    <label for="descripcion" class="form-label small fw-medium text-dark">Descripción</label>
                    <textarea name="descripcion" id="descripcion" rows="3"
                              class="form-control @error('descripcion') is-invalid @enderror"
                              placeholder="Notas adicionales sobre la manzana">{{ old('descripcion', $m?->descripcion) }}</textarea>
                    @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Color y Estado --}}
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <label for="color" class="form-label small fw-medium text-dark">Color <span class="text-danger">*</span></label>
                        <input type="color" name="color" id="color"
                               class="form-control form-control-color @error('color') is-invalid @enderror"
                               value="{{ old('color', $m?->color ?? '#6c757d') }}" required>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label small fw-medium text-dark d-block">Estado</label>
                        <div class="form-check form-switch pt-1">
                            <input type="hidden" name="activo" value="0">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                                   @checked(old('activo', $m?->activo ?? true))>
                            <label for="activo" class="form-check-label small">Manzana activa</label>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- =========================================
         Sección 2: Editor de polígono
         ========================================= --}}
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-pin-map me-2 text-dark fs-5 align-middle"></i>Polígono de la manzana
                </h2>
            </div>
            <div class="card-body p-4">
                <span class="form-text text-muted fs-7 d-block mb-3">
                    <i class="bi bi-info-circle me-1"></i> El área tarifada de la zona aparece sombreada como referencia.
                </span>
                @include('partials.mapa-editor', [
                    'id'          => 'manzana',
                    'tipo'        => 'poligono',
                    'inputName'   => 'poligono',
                    'valorActual' => old('poligono') ? json_decode(old('poligono'), true) : ($m?->poligono ?? []),
                    'centro'      => $centroMapa,
                    'zoom'        => $zoomMapa,
                    'color'       => $m?->color ?? '#6c757d',
                    'referencias' => $zonasReferencia,
                ])
                @error('poligono')<div class="text-danger small mt-3 fs-7"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

</div>

{{-- 3. Botones de acción / Footer --}}
<div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border border-light-subtle">
    <a href="{{ route('manzanas.index') }}" class="btn btn-light text-secondary border-light-subtle" title="Cancelar y regresar al listado">
        <i class="bi bi-arrow-left me-1"></i> Cancelar y volver
    </a>
    <button type="submit" class="btn btn-dark px-4" title="{{ $modo === 'crear' ? 'Confirmar creación' : 'Guardar actualizaciones' }}">
        <i class="bi bi-floppy me-1"></i>
        {{ $modo === 'crear' ? 'Crear manzana' : 'Guardar cambios' }}
    </button>
</div>
