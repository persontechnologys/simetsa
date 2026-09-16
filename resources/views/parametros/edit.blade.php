{{-- resources/views/parametros/edit.blade.php --}}
@extends('layouts.app')

@section('breadcrumb')
    {{ Breadcrumbs::render('parametros.edit', $parametro) }}
@endsection

@section('content')

<div class="row g-4">
    {{-- Formulario principal --}}
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-0">
                <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
                    <i class="bi bi-sliders me-2 text-dark fs-5 align-middle"></i>Parámetro: {{ $parametro->clave }}
                </h2>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('parametros.update', $parametro) }}">
                    @csrf @method('PUT')

                    {{-- Información solo-lectura: Clave --}}
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-dark">Clave</label>
                        <div class="p-2 bg-light rounded border">
                            <code class="small font-monospace">{{ $parametro->clave }}</code>
                        </div>
                    </div>

                    {{-- Información solo-lectura: Categoría --}}
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-dark">Categoría</label>
                        <div class="p-2 bg-light rounded border">
                            {{ $parametro->categoria_etiqueta }}
                        </div>
                    </div>

                    {{-- Información solo-lectura: Origen legal --}}
                    @if($parametro->articulo_ordenanza)
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-dark">Origen legal</label>
                            <div class="p-2 bg-light rounded border">
                                <span class="badge bg-light text-dark border">
                                    Ordenanza SIMETSA — {{ $parametro->articulo_ordenanza }}
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Información solo-lectura: Tipo --}}
                    <div class="mb-3">
                        <label class="form-label small fw-medium text-dark">Tipo de dato</label>
                        <div class="p-2 bg-light rounded border">
                            <code class="small font-monospace">{{ $parametro->tipo }}</code>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Campo editable: Valor --}}
                    <div class="mb-3">
                        <label for="valor" class="form-label small fw-medium text-dark">Valor <span class="text-danger">*</span></label>

                        @switch($parametro->tipo)
                            @case(\App\Models\Parametro::TIPO_BOOLEAN)
                                <select name="valor" id="valor"
                                        class="form-select @error('valor') is-invalid @enderror" required>
                                    <option value="1" @selected(filter_var(old('valor', $parametro->valor), FILTER_VALIDATE_BOOLEAN))>Verdadero</option>
                                    <option value="0" @selected(!filter_var(old('valor', $parametro->valor), FILTER_VALIDATE_BOOLEAN))>Falso</option>
                                </select>
                                @break

                            @case(\App\Models\Parametro::TIPO_INTEGER)
                                <input type="number" name="valor" id="valor" step="1" min="0"
                                       class="form-control @error('valor') is-invalid @enderror"
                                       value="{{ old('valor', $parametro->valor) }}" required>
                                @break

                            @case(\App\Models\Parametro::TIPO_DECIMAL)
                                <input type="number" name="valor" id="valor" step="0.01" min="0"
                                       class="form-control @error('valor') is-invalid @enderror"
                                       value="{{ old('valor', $parametro->valor) }}" required>
                                @break

                            @default
                                <input type="text" name="valor" id="valor"
                                       class="form-control @error('valor') is-invalid @enderror"
                                       value="{{ old('valor', $parametro->valor) }}" required>
                        @endswitch

                        @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Descripción (opcional) --}}
                    <div class="mb-4">
                        <label for="descripcion" class="form-label small fw-medium text-dark">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="2"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                                  placeholder="Notas adicionales sobre este parámetro">{{ old('descripcion', $parametro->descripcion) }}</textarea>
                        @error('descripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Botones de acción --}}
                    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded border border-light-subtle">
                        <a href="{{ route('parametros.index') }}" class="btn btn-light text-secondary border-light-subtle">
                            <i class="bi bi-arrow-left me-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-dark px-4">
                            <i class="bi bi-floppy me-1"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Panel lateral: Advertencia --}}
    <div class="col-12 col-lg-5">
        <div class="alert alert-warning small border-0 shadow-sm rounded-4 mb-0">
            <div class="d-flex">
                <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0 mt-1"></i>
                <div>
                    <strong>Atención: Cambios críticos.</strong>
                    <p class="mb-2 mt-2">Modificar este parámetro afecta el comportamiento operativo del sistema en tiempo real:</p>
                    <ul class="mb-2 ps-3">
                        <li>Cambiar el SBU recalcula multas pendientes</li>
                        <li>Modificar porcentajes de liquidación cambia reparto futuro</li>
                        <li>Ajustes de tarifas aplican solo a nuevos tickets</li>
                    </ul>
                    <p class="mb-0 text-secondary fs-7"><i class="bi bi-info-circle me-1"></i> Los cambios quedan registrados con timestamp y usuario en el historial.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Historial de cambios --}}
<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-transparent border-bottom-0 pt-4 px-4 pb-3">
        <h2 class="h6 text-uppercase fw-bold text-muted mb-0 title-tracking">
            <i class="bi bi-clock-history me-2 text-dark fs-5 align-middle"></i>Historial de cambios
        </h2>
    </div>
    @php($bitacora = $parametro->bitacora()->with('user')->limit(20)->get())

    @if($bitacora->isEmpty())
        <div class="card-body text-muted small text-center py-4">
            <i class="bi bi-inbox d-block mb-1 opacity-50"></i>
            Este parámetro no ha sido modificado desde su creación
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="small text-uppercase text-muted border-bottom">
                    <tr>
                        <th class="ps-4 fw-semibold">Fecha / Hora</th>
                        <th class="fw-semibold">Usuario</th>
                        <th class="fw-semibold">Campo</th>
                        <th class="fw-semibold">Valor anterior</th>
                        <th class="fw-semibold">Valor nuevo</th>
                        <th class="pe-4 fw-semibold">IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bitacora as $b)
                        <tr class="border-bottom">
                            <td class="ps-4 small">{{ $b->ocurrido_en->format('d/m/Y H:i:s') }}</td>
                            <td class="small">
                                @if($b->user)
                                    <strong>{{ $b->user->name }}</strong>
                                @else
                                    <span class="text-muted">Sistema</span>
                                @endif
                            </td>
                            <td class="small"><code class="font-monospace">{{ $b->campo }}</code></td>
                            <td class="small"><span class="text-danger">{{ $b->valor_anterior ?? '—' }}</span></td>
                            <td class="small"><span class="text-success">{{ $b->valor_nuevo ?? '—' }}</span></td>
                            <td class="pe-4 small"><code class="font-monospace">{{ $b->ip ?? '—' }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($parametro->bitacora()->count() > 20)
            <div class="card-footer bg-white border-top-0 small text-muted">
                Mostrando las 20 entradas más recientes.
            </div>
        @endif
    @endif
</div>

@endsection
