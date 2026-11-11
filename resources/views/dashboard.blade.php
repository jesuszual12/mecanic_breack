<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>mecanic_breack | Gestión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}" aria-label="mecanic_breack inicio">
                <span class="brand-mark">MB</span>
                <span><strong>MECANIC_BREACK</strong><small>GESTIÓN DE SERVICIO</small></span>
            </a>
            <div class="nav-label">ESPACIO DE TRABAJO</div>
            <nav class="main-nav" aria-label="Navegación principal">
                <a class="nav-link {{ request('panel', 'overview') === 'overview' ? 'active' : '' }}"
                    href="{{ route('dashboard') }}"><span class="nav-icon">⌂</span>Resumen</a>
                <a class="nav-link {{ request('panel') === 'orders' ? 'active' : '' }}"
                    href="{{ route('dashboard', ['panel' => 'orders']) }}"><span class="nav-icon">▤</span>Órdenes de
                    trabajo<span class="nav-count">{{ $activeCount }}</span></a>
                <a class="nav-link {{ request('panel') === 'customers' ? 'active' : '' }}"
                    href="{{ route('dashboard', ['panel' => 'customers']) }}#client-directory"><span
                        class="nav-icon">♙</span>Clientes</a>
                <a class="nav-link {{ request('panel') === 'vehicles' ? 'active' : '' }}"
                    href="{{ route('dashboard', ['panel' => 'vehicles']) }}#vehicle-directory"><span
                        class="nav-icon">▱</span>Vehículos</a>
                @can('manage-inventory')
                    <a class="nav-link {{ request('panel') === 'inventory' ? 'active' : '' }}"
                        href="{{ route('dashboard', ['panel' => 'inventory']) }}#inventory"><span
                            class="nav-icon">▦</span>Refacciones</a>
                @endcan
                @can('manage-finances')
                    <a class="nav-link {{ request('panel') === 'finance' ? 'active' : '' }}"
                        href="{{ route('dashboard', ['panel' => 'finance']) }}#finance"><span
                            class="nav-icon">＄</span>Finanzas</a>
                @endcan
                @can('manage-finances')
                    <a class="nav-link {{ request('panel') === 'quotes' ? 'active' : '' }}"
                        href="{{ route('dashboard', ['panel' => 'quotes']) }}#quotes"><span
                            class="nav-icon">▧</span>Cotizaciones</a>
                @endcan
                @can('manage-users')
                    <a class="nav-link {{ request('panel') === 'team' ? 'active' : '' }}"
                        href="{{ route('dashboard', ['panel' => 'team']) }}#team"><span class="nav-icon">♙</span>Equipo</a>
                @endcan
                @can('manage-finances')
                    <a class="nav-link {{ request('panel') === 'reports' ? 'active' : '' }}"
                        href="{{ route('dashboard', ['panel' => 'reports']) }}#reports"><span
                            class="nav-icon">▥</span>Reportes</a>
                @endcan
            </nav>
            <div class="sidebar-bottom">
                <div class="shop-status"><span class="status-light"></span><span><strong>Taller
                            abierto</strong><small>{{ now()->translatedFormat('l, j F') }}</small></span></div>
                <div class="user-chip"><span
                        class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ ['admin' => 'Administrador', 'manager' => 'Encargado', 'technician' => 'Técnico'][auth()->user()->role] ?? 'Personal' }}</small></span>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-button"
                            type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">↪</button></form>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div class="breadcrumb">Taller <span>/</span>
                    <strong>{{ ['orders' => 'Órdenes de trabajo', 'customers' => 'Clientes', 'vehicles' => 'Vehículos'][request('panel')] ?? 'Resumen' }}</strong>
                </div>
                <form class="topbar-search" method="GET" action="{{ route('dashboard') }}">
                    <input type="search" name="q" value="{{ request('q') }}"
                        placeholder="Buscar orden, cliente o placas"
                        aria-label="Buscar orden, cliente, teléfono o placas">
                    <button type="submit" aria-label="Buscar">⌕</button>
                </form>
                <div class="topbar-actions"><span class="today-label">{{ now()->format('d.m.Y') }}</span><a
                        class="button button-primary" href="#new-order"><span>＋</span> Nueva orden</a></div>
            </header>

            <div class="page-wrap">
                @if (session('success'))
                    <div class="flash-success" role="status">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="flash-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <section class="page-heading">
                    <div>
                        <p class="eyebrow">OPERACIÓN DEL TALLER <span class="eyebrow-rule"></span>
                            {{ now()->translatedFormat('D, d M Y') }}</p>
                        <h1>{{ ['orders' => 'Órdenes de trabajo', 'customers' => 'Clientes', 'vehicles' => 'Vehículos', 'inventory' => 'Refacciones', 'finance' => 'Finanzas', 'quotes' => 'Cotizaciones', 'team' => 'Equipo', 'reports' => 'Reportes'][request('panel')] ?? 'Resumen del taller' }}
                        </h1>
                        <p class="heading-subtitle">Control claro de cada vehículo, desde su recepción hasta la entrega.
                        </p>
                    </div>
                    <a class="button button-dark" href="#new-order"><span>＋</span> Registrar ingreso</a>
                </section>

                <section
                    class="metric-grid {{ auth()->user()->can('manage-finances') ? '' : 'metric-grid-operational' }}"
                    aria-label="Indicadores del taller">
                    <article class="metric-card metric-primary">
                        <div class="metric-top"><span>ÓRDENES ACTIVAS</span><span class="metric-symbol">▤</span></div>
                        <strong>{{ number_format($activeCount) }}</strong><small><span class="metric-dot"></span>En
                            espera o reparación</small>
                        <div class="metric-line"></div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span>INGRESOS HOY</span><span
                                class="metric-symbol symbol-green">↗</span></div>
                        <strong>{{ number_format($receivedToday) }}</strong><small>Vehículos recibidos</small>
                        <div class="metric-line line-green"></div>
                    </article>
                    <article class="metric-card">
                        <div class="metric-top"><span>ENTREGAS HOY</span><span
                                class="metric-symbol symbol-orange">↗</span></div>
                        <strong>{{ number_format($deliveredToday) }}</strong><small>Vehículos entregados</small>
                        <div class="metric-line line-orange"></div>
                    </article>
                    @can('manage-finances')
                        <article class="metric-card">
                            <div class="metric-top"><span>COBRADO ESTE MES</span><span
                                    class="metric-symbol symbol-blue">＄</span></div><strong
                                class="metric-money">${{ number_format($monthlyIncome, 2) }}</strong><small>Gastos:
                                ${{ number_format($monthlyExpenses, 2) }}</small>
                            <div class="metric-line line-blue"></div>
                        </article>
                    @endcan
                </section>

                <section class="content-grid">
                    <article class="panel orders-panel" id="orders">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow">SEGUIMIENTO</p>
                                <h2>Órdenes recientes</h2>
                            </div><a class="text-link" href="{{ route('dashboard', ['panel' => 'orders']) }}">Ver
                                todas <span>→</span></a>
                        </div>
                        <div class="table-wrap">
                            <table class="orders-table w-max min-w-full table-auto">
                                <thead>
                                    <tr>
                                        <th>ORDEN</th>
                                        <th>VEHÍCULO / CLIENTE</th>
                                        <th>INGRESO</th>
                                        <th>ESTADO</th>
                                        @can('manage-finances')
                                            <th>TOTAL</th>
                                        @endcan
                                        <th>
                                            ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($orders as $order)
                                        <tr>
                                            <td><a class="order-number"
                                                    href="{{ route('work-orders.show', $order) }}">{{ $order->number }}
                                                    ↗</a></td>
                                            <td><strong>{{ $order->vehicle->make }}
                                                    {{ $order->vehicle->model }}</strong><small
                                                    class="table-secondary">{{ $order->vehicle->plate }} ·
                                                    {{ $order->customer->name }}</small></td>
                                            <td>{{ $order->received_at->format('d/m/Y') }}</td>
                                            <td><span
                                                    class="status-pill status-{{ $order->status }}"><i></i>{{ ['pending' => 'Pendiente', 'in_progress' => 'En proceso', 'completed' => 'Terminado', 'delivered' => 'Entregado'][$order->status] ?? $order->status }}</span>
                                            </td>
                                            @can('manage-finances')
                                                <td class="amount">${{ number_format($order->total, 2) }}</td>
                                            @endcan
                                            <td>
                                                <form class="status-action" method="POST"
                                                    action="{{ route('work-orders.status', $order) }}">@csrf
                                                    @method('PATCH')<label class="sr-only"
                                                        for="status-{{ $order->id }}">Cambiar estado</label><select
                                                        id="status-{{ $order->id }}" name="status">
                                                        <option value="pending" @selected($order->status === 'pending')>Pendiente
                                                        </option>
                                                        <option value="in_progress" @selected($order->status === 'in_progress')>En
                                                            proceso</option>
                                                        <option value="completed" @selected($order->status === 'completed')>
                                                            Terminado</option>
                                                        <option value="delivered" @selected($order->status === 'delivered')>
                                                            Entregado</option>
                                                    </select><button class="button button-small"
                                                        type="submit">Guardar</button></form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ auth()->user()->can('manage-finances') ? 6 : 5 }}"
                                                class="empty-state"><span class="empty-icon">▤</span><strong>Aún no
                                                    hay órdenes</strong><small>Registra el primer vehículo para comenzar
                                                    el seguimiento.</small><a class="text-link"
                                                    href="#new-order">Crear primera orden →</a></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer"><span>{{ $orders->count() }} órdenes recientes</span><span
                                class="footer-dot"></span><span>{{ $customerCount }} clientes · {{ $vehicleCount }}
                                vehículos</span></div>
                    </article>

                    <aside class="side-column">
                        <article class="panel quick-panel" id="new-order">
                            <div class="panel-heading">
                                <div>
                                    <p class="eyebrow">RECEPCIÓN</p>
                                    <h2>Nueva orden</h2>
                                </div><span class="form-step">01 <i>/</i> 03</span>
                            </div>
                            <form class="stacked-form" method="POST" action="{{ route('work-orders.store') }}">
                                @csrf
                                <label>Cliente<select name="customer_id" id="order-customer" required>
                                        <option value="">Selecciona un cliente</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                                {{ $customer->name }} · {{ $customer->phone }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Vehículo<select name="vehicle_id" id="order-vehicle" required>
                                        <option value="">Selecciona un vehículo</option>
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}"
                                                data-customer="{{ $vehicle->customer_id }}"
                                                @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->make }}
                                                {{ $vehicle->model }} · {{ $vehicle->plate }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Motivo de ingreso
                                    <textarea name="complaint" rows="2" placeholder="Describe brevemente el problema" required>{{ old('complaint') }}</textarea>
                                </label>
                                @can('manage-finances')
                                    <div class="form-row"><label>Mano de obra <span class="input-prefix"><i>$</i><input
                                                    name="labor_cost" type="number" min="0" step="0.01"
                                                    value="{{ old('labor_cost', 0) }}"></span></label><label>Refacciones
                                            <span class="input-prefix"><i>$</i><input name="parts_cost" type="number"
                                                    min="0" step="0.01"
                                                    value="{{ old('parts_cost', 0) }}"></span></label></div>
                                    <div class="form-row"><label>Otros conceptos <span class="input-prefix"><i>$</i><input
                                                    name="other_cost" type="number" min="0" step="0.01"
                                                    value="{{ old('other_cost', 0) }}"></span></label><label>Anticipo
                                            <span class="input-prefix"><i>$</i><input name="initial_payment"
                                                    type="number" min="0" step="0.01"
                                                    value="{{ old('initial_payment', 0) }}"></span></label></div>
                                    <div class="form-row"><label>Garantía (días)<input name="warranty_days"
                                                type="number" min="0" max="3650"
                                                value="{{ old('warranty_days', 0) }}"></label><label>Condiciones<input
                                                name="warranty_terms" type="text" maxlength="2000"
                                                placeholder="Opcional"></label></div>
                                @endcan
                                <button class="button button-primary button-wide" type="submit">Crear orden de
                                    trabajo <span>→</span></button>
                            </form>
                            <p class="form-note"><span>▣</span> El ingreso queda registrado con fecha y hora.</p>
                        </article>
                    </aside>
                </section>

                <section class="lower-grid" id="register-customer">
                    <article class="panel form-panel">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow">DIRECTORIO</p>
                                <h2>Registrar cliente</h2>
                            </div><span class="section-index">01</span>
                        </div>
                        <form method="POST" action="{{ route('customers.store') }}" class="compact-form">@csrf<div
                                class="form-row"><label>Nombre completo<input name="name" required maxlength="160"
                                        placeholder="Nombre del cliente"></label><label>Teléfono<input name="phone"
                                        required maxlength="30" placeholder="10 dígitos"></label></div>
                            <div class="form-row"><label>Correo electrónico<input name="email" type="email"
                                        maxlength="190"
                                        placeholder="cliente@correo.com"></label><label>Domicilio<input name="address"
                                        maxlength="1000" placeholder="Calle, colonia, ciudad"></label></div><button
                                class="button button-outline" type="submit">Guardar cliente <span>→</span></button>
                        </form>
                    </article>
                    <article class="panel form-panel" id="register-vehicle">
                        <div class="panel-heading">
                            <div>
                                <p class="eyebrow">UNIDAD</p>
                                <h2>Registrar vehículo</h2>
                            </div><span class="section-index">02</span>
                        </div>
                        <form method="POST" action="{{ route('vehicles.store') }}" class="compact-form">
                            @csrf<label>Propietario<select name="customer_id" required>
                                    <option value="">Selecciona un cliente</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <div class="form-row"><label>Marca<input name="make" required maxlength="80"
                                        placeholder="Toyota"></label><label>Modelo<input name="model" required
                                        maxlength="100" placeholder="Corolla"></label></div>
                            <div class="form-row"><label>Placas<input name="plate" required maxlength="20"
                                        placeholder="ABC-123-A"></label><label>Año<input name="year"
                                        type="number" min="1900" max="{{ now()->year + 1 }}"
                                        placeholder="2022"></label></div>
                            <div class="form-row"><label>Color<input name="color" maxlength="50"
                                        placeholder="Plata"></label><label>VIN<input name="vin" maxlength="40"
                                        placeholder="Número de serie"></label></div><button
                                class="button button-outline" type="submit">Guardar vehículo <span>→</span></button>
                        </form>
                    </article>
                </section>

                @if (request('panel') === 'customers' || request('q'))
                    <section class="module-section" id="client-directory">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">DIRECTORIO</p>
                                <h2>Clientes</h2>
                            </div><span>{{ $customerDirectory->count() }} resultados</span>
                        </div>
                        <article class="panel data-panel">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>CLIENTE</th>
                                            <th>TELÉFONO</th>
                                            <th>CORREO</th>
                                            <th>VEHÍCULOS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($customerDirectory as $customer)
                                            <tr>
                                                <td><strong>{{ $customer->name }}</strong></td>
                                                <td>{{ $customer->phone }}</td>
                                                <td>{{ $customer->email ?: '—' }}</td>
                                                <td>{{ $customer->vehicles_count }}</td>
                                        </tr>@empty<tr>
                                                <td colspan="4" class="empty-row">No se encontraron clientes.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    </section>
                @endif
                @if (request('panel') === 'vehicles' || request('q'))
                    <section class="module-section" id="vehicle-directory">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">UNIDADES</p>
                                <h2>Vehículos</h2>
                            </div><span>{{ $vehicleDirectory->count() }} resultados</span>
                        </div>
                        <article class="panel data-panel">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>VEHÍCULO</th>
                                            <th>PLACAS / SERIE</th>
                                            <th>PROPIETARIO</th>
                                            <th>ÚLTIMO INGRESO</th>
                                            <th>HISTORIAL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($vehicleDirectory as $vehicle)
                                            <tr>
                                                <td><strong>{{ $vehicle->make }} {{ $vehicle->model }}
                                                        {{ $vehicle->year }}</strong><small
                                                        class="table-secondary">{{ $vehicle->color ?: 'Color no registrado' }}</small>
                                                </td>
                                                <td>{{ $vehicle->plate }}<small
                                                        class="table-secondary">{{ $vehicle->vin ?: 'Sin VIN' }}</small>
                                                </td>
                                                <td>{{ $vehicle->customer->name }}<small
                                                        class="table-secondary">{{ $vehicle->customer->phone }}</small>
                                                </td>
                                                <td>{{ $vehicle->work_orders_max_received_at ? \Illuminate\Support\Carbon::parse($vehicle->work_orders_max_received_at)->format('d/m/Y') : 'Sin ingresos' }}
                                                </td>
                                                <td><a class="text-link"
                                                        href="{{ route('vehicles.show', $vehicle) }}">Ver historial
                                                        →</a></td>
                                        </tr>@empty<tr>
                                                <td colspan="5" class="empty-row">No se encontraron vehículos.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    </section>
                @endif

                @can('manage-finances')<section class="module-section" id="quotes">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">PRESUPUESTO PREVIO</p>
                                <h2>Cotizaciones</h2>
                            </div><span>{{ $quotes->count() }} recientes</span>
                        </div>
                        <div class="module-grid">
                            <article class="panel data-panel">
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>COTIZACIÓN</th>
                                                <th>VEHÍCULO / CLIENTE</th>
                                                <th>VIGENCIA</th>
                                                <th>TOTAL</th>
                                                <th>ESTADO</th>
                                                <th>ACCIÓN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($quotes as $quote)
                                                <tr>
                                                    <td><strong>{{ $quote->number }}</strong><small
                                                            class="table-secondary">{{ \Illuminate\Support\Str::limit($quote->complaint, 55) }}</small>
                                                    </td>
                                                    <td>{{ $quote->vehicle->make }} {{ $quote->vehicle->model }}<small
                                                            class="table-secondary">{{ $quote->vehicle->plate }} ·
                                                            {{ $quote->customer->name }}</small></td>
                                                    <td>{{ $quote->valid_until->format('d/m/Y') }}</td>
                                                    <td><strong>${{ number_format($quote->total, 2) }}</strong></td>
                                                    <td><span
                                                            class="status-pill {{ ['drafted' => 'status-pending', 'authorized' => 'status-delivered', 'converted' => 'status-in_progress', 'rejected' => 'status-pending'][$quote->status] ?? 'status-pending' }}"><i></i>{{ ['drafted' => 'Pendiente', 'authorized' => 'Autorizada', 'converted' => 'Convertida', 'rejected' => 'Rechazada'][$quote->status] ?? $quote->status }}</span>
                                                    </td>
                                                    <td>
                                                        @if ($quote->status === 'drafted' && !$quote->valid_until->isBefore(today()))
                                                            <form class="quote-action" method="POST"
                                                                action="{{ route('quotes.authorize', $quote) }}">@csrf
                                                                @method('PATCH')<input name="authorization_note"
                                                                    maxlength="190"
                                                                    value="Autorización registrada por {{ auth()->user()->name }}"
                                                                    required><button class="button button-small"
                                                                    type="submit">Autorizar</button></form>
                                                        @elseif ($quote->status === 'authorized' && !$quote->valid_until->isBefore(today()))
                                                            <form method="POST"
                                                                action="{{ route('quotes.convert', $quote) }}">
                                                                @csrf<button class="button button-small"
                                                                    type="submit">Crear orden →</button></form>
                                                        @elseif ($quote->work_order_id)
                                                            <a class="text-link"
                                                                href="{{ route('work-orders.show', $quote->work_order_id) }}">{{ $quote->work_order_id }}
                                                            · Abrir</a>@else<span
                                                                class="table-secondary">Vencida</span>
                                                        @endif
                                                    </td>
                                            </tr>@empty<tr>
                                                    <td colspan="6" class="empty-row">Todavía no hay cotizaciones.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                            <article class="panel form-panel">
                                <div class="panel-heading">
                                    <div>
                                        <p class="eyebrow">NUEVO PRESUPUESTO</p>
                                        <h2>Crear cotización</h2>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('quotes.store') }}" class="compact-form">
                                    @csrf<label>Cliente<select name="customer_id" id="quote-customer" required>
                                            <option value="">Selecciona un cliente</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </label><label>Vehículo<select name="vehicle_id" id="quote-vehicle" required>
                                            <option value="">Selecciona un vehículo</option>
                                            @foreach ($vehicles as $vehicle)
                                                <option value="{{ $vehicle->id }}"
                                                    data-customer="{{ $vehicle->customer_id }}">{{ $vehicle->make }}
                                                    {{ $vehicle->model }} · {{ $vehicle->plate }}</option>
                                            @endforeach
                                        </select>
                                    </label><label>Trabajos solicitados
                                        <textarea name="complaint" rows="2" maxlength="3000" required></textarea>
                                    </label>
                                    <div class="form-row"><label>Mano de obra<input name="labor_cost" type="number"
                                                min="0" step="0.01" value="0"
                                                required></label><label>Refacciones<input name="parts_cost" type="number"
                                                min="0" step="0.01" value="0" required></label></div>
                                    <div class="form-row"><label>Otros conceptos<input name="other_cost" type="number"
                                                min="0" step="0.01" value="0"
                                                required></label><label>Vigencia (días)<input name="valid_days"
                                                type="number" min="1" max="365" value="15"
                                                required></label></div><button class="button button-outline"
                                        type="submit">Guardar cotización →</button>
                                </form>
                            </article>
                        </div>
                </section>@endcan

                @can('manage-inventory')<section class="module-section" id="inventory">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">ALMACÉN</p>
                                <h2>Refacciones e inventario</h2>
                            </div><span>{{ $inventoryItems->count() }} artículos</span>
                        </div>
                        <div class="module-grid">
                            <article class="panel data-panel">
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>REFACCIÓN</th>
                                                <th>PROVEEDOR / SKU</th>
                                                <th>EXISTENCIA</th>
                                                <th>COSTO</th>
                                                <th>PRECIO</th>
                                                <th>MOVIMIENTO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($inventoryItems as $item)
                                                <tr>
                                                    <td><strong>{{ $item->name }}</strong></td>
                                                    <td>{{ $item->supplier ?: '—' }}<small
                                                            class="table-secondary">{{ $item->sku ?: 'Sin SKU' }}</small>
                                                    </td>
                                                    <td><span
                                                            class="stock-value {{ $item->stock <= $item->minimum_stock ? 'stock-low' : '' }}">{{ number_format($item->stock, 2) }}
                                                            u.</span><small class="table-secondary">Mínimo
                                                            {{ number_format($item->minimum_stock, 2) }}</small></td>
                                                    <td>${{ number_format($item->unit_cost, 2) }}</td>
                                                    <td>${{ number_format($item->sale_price, 2) }}</td>
                                                    <td>
                                                        <details class="row-menu">
                                                            <summary aria-label="Registrar movimiento">···</summary>
                                                            <form method="POST"
                                                                action="{{ route('inventory.movements.store', $item) }}">
                                                                @csrf<label
                                                                    for="direction-{{ $item->id }}">Movimiento</label><select
                                                                    name="direction" id="direction-{{ $item->id }}">
                                                                    <option value="in">Entrada / compra</option>
                                                                    <option value="out">Salida / consumo</option>
                                                                </select><label
                                                                    for="quantity-{{ $item->id }}">Cantidad</label><input
                                                                    id="quantity-{{ $item->id }}" type="number"
                                                                    name="quantity" min="0.01" step="0.01"
                                                                    required><label for="order-{{ $item->id }}">Orden
                                                                    (opcional)</label><select name="work_order_id"
                                                                    id="order-{{ $item->id }}">
                                                                    <option value="">Sin orden</option>
                                                                    @foreach ($orders as $order)
                                                                        <option value="{{ $order->id }}">
                                                                            {{ $order->number }} ·
                                                                            {{ $order->vehicle->plate }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="text" name="notes" maxlength="190"
                                                                    placeholder="Nota"><button class="button button-small"
                                                                    type="submit">Guardar</button>
                                                            </form>
                                                        </details>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="empty-row">No hay refacciones registradas.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                            <article class="panel form-panel">
                                <div class="panel-heading">
                                    <div>
                                        <p class="eyebrow">ALTA DE ARTÍCULO</p>
                                        <h2>Agregar refacción</h2>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('inventory.store') }}" class="compact-form">
                                    @csrf<label>Nombre<input name="name" required maxlength="160"
                                            placeholder="Filtro de aceite"></label>
                                    <div class="form-row"><label>SKU<input name="sku" maxlength="80"
                                                placeholder="Opcional"></label><label>Proveedor<input name="supplier"
                                                maxlength="160" placeholder="Proveedor"></label></div>
                                    <div class="form-row"><label>Existencia inicial<input type="number" name="stock"
                                                min="0" step="0.01" value="0"
                                                required></label><label>Stock mínimo<input type="number"
                                                name="minimum_stock" min="0" step="0.01" value="1"
                                                required></label></div>
                                    <div class="form-row"><label>Costo unitario<input type="number" name="unit_cost"
                                                min="0" step="0.01" value="0"
                                                required></label><label>Precio de venta<input type="number"
                                                name="sale_price" min="0" step="0.01" value="0"
                                                required></label></div><button class="button button-outline"
                                        type="submit">Guardar artículo →</button>
                                </form>
                            </article>
                        </div>
                </section>@endcan

                @can('manage-finances')<section class="module-section" id="finance">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">CONTROL FINANCIERO</p>
                                <h2>Ingresos, saldos y gastos</h2>
                            </div><span>Mes actual</span>
                        </div>
                        <div class="finance-strip">
                            <div><small>INGRESOS COBRADOS</small><strong>${{ number_format($monthlyIncome, 2) }}</strong>
                            </div>
                            <div><small>GASTOS
                                    OPERATIVOS</small><strong>${{ number_format($monthlyExpenses, 2) }}</strong></div>
                            <div><small>NÓMINA PAGADA</small><strong>${{ number_format($monthlyPayroll, 2) }}</strong>
                            </div>
                            <div><small>SALDO DE ÓRDENES</small><strong>${{ number_format($openBalance, 2) }}</strong>
                            </div>
                            <div><small>SALDO DE
                                    CONVENIOS</small><strong>${{ number_format($openDebtBalance, 2) }}</strong></div>
                        </div>
                        <div class="module-grid">
                            <article class="panel data-panel">
                                <div class="panel-heading">
                                    <div>
                                        <p class="eyebrow">CUENTAS POR COBRAR</p>
                                        <h2>Órdenes con saldo</h2>
                                    </div>
                                </div>
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>ORDEN</th>
                                                <th>CLIENTE</th>
                                                <th>TOTAL</th>
                                                <th>PAGADO</th>
                                                <th>SALDO</th>
                                                <th>ABONO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($balanceOrders as $order)
                                                <tr>
                                                    <td><strong>{{ $order->number }}</strong></td>
                                                    <td>{{ $order->customer->name }}</td>
                                                    <td>${{ number_format($order->total, 2) }}</td>
                                                    <td>${{ number_format($order->paid, 2) }}</td>
                                                    <td class="balance-amount">
                                                        ${{ number_format($order->total - $order->paid, 2) }}</td>
                                                    <td>
                                                        <form class="inline-payment" method="POST"
                                                            action="{{ route('payments.store', $order) }}">@csrf<input
                                                                aria-label="Monto del abono" name="amount"
                                                                type="number" min="0.01"
                                                                max="{{ number_format($order->total - $order->paid, 2, '.', '') }}"
                                                                step="0.01" placeholder="$" required><button
                                                                class="button button-small" type="submit">Cobrar</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="empty-row">No hay saldos pendientes.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                            <article class="panel form-panel">
                                <div class="panel-heading">
                                    <div>
                                        <p class="eyebrow">EGRESO</p>
                                        <h2>Registrar gasto</h2>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('expenses.store') }}" class="compact-form">
                                    @csrf<label>Concepto<input name="concept" required maxlength="160"
                                            placeholder="Compra de herramienta"></label>
                                    <div class="form-row"><label>Categoría<select name="category">
                                                <option value="purchase">Compra</option>
                                                <option value="rent">Renta</option>
                                                <option value="utilities">Servicios</option>
                                                <option value="other">Imprevisto / otro</option>
                                            </select></label><label>Monto<input name="amount" type="number"
                                                min="0.01" step="0.01" required></label></div>
                                    <label>Proveedor<input name="supplier" maxlength="160"
                                            placeholder="Opcional"></label><button class="button button-outline"
                                        type="submit">Registrar gasto →</button>
                                </form>
                                <div class="recent-expenses"><strong>Últimos gastos</strong>
                                    @forelse ($expenses as $expense)
                                        <div>
                                            <span>{{ $expense->concept }}<small>{{ \Illuminate\Support\Carbon::parse($expense->spent_at)->format('d/m/Y') }}</small></span><b>${{ number_format($expense->amount, 2) }}</b>
                                    </div>@empty<small>No hay gastos registrados.</small>
                                    @endforelse
                                </div>
                            </article>
                        </div>
                    </section>
                    <section class="module-section" id="agreements">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">CRÉDITO Y CONVENIOS</p>
                                <h2>Cuentas por cobrar</h2>
                            </div><span>{{ $debtAccounts->count() }} abiertas</span>
                        </div>
                        <div class="module-grid">
                            <article class="panel data-panel">
                                <div class="table-wrap">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>CLIENTE / EMPRESA</th>
                                                <th>REFERENCIA</th>
                                                <th>GENERADO</th>
                                                <th>VENCIMIENTO</th>
                                                <th>SALDO</th>
                                                <th>ABONO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($debtAccounts as $account)
                                                <tr>
                                                    <td><strong>{{ $account->customer?->name ?: $account->company_name }}</strong><small
                                                            class="table-secondary">{{ $account->agreement ?: 'Convenio de crédito' }}</small>
                                                    </td>
                                                    <td>{{ $account->reference ?: '—' }}</td>
                                                    <td>{{ $account->created_at->format('d/m/Y') }}</td>
                                                    <td>{{ $account->due_at?->format('d/m/Y') ?: 'Sin fecha' }}</td>
                                                    <td class="balance-amount">${{ number_format($account->balance, 2) }}
                                                    </td>
                                                    <td>
                                                        <form class="inline-payment" method="POST"
                                                            action="{{ route('debt-accounts.payments.store', $account) }}">
                                                            @csrf<input aria-label="Abono de convenio" name="amount"
                                                                type="number" min="0.01"
                                                                max="{{ number_format($account->balance, 2, '.', '') }}"
                                                                step="0.01" required><button
                                                                class="button button-small" type="submit">Abonar</button>
                                                        </form>
                                                    </td>
                                            </tr>@empty<tr>
                                                    <td colspan="6" class="empty-row">No hay convenios pendientes.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                            <article class="panel form-panel">
                                <div class="panel-heading">
                                    <div>
                                        <p class="eyebrow">NUEVO CRÉDITO</p>
                                        <h2>Registrar convenio</h2>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('debt-accounts.store') }}" class="compact-form">
                                    @csrf<label>Cliente (opcional)<select name="customer_id">
                                            <option value="">Empresa sin ficha de cliente</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </label><label>Empresa<input name="company_name" maxlength="160"
                                            placeholder="Obligatoria si no eliges cliente"></label>
                                    <div class="form-row"><label>Referencia<input name="reference" maxlength="100"
                                                placeholder="Folio o contrato"></label><label>Monto total<input
                                                name="amount" type="number" min="0.01" step="0.01"
                                                required></label></div>
                                    <div class="form-row"><label>Fecha estimada de liquidación<input name="due_at"
                                                type="date"></label><label>Convenio<input name="agreement"
                                                maxlength="190" placeholder="Condiciones resumidas"></label></div><button
                                        class="button button-outline" type="submit">Guardar convenio →</button>
                                </form>
                            </article>
                        </div>
                </section>@endcan

                @can('manage-users')<section class="module-section" id="team">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">PERSONAL</p>
                                <h2>Equipo y nómina</h2>
                            </div><span>{{ $employees->count() }} activos</span>
                        </div>
                        <div class="module-grid">
                            <article class="panel data-panel">
                                <div class="table-wrap team-table-wrap w-full min-w-0 max-w-full overflow-x-auto">
                                    <table class="mobile-card-table w-full">
                                        <thead>
                                            <tr>
                                                <th>EMPLEADO</th>
                                                <th>PUESTO</th>
                                                <th>SUELDO BASE</th>
                                                <th>PERIODICIDAD</th>
                                                <th>REGISTRAR PAGO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($employees as $employee)
                                                <tr>
                                                    <td data-label="Empleado">
                                                        <strong>{{ $employee->name }}</strong><small
                                                            class="table-secondary">{{ $employee->phone ?: 'Sin teléfono' }}</small>
                                                    </td>
                                                    <td data-label="Puesto">{{ $employee->position }}</td>
                                                    <td data-label="Sueldo base">
                                                        ${{ number_format($employee->salary, 2) }}</td>
                                                    <td data-label="Periodicidad">
                                                        {{ ['weekly' => 'Semanal', 'biweekly' => 'Quincenal', 'monthly' => 'Mensual'][$employee->pay_frequency] ?? $employee->pay_frequency }}
                                                    </td>
                                                    <td data-label="Registrar pago">
                                                        <form class="payroll-form" method="POST"
                                                            action="{{ route('payroll.store', $employee->id) }}">
                                                            @csrf<input aria-label="Monto de nómina" name="amount"
                                                                type="number" min="0.01" step="0.01"
                                                                placeholder="Monto" required><input
                                                                aria-label="Periodo cubierto" name="paid_for"
                                                                type="date" value="{{ now()->toDateString() }}"
                                                                required><input aria-label="Nota del pago" name="notes"
                                                                maxlength="190" placeholder="Nota"><button
                                                                class="button button-small" type="submit">Registrar
                                                                pago</button></form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="empty-row">Aún no hay empleados registrados.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </article>
                            <article class="panel form-panel">
                                <div class="panel-heading">
                                    <div>
                                        <p class="eyebrow">ALTA DE PERSONAL</p>
                                        <h2>Registrar empleado</h2>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('employees.store') }}" class="compact-form">@csrf
                                    <div class="form-row"><label>Nombre<input name="name" maxlength="160"
                                                required></label><label>Puesto<input name="position" maxlength="100"
                                                required></label></div>
                                    <div class="form-row"><label>Teléfono<input name="phone"
                                                maxlength="30"></label><label>Sueldo base<input name="salary"
                                                type="number" min="0" step="0.01" required></label></div>
                                    <label>Periodicidad<select name="pay_frequency">
                                            <option value="weekly">Semanal</option>
                                            <option value="biweekly">Quincenal</option>
                                            <option value="monthly">Mensual</option>
                                        </select></label>
                                    <div class="account-fields">
                                        <p>CUENTA DE ACCESO <small>Opcional</small></p><label>Correo del usuario<input
                                                name="email" type="email" maxlength="190"
                                                placeholder="Dejar vacío si no requiere acceso"></label>
                                        <div class="form-row"><label>Contraseña inicial<input name="password"
                                                    type="password" minlength="10"
                                                    autocomplete="new-password"></label><label>Confirmar contraseña<input
                                                    name="password_confirmation" type="password"
                                                    autocomplete="new-password"></label></div><label>Rol<select
                                                name="role">
                                                <option value="technician">Técnico</option>
                                                <option value="manager">Encargado</option>
                                            </select></label>
                                    </div><button class="button button-outline" type="submit">Guardar empleado →</button>
                                </form>
                            </article>
                        </div>
                </section>@endcan

                <section class="module-section" id="warranties">
                    <div class="module-title">
                        <div>
                            <p class="eyebrow">POSTVENTA</p>
                            <h2>Garantías vigentes y vencidas</h2>
                        </div><span>Trabajos entregados</span>
                    </div>
                    <article class="panel data-panel">
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ORDEN</th>
                                        <th>VEHÍCULO</th>
                                        <th>CLIENTE</th>
                                        <th>VIGENCIA</th>
                                        <th>CONDICIONES</th>
                                        <th>ESTADO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($warranties as $warranty)
                                        @php($warrantyEnd = $warranty->delivered_at->copy()->addDays($warranty->warranty_days))
                                        <tr>
                                            <td><strong>{{ $warranty->number }}</strong></td>
                                            <td>{{ $warranty->vehicle->make }} {{ $warranty->vehicle->model }} ·
                                                {{ $warranty->vehicle->plate }}</td>
                                            <td>{{ $warranty->customer->name }}</td>
                                            <td>{{ $warranty->delivered_at->format('d/m/Y') }} -
                                                {{ $warrantyEnd->format('d/m/Y') }}</td>
                                            <td>{{ $warranty->warranty_terms ?: 'Sin condiciones adicionales' }}</td>
                                            <td><span
                                                    class="status-pill {{ $warrantyEnd->isFuture() ? 'status-delivered' : 'status-pending' }}"><i></i>{{ $warrantyEnd->isFuture() ? 'Vigente' : 'Vencida' }}</span>
                                            </td>
                                        </tr>
                                    @empty<tr>
                                            <td colspan="6" class="empty-row">No hay garantías registradas para
                                                trabajos entregados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>

                @can('manage-finances')
                    <section class="module-section" id="reports">
                        <div class="module-title">
                            <div>
                                <p class="eyebrow">RENDIMIENTO</p>
                                <h2>Resumen {{ now()->year }}</h2>
                            </div><span>Acumulado anual</span>
                        </div>
                        <div class="report-grid">
                            <article class="report-card"><small>VEHÍCULOS
                                    ATENDIDOS</small><strong>{{ number_format($yearVehicles) }}</strong><span>{{ number_format($yearDelivered) }}
                                    entregados</span></article>
                            <article class="report-card"><small>INGRESOS
                                    COBRADOS</small><strong>${{ number_format($yearIncome, 2) }}</strong><span>Pagos
                                    registrados en el año</span></article>
                            <article class="report-card"><small>GASTOS +
                                    NÓMINA</small><strong>${{ number_format($yearExpenses + $yearPayroll, 2) }}</strong><span>${{ number_format($yearExpenses, 2) }}
                                    gastos · ${{ number_format($yearPayroll, 2) }} nómina</span></article>
                            <article class="report-card report-net"><small>FLUJO NETO
                                    COBRADO</small><strong>${{ number_format($yearIncome - $yearExpenses - $yearPayroll, 2) }}</strong><span>Ingresos
                                    cobrados menos egresos registrados</span></article>
                        </div>
                        <p class="report-note">El flujo neto es un resumen de caja; no sustituye un estado contable ni
                            considera impuestos o cuentas por cobrar.</p>
                    </section>
                @endcan

                <footer class="page-footer"><span>MECANIC_BREACK <i>·</i> CONTROL DE SERVICIO</span><span>Los
                        movimientos quedan asociados a su registro de auditoría.</span></footer>
            </div>
        </main>
    </div>
</body>

</html>
