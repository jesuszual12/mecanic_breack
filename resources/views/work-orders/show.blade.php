<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $workOrder->number }} | mecanic_breack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="detail-page">
        <header class="detail-topbar"><a class="text-link" href="{{ route('dashboard', ['panel' => 'orders']) }}">←
                Regresar a órdenes</a>
            <div class="detail-actions">
                @can('manage-finances')
                    <a class="button button-outline" href="{{ route('work-orders.receipt', $workOrder) }}">Imprimir nota
                        <span>↗</span></a>
                @endcan
                <a class="button button-primary" href="{{ route('dashboard') }}#new-order">
                    ＋ Nueva orden</a>
            </div>
        </header>
        @if (session('success'))
            <div class="flash-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash-error">{{ $errors->first() }}</div>
        @endif
        <section class="detail-heading">
            <div>
                <p class="eyebrow">ORDEN DE TRABAJO</p>
                <h1>{{ $workOrder->number }}</h1>
                <p>{{ $workOrder->received_at->format('d/m/Y H:i') }} · <a
                        href="{{ route('vehicles.show', $workOrder->vehicle) }}">Historial del vehículo
                        {{ $workOrder->vehicle->plate }}</a>@if ($workOrder->scheduled_at) · Cita:
                        {{ $workOrder->scheduled_at->format('d/m/Y H:i') }}@endif</p>
            </div><span
                class="status-pill status-{{ $workOrder->status }}"><i></i>{{ ['pending' => 'Pendiente', 'in_progress' => 'En proceso', 'completed' => 'Terminado', 'delivered' => 'Entregado'][$workOrder->status] ?? $workOrder->status }}</span>
        </section>
        <div class="detail-grid">
            <section class="detail-main">
                <article class="panel detail-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">RECEPCIÓN</p>
                            <h2>Datos del servicio</h2>
                        </div><span class="detail-time">{{ $workOrder->received_at->format('d M Y, H:i') }}</span>
                    </div>
                    <div class="detail-info-grid">
                        <div>
                            <small>CLIENTE</small><strong>{{ $workOrder->customer->name }}</strong><span>{{ $workOrder->customer->phone }}</span>
                        </div>
                        <div><small>VEHÍCULO</small><strong>{{ $workOrder->vehicle->make }}
                                {{ $workOrder->vehicle->model }}
                                {{ $workOrder->vehicle->year }}</strong><span>{{ $workOrder->vehicle->plate }} ·
                                {{ $workOrder->vehicle->color ?: 'Color no registrado' }}</span></div>
                    </div>
                    <div class="detail-description"><small>MOTIVO DE INGRESO</small>
                        <p>{{ $workOrder->complaint }}</p>
                        @if ($workOrder->diagnosis)
                            <small>DIAGNÓSTICO</small>
                            <p>{{ $workOrder->diagnosis }}</p>
                        @endif
                        @if ($workOrder->damage_points)
                            <small>DAÑOS DE CARROCERÍA</small>
                            <ul class="damage-detail-list">
                                @foreach ($workOrder->damage_points as $damage)
                                    <li><strong>{{ ['hood' => 'Cofre', 'roof' => 'Techo', 'trunk' => 'Cajuela', 'front_bumper' => 'Defensa delantera', 'rear_bumper' => 'Defensa trasera', 'left_front_door' => 'Puerta delantera izquierda', 'right_front_door' => 'Puerta delantera derecha', 'left_rear_door' => 'Puerta trasera izquierda', 'right_rear_door' => 'Puerta trasera derecha', 'left_front_fender' => 'Salpicadera delantera izquierda', 'right_front_fender' => 'Salpicadera delantera derecha', 'left_rear_fender' => 'Salpicadera trasera izquierda', 'right_rear_fender' => 'Salpicadera trasera derecha'][$damage['part']] ?? $damage['part'] }}</strong><span>{{ $damage['detail'] }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </article>
                <article class="panel detail-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">PRESUPUESTO Y TRABAJO</p>
                            <h2>Conceptos de la orden</h2>
                        </div>
                    </div>
                    <div class="table-wrap w-full min-w-0 max-w-full overflow-x-auto">
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>CONCEPTO</th>
                                    <th>CANT.</th>
                                    @can('manage-finances')
                                        <th>PRECIO UNITARIO</th>
                                        <th>IMPORTE</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @if ($workOrder->items->isEmpty())
                                    <tr>
                                        <td colspan="{{ auth()->user()->can('manage-finances') ? 4 : 2 }}"
                                            class="empty-row">No se han agregado conceptos detallados.</td>
                                    </tr>
                                @else
                                    @foreach ($workOrder->items as $item)
                                        <tr>
                                            <td><strong>{{ $item->description }}</strong><small
                                                    class="table-secondary">{{ ['service' => 'Servicio', 'labor' => 'Mano de obra', 'part' => 'Refacción'][$item->type] ?? $item->type }}</small>
                                            </td>
                                            <td>{{ number_format($item->quantity, 2) }}</td>
                                            @can('manage-finances')
                                                <td>${{ number_format($item->unit_price, 2) }}</td>
                                                <td>${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                                            @endcan
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <form method="POST" action="{{ route('work-orders.items.store', $workOrder) }}"
                        class="add-line-form">@csrf<label>Tipo<select name="type">
                                <option value="service">Servicio</option>
                                <option value="labor">Mano de obra</option>
                            </select></label><label>Descripción<input name="description" maxlength="190" required
                                placeholder="Alineación y balanceo"></label><label>Cantidad<input type="number"
                                name="quantity" min="0.01" step="0.01" value="1" required></label>
                        @can('manage-finances')
                            <label>Precio unitario<input type="number" name="unit_price" min="0" step="0.01"
                                    required></label>
                        @endcan
                        <button class="button button-primary" type="submit">
                            Agregar trabajo</button>
                    </form>
                </article>
                <article class="panel detail-panel">
                    <div class="panel-heading">
                        <div>
                            <p class="eyebrow">MOVIMIENTOS</p>
                            <h2>Pagos recibidos</h2>
                        </div>
                    </div>
                    @forelse ($workOrder->payments as $payment)
                        <div class="payment-row">
                            <span><strong>{{ $payment->paid_at->format('d/m/Y H:i') }}</strong><small>{{ ['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta'][$payment->method] ?? $payment->method }}</small></span><b>${{ number_format($payment->amount, 2) }}</b>
                    </div>@empty<p class="empty-copy">Todavía no se registran pagos.</p>
                    @endforelse
                </article>
            </section>
            <aside class="detail-aside">
                <article class="panel total-panel">
                    <p class="eyebrow">RESUMEN DE CUENTA</p>
                    @can('manage-finances')
                        <div><span>Mano de obra</span><strong>${{ number_format($workOrder->labor_cost, 2) }}</strong>
                        </div>
                        <div><span>Refacciones</span><strong>${{ number_format($workOrder->parts_cost, 2) }}</strong></div>
                        <div><span>Otros conceptos</span><strong>${{ number_format($workOrder->other_cost, 2) }}</strong>
                        </div>
                        <div class="total-divider">
                            <span>Total</span><strong>${{ number_format($workOrder->total, 2) }}</strong></div>
                        <div><span>Pagado</span><strong>${{ number_format($workOrder->paid, 2) }}</strong></div>
                        <div class="balance-row"><span>Saldo
                                pendiente</span><strong>${{ number_format($workOrder->total - $workOrder->paid, 2) }}</strong>
                    </div>@else<p class="restricted-copy">Los importes solo son visibles para administración.</p>
                    @endcan
                </article>
                @can('manage-finances')
                    <article class="panel detail-panel">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow">CIERRE ADMINISTRATIVO</p>
                                <h2>Costos y garantía</h2>
                            </div>
                        </div>
                        <form class="compact-form" method="POST"
                            action="{{ route('work-orders.financials.update', $workOrder) }}">@csrf @method('PATCH')<div
                                class="form-row"><label>Mano de obra<input name="labor_cost" type="number" min="0"
                                        max="99999999.99" step="0.01"
                                        value="{{ old('labor_cost', $workOrder->labor_cost) }}"
                                        required></label><label>Refacciones<input name="parts_cost" type="number"
                                        min="0" max="99999999.99" step="0.01"
                                        value="{{ old('parts_cost', $workOrder->parts_cost) }}" required></label></div>
                            <label>Otros conceptos<input name="other_cost" type="number" min="0" max="99999999.99"
                                    step="0.01" value="{{ old('other_cost', $workOrder->other_cost) }}"
                                    required></label>
                            <p class="form-note">El total se calcula sumando mano de obra, refacciones y otros conceptos.
                            </p>
                            <div class="form-row"><label>Garantía (días)<input name="warranty_days" type="number"
                                        min="0" max="3650"
                                        value="{{ old('warranty_days', $workOrder->warranty_days) }}"
                                        required></label><label>Condiciones<input name="warranty_terms" maxlength="2000"
                                        value="{{ old('warranty_terms', $workOrder->warranty_terms) }}"
                                        placeholder="Opcional"></label></div><button class="button button-primary"
                                type="submit">Guardar costos y garantía</button>
                        </form>
                    </article>
                @endcan
                <article class="panel warranty-panel">
                    <p class="eyebrow">GARANTÍA</p>
                    @if ($workOrder->warranty_days > 0)
                        <strong>{{ $workOrder->warranty_days }} días</strong>
                        <p>{{ $workOrder->warranty_terms ?: 'Sin condiciones adicionales.' }}</p>
                        @if ($workOrder->delivered_at)
                            <small>Vence
                            {{ $workOrder->delivered_at->copy()->addDays($workOrder->warranty_days)->format('d/m/Y') }}</small>@else<small>Comienza
                                al entregar el vehículo.</small>
                    @endif @else<p>Sin garantía registrada.</p>
                    @endif
                </article>
                <article class="panel status-panel">
                    <p class="eyebrow">ACTUALIZAR ESTADO</p>
                    <form method="POST" action="{{ route('work-orders.status', $workOrder) }}">@csrf
                        @method('PATCH')<select name="status">
                            <option value="pending" @selected($workOrder->status === 'pending')>Pendiente</option>
                            <option value="in_progress" @selected($workOrder->status === 'in_progress')>En proceso</option>
                            <option value="completed" @selected($workOrder->status === 'completed')>Terminado</option>
                            <option value="delivered" @selected($workOrder->status === 'delivered')>Entregado</option>
                        </select><button class="button button-outline" type="submit">Guardar estado</button></form>
                </article>
            </aside>
        </div>
    </div>
</body>

</html>
