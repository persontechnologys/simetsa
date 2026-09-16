{{-- resources/views/tarifas/_form.blade.php --}}
@php $t = $tarifa ?? null; $modo = $modo ?? 'crear'; @endphp

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
        <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
            <i class="bi bi-receipt me-2 text-dark fs-5 align-middle"></i>Datos de la tarifa
        </h2>
    </div>
    <div class="card-body p-4">

        {{-- Tipo de Plaza y Nombre --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <label for="tipo_plaza_id" class="form-label small fw-medium text-dark">Tipo de plaza <span class="text-danger">*</span></label>
                <select name="tipo_plaza_id" id="tipo_plaza_id"
                        class="form-select @error('tipo_plaza_id') is-invalid @enderror" required>
                    <option value="">— Seleccione un tipo —</option>
                    @foreach($tiposPlaza as $tp)
                        <option value="{{ $tp->id }}"
                            @selected(old('tipo_plaza_id', $t?->tipo_plaza_id) == $tp->id)>
                            {{ $tp->nombre }} ({{ $tp->codigo }})
                            @if(!$tp->es_pagado) — Exonerado @endif
                        </option>
                    @endforeach
                </select>
                @error('tipo_plaza_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-lg-6">
                <label for="nombre" class="form-label small fw-medium text-dark">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" id="nombre"
                       class="form-control @error('nombre') is-invalid @enderror"
                       value="{{ old('nombre', $t?->nombre) }}" required
                       placeholder="Ej. Tarifa estándar 2026">
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Valor por Hora, Vigente Desde, Vigente Hasta, Estado --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label for="valor_hora" class="form-label small fw-medium text-dark">Valor por hora (USD) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">$</span>
                    <input type="number" name="valor_hora" id="valor_hora" step="0.0001" min="0"
                           class="form-control border-start-0 ps-0 @error('valor_hora') is-invalid @enderror"
                           value="{{ old('valor_hora', $t?->valor_hora) }}" required>
                    @error('valor_hora')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <span class="form-text text-muted fs-7">Según Art. 22: $0.25 mínimo</span>
            </div>

            <div class="col-12 col-md-6">
                <label for="vigente_desde" class="form-label small fw-medium text-dark">Vigente desde <span class="text-danger">*</span></label>
                <input type="date" name="vigente_desde" id="vigente_desde"
                       class="form-control @error('vigente_desde') is-invalid @enderror"
                       value="{{ old('vigente_desde', $t?->vigente_desde?->format('Y-m-d')) }}" required>
                @error('vigente_desde')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- Vigente Hasta y Estado en la misma fila --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
                <label for="vigente_hasta" class="form-label small fw-medium text-dark">Vigente hasta</label>
                <input type="date" name="vigente_hasta" id="vigente_hasta"
                       class="form-control @error('vigente_hasta') is-invalid @enderror"
                       value="{{ old('vigente_hasta', $t?->vigente_hasta?->format('Y-m-d')) }}">
                <span class="form-text text-muted fs-7">Dejar en blanco si es "hasta nuevo aviso"</span>
                @error('vigente_hasta')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label small fw-medium text-dark d-block mb-2">Estado</label>
                <div class="form-check form-switch pt-1">
                    <input type="hidden" name="activo" value="0">
                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1"
                           @checked(old('activo', $t?->activo ?? true))>
                    <label for="activo" class="form-check-label small">Tarifa activa</label>
                </div>
            </div>
        </div>

        {{-- Descripción --}}
        <div class="mb-0">
            <label for="descripcion" class="form-label small fw-medium text-dark">Descripción</label>
            <textarea name="descripcion" id="descripcion" rows="2"
                      class="form-control @error('descripcion') is-invalid @enderror"
                      placeholder="Notas o condiciones especiales de esta tarifa">{{ old('descripcion', $t?->descripcion) }}</textarea>
            @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

    </div>
</div>

{{-- Botones de acción / Footer --}}
<div class="mt-4 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border border-light-subtle">
    <a href="{{ route('tarifas.index') }}" class="btn btn-light text-secondary border-light-subtle" title="Cancelar y regresar al listado">
        <i class="bi bi-arrow-left me-1"></i> Cancelar y volver
    </a>
    <button type="submit" class="btn btn-dark px-4" title="{{ $modo === 'crear' ? 'Confirmar creación' : 'Guardar actualizaciones' }}">
        <i class="bi bi-floppy me-1"></i>
        {{ $modo === 'crear' ? 'Crear tarifa' : 'Guardar cambios' }}
    </button>
</div>
