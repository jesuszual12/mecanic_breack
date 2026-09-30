<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial {{ $vehicle->plate }} | mecanic_breack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="detail-page history-page"><header class="detail-topbar"><a class="text-link" href="{{ route('dashboard', ['panel' => 'vehicles']) }}">← Regresar al taller</a><a class="button button-primary" href="{{ route('dashboard') }}#new-order">＋ Nueva orden</a></header><section class="detail-heading"><div><p class="eyebrow">HISTORIAL DEL VEHÍCULO</p><h1>{{ $vehicle->make }} {{ $vehicle->model }} {{ $vehicle->year }}</h1><p>{{ $vehicle->plate }} · Propietario: {{ $vehicle->customer->name }} · {{ $vehicle->customer->phone }}</p></div><span class="history-count">{{ $vehicle->workOrders->count() }} órdenes</span></section><article class="panel data-panel history-panel"><div class="table-wrap history-table-wrap w-full min-w-0 max-w-full overflow-x-auto"><table class="mobile-card-table w-full"><thead><tr><th>ORDEN</th><th>INGRESO</th><th>SALIDA</th><th>TRABAJO SOLICITADO</th><th>ESTADO</th>@can('manage-finances')<th>TOTAL</th>@endcan<th></th></tr></thead><tbody>@forelse ($vehicle->workOrders as $order)<tr><td data-label="Orden"><strong>{{ $order->number }}</strong></td><td data-label="Ingreso">{{ $order->received_at->format('d/m/Y H:i') }}</td><td data-label="Salida">{{ $order->delivered_at?->format('d/m/Y H:i') ?: '—' }}</td><td data-label="Trabajo solicitado">{{ \Illuminate\Support\Str::limit($order->complaint, 70) }}</td><td data-label="Estado"><span class="status-pill status-{{ $order->status }}"><i></i>{{ ['pending' => 'Pendiente', 'in_progress' => 'En proceso', 'completed' => 'Terminado', 'delivered' => 'Entregado'][$order->status] ?? $order->status }}</span></td>@can('manage-finances')<td data-label="Total">${{ number_format($order->total, 2) }}</td>@endcan<td data-label="Detalle"><a class="text-link" href="{{ route('work-orders.show', $order) }}">Abrir →</a></td></tr>@empty<tr><td colspan="{{ auth()->user()->can('manage-finances') ? 7 : 6 }}" class="empty-row">Este vehículo todavía no tiene servicios registrados.</td></tr>@endforelse</tbody></table></div></article></main>
</body>
</html>
