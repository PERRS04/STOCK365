@extends('layouts.ticket')

@section('page-title', 'Ticket #' . str_pad($sale->id, 6, '0', STR_PAD_LEFT))

{{-- Ticket-specific print styles injected into layout <head> via @yield('styles') --}}
@section('styles')
<style>
    /* ── Shared helpers ── */
    .t-center    { text-align: center; }
    .t-rule      { border: none; border-top: 1px dashed #333; margin: 8px 0; }
    .t-rule-solid{ border: none; border-top: 1px solid #000; margin: 6px 0; }

    /* ── Header ── */
    .t-sede-name {
        font-size: 17px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        text-align: center;
    }
    .t-sede-info {
        font-size: 10px;
        color: #444;
        text-align: center;
        margin-top: 2px;
    }

    /* ── Comprobante label + meta ── */
    .t-comp-label {
        text-align: center;
        font-size: 9px;
        font-weight: bold;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        margin: 4px 0 5px;
    }
    .t-meta      { font-size: 10px; margin: 2px 0; }

    /* ── ANULADA banner ── */
    .t-anulada {
        text-align: center;
        font-weight: bold;
        font-size: 12px;
        letter-spacing: 0.12em;
        border: 2px solid #000;
        padding: 5px 4px;
        margin: 6px 0;
    }

    /* ── Items ── */
    .t-item {
        margin: 7px 0;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .t-product-name {
        font-weight: bold;
        font-size: 11px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .t-item-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: 10px;
        margin-top: 2px;
        gap: 6px;
    }
    .t-item-desc    { color: #333; flex: 1; }
    .t-item-amount  { font-weight: bold; white-space: nowrap; }

    /* ── Totals ── */
    .t-total-row {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        margin: 2px 0;
    }
    .t-total-final {
        display: flex;
        justify-content: space-between;
        font-size: 16px;
        font-weight: bold;
        padding: 6px 0;
        border-top: 2px solid #000;
        border-bottom: 2px solid #000;
        margin: 6px 0;
    }

    /* ── Footer ── */
    .t-thanks   { font-size: 11px; font-weight: bold; text-align: center; margin-bottom: 5px; }
    .t-legal    { font-size: 9px; color: #555; text-align: center; line-height: 1.55; }
</style>
@endsection

@section('content')

{{-- ── Header: Sede ── --}}
<div class="t-sede-name">{{ $sale->sede->nombre }}</div>

@php
    $sedeLines = array_filter([
        $sale->sede->ubicacion,
        $sale->sede->ciudad,
    ]);
@endphp
@if($sedeLines)
    <div class="t-sede-info">{{ implode(', ', $sedeLines) }}</div>
@endif
@if($sale->sede->telefono)
    <div class="t-sede-info">Tel: {{ $sale->sede->telefono }}</div>
@endif

<hr class="t-rule">

{{-- ── ANULADA banner ── --}}
@if($sale->estado === 'anulada')
<div class="t-anulada">** VENTA ANULADA **</div>
@endif

{{-- ── Comprobante meta ── --}}
<div class="t-comp-label">Comprobante de Venta</div>
<div class="t-meta">Venta&nbsp;#{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</div>
<div class="t-meta">{{ $sale->fecha_venta->format('d/m/Y') }}&nbsp;&middot;&nbsp;{{ $sale->fecha_venta->format('H:i') }}</div>
<div class="t-meta">Atendido por: {{ $sale->user?->name ?? '—' }}</div>

<hr class="t-rule">

{{-- ── Items ── --}}
{{--
    Snapshot rule:
    · presentation_name != null  → ETAPA 4: show cantidad_presentaciones × presentation_name
    · presentation_name == null  → legacy: show cantidad base
    · precio_unitario, subtotal  → always from SaleItem snapshot
    · product name               → live ($item->product?->nombre) — no snapshot exists (v1 limitation)
--}}
@foreach($sale->items as $item)
<div class="t-item">
    <div class="t-product-name">{{ $item->product?->nombre ?? 'Producto' }}</div>
    <div class="t-item-row">
        @if($item->presentation_name)
            <span class="t-item-desc">
                {{ $item->cantidad_presentaciones }}&nbsp;&times;&nbsp;{{ $item->presentation_name }}
                &nbsp;&middot;&nbsp;${{ number_format($item->precio_unitario, 2) }}&nbsp;c/u
            </span>
        @else
            <span class="t-item-desc">
                {{ $item->cantidad }}&nbsp;&times;&nbsp;${{ number_format($item->precio_unitario, 2) }}&nbsp;c/u
            </span>
        @endif
        <span class="t-item-amount">${{ number_format($item->subtotal, 2) }}</span>
    </div>
</div>
@endforeach

<hr class="t-rule">

{{-- ── Totals ── --}}
<div class="t-total-row">
    <span>Subtotal</span>
    <span>${{ number_format($sale->subtotal_bruto, 2) }}</span>
</div>
<div class="t-total-row">
    <span>Descuento</span>
    <span>-${{ number_format($sale->descuento, 2) }}</span>
</div>
<div class="t-total-final">
    <span>TOTAL</span>
    <span>${{ number_format($sale->total_sistema, 2) }}</span>
</div>

{{-- ── Footer ── --}}
<div class="t-thanks">Gracias por su compra</div>
<div class="t-legal">
    Este comprobante no constituye<br>
    factura electr&oacute;nica autorizada<br>
    por el SRI.
</div>

@endsection
