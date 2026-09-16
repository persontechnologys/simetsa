{{-- resources/views/comprobantes/pdf.blade.php --}}
{{-- Comprobante de pago imprimible (Art. 19 — Ordenanza SIMETSA). --}}
{{-- Preparado para futura facturación electrónica SRI. --}}
@extends('layouts.impresion')

@section('titulo', 'Comprobante ' . $comprobante->numero)

@section('content')

<div class="row mb-3">
    <div class="col-6">
        <h2 class="h5 fw-bold mb-0">SIMETSA</h2>
        <div class="text-muted small">
            GAD Municipal del Cantón Salcedo<br>
            Sistema Municipal de Estacionamiento Tarifado<br>
            Nota de Venta Interna — No válido como factura SRI
        </div>
    </div>
    <div class="col-6 text-end">
        <div class="fw-bold fs-5">{{ $comprobante->numero }}</div>
        <div class="small text-muted">
            Fecha de emisión:<br>
            <strong>{{ $comprobante->fecha_emision?->isoFormat('D MMM YYYY, HH:mm') }}</strong>
        </div>
    </div>
</div>

<table class="table table-sm table-bordered mb-3">
    <tbody>
        <tr>
            <th width="35%">Concepto</th>
            <td>
                @if($comprobante->concepto)
                    {{ $comprobante->concepto->descripcionCobro() }}
                @else
                    —
                @endif
            </td>
        </tr>
        @if($comprobante->concepto_type === \App\Models\Ticket::class)
            <tr>
                <th>Tipo</th>
                <td>Ticket de Parqueo</td>
            </tr>
            @if($comprobante->concepto)
                <tr>
                    <th>Ticket</th>
                    <td>{{ $comprobante->concepto->codigo ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Placa</th>
                    <td>{{ $comprobante->concepto->vehiculo?->placa ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Zona</th>
                    <td>{{ $comprobante->concepto->zona?->nombre ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Horas</th>
                    <td>{{ $comprobante->concepto->horas_compradas ?? '—' }}h</td>
                </tr>
            @endif
        @elseif($comprobante->concepto_type === \App\Models\Infraccion::class)
            <tr>
                <th>Tipo</th>
                <td>Pago de Multa (Art. 28)</td>
            </tr>
            @if($comprobante->concepto)
                <tr>
                    <th>Infracción</th>
                    <td>#{{ $comprobante->concepto->id }}</td>
                </tr>
                <tr>
                    <th>Placa</th>
                    <td>{{ $comprobante->concepto->placa }}</td>
                </tr>
                <tr>
                    <th>Tipo infracción</th>
                    <td>{{ $comprobante->concepto->tipo_infraccion?->value }}</td>
                </tr>
            @endif
        @endif
        <tr>
            <th>Monto pagado</th>
            <td class="fw-bold fs-6">$ {{ number_format($comprobante->monto, 2) }} USD</td>
        </tr>
    </tbody>
</table>

<div class="text-muted small mt-3">
    <p>
        Este documento es un comprobante interno del Sistema Municipal de Estacionamiento
        Tarifado (SIMETSA) del cantón Salcedo. No constituye factura electrónica ni
        comprobante de retención según la normativa del SRI.
    </p>
    <p class="mb-0">
        Para reclamos o consultas comuníquese con la Comisaría Municipal.
        <strong>Número de comprobante: {{ $comprobante->numero }}</strong>
    </p>
</div>

<div class="no-print text-center mt-4">
    <button class="btn btn-sm btn-primary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimir / Guardar PDF
    </button>
</div>

@endsection
