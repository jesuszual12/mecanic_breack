<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DebtAccount;
use App\Models\DebtPayment;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkshopController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $search = trim((string) request('q', ''));
        $canManageFinances = auth()->user()->can('manage-finances');
        $canManageUsers = auth()->user()->can('manage-users');
        $orderQuery = WorkOrder::with(['customer', 'vehicle']);
        $customerDirectory = Customer::withCount('vehicles')->orderBy('name');
        $vehicleDirectory = Vehicle::with('customer')->withMax('workOrders', 'received_at')->latest();

        if ($search !== '') {
            $orderQuery->where(function ($query) use ($search): void {
                $query->where('number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'))
                    ->orWhereHas('vehicle', fn ($vehicleQuery) => $vehicleQuery->where('plate', 'like', '%'.$search.'%')->orWhere('make', 'like', '%'.$search.'%')->orWhere('model', 'like', '%'.$search.'%'));
            });
            $customerDirectory->where(function ($query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
            $vehicleDirectory->where(function ($query) use ($search): void {
                $query->where('plate', 'like', '%'.$search.'%')
                    ->orWhere('vin', 'like', '%'.$search.'%')
                    ->orWhere('make', 'like', '%'.$search.'%')
                    ->orWhere('model', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'));
            });
        }

        return view('dashboard', [
            'customerCount' => Customer::count(),
            'vehicleCount' => Vehicle::count(),
            'activeCount' => WorkOrder::whereIn('status', ['pending', 'in_progress'])->count(),
            'receivedToday' => WorkOrder::whereDate('received_at', $today)->count(),
            'deliveredToday' => WorkOrder::whereDate('delivered_at', $today)->count(),
            'monthlyIncome' => $canManageFinances
                ? (float) Payment::where('paid_at', '>=', $monthStart)->sum('amount') + (float) DebtPayment::where('paid_at', '>=', $monthStart)->sum('amount')
                : 0,
            'monthlyExpenses' => $canManageFinances ? DB::table('expenses')->where('spent_at', '>=', $monthStart)->sum('amount') : 0,
            'monthlyPayroll' => $canManageFinances ? DB::table('payroll_payments')->where('paid_at', '>=', $monthStart)->sum('amount') : 0,
            'orders' => $orderQuery->latest('received_at')->limit($search === '' ? 10 : 100)->get(),
            'balanceOrders' => $canManageFinances ? WorkOrder::with('customer')->whereColumn('paid', '<', 'total')->latest('received_at')->get() : collect(),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone']),
            'vehicles' => Vehicle::with('customer')->orderBy('plate')->get(),
            'customerDirectory' => $customerDirectory->get(),
            'vehicleDirectory' => $vehicleDirectory->get(),
            'inventoryItems' => InventoryItem::orderBy('name')->get(),
            'expenses' => $canManageFinances ? DB::table('expenses')->latest('spent_at')->limit(6)->get() : collect(),
            'employees' => $canManageUsers ? DB::table('employees')->where('is_active', true)->orderBy('name')->get() : collect(),
            'warranties' => WorkOrder::with(['customer', 'vehicle'])->where('status', 'delivered')->where('warranty_days', '>', 0)->latest('delivered_at')->limit(8)->get(),
            'yearIncome' => $canManageFinances
                ? (float) Payment::whereYear('paid_at', now()->year)->sum('amount') + (float) DebtPayment::whereYear('paid_at', now()->year)->sum('amount')
                : 0,
            'yearExpenses' => $canManageFinances ? DB::table('expenses')->whereYear('spent_at', now()->year)->sum('amount') : 0,
            'yearPayroll' => $canManageFinances ? DB::table('payroll_payments')->whereYear('paid_at', now()->year)->sum('amount') : 0,
            'yearVehicles' => WorkOrder::whereYear('received_at', now()->year)->distinct('vehicle_id')->count('vehicle_id'),
            'yearDelivered' => WorkOrder::where('status', 'delivered')->whereYear('delivered_at', now()->year)->count(),
            'openBalance' => $canManageFinances ? WorkOrder::whereColumn('paid', '<', 'total')->sum(DB::raw('total - paid')) : 0,
            'openDebtBalance' => $canManageFinances ? DebtAccount::where('status', 'open')->sum('balance') : 0,
            'debtAccounts' => $canManageFinances ? DebtAccount::with(['customer', 'payments' => fn ($query) => $query->latest('paid_at')->limit(3)])->where('status', 'open')->latest()->get() : collect(),
            'recentDebtPayments' => $canManageFinances ? DebtPayment::with('account.customer')->latest('paid_at')->limit(10)->get() : collect(),
            'quotes' => $canManageFinances ? Quote::with(['customer', 'vehicle'])->latest()->limit(8)->get() : collect(),
        ]);
    }

    public function showWorkOrder(WorkOrder $workOrder): View
    {
        $workOrder->load(['customer', 'vehicle.customer', 'items', 'payments' => fn ($query) => $query->latest('paid_at')]);

        return view('work-orders.show', compact('workOrder'));
    }

    public function printReceipt(WorkOrder $workOrder): View
    {
        $workOrder->load(['customer', 'vehicle', 'items', 'payments' => fn ($query) => $query->orderBy('paid_at')]);

        return view('work-orders.receipt', [
            'workOrder' => $workOrder,
            'workshop' => config('workshop'),
        ]);
    }

    public function showVehicle(Vehicle $vehicle): View
    {
        $vehicle->load(['customer', 'workOrders' => fn ($query) => $query->with('items')->latest('received_at')]);

        return view('vehicles.history', compact('vehicle'));
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = Customer::create($data);
        $this->audit('customer.created', $customer, ['name' => $customer->name]);

        return redirect()->route('dashboard', ['panel' => 'customers'])->with('success', 'Cliente registrado.');
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'make' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'between:1900,'.(now()->year + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'plate' => ['required', 'string', 'max:20', 'unique:vehicles,plate'],
            'vin' => ['nullable', 'string', 'max:40', 'unique:vehicles,vin'],
            'odometer' => ['nullable', 'integer', 'min:0'],
        ]);

        $vehicle = Vehicle::create($data);
        $this->audit('vehicle.created', $vehicle, ['plate' => $vehicle->plate]);

        return redirect()->route('dashboard', ['panel' => 'vehicles'])->with('success', 'Vehículo registrado.');
    }

    public function storeWorkOrder(Request $request): RedirectResponse
    {
        $canManageFinances = $request->user()->can('manage-finances');
        $restrictedFields = ['labor_cost', 'parts_cost', 'other_cost', 'initial_payment', 'warranty_days', 'warranty_terms'];
        $submittedRestrictedFields = array_intersect($restrictedFields, array_keys($request->all()));

        if (! $canManageFinances && $submittedRestrictedFields !== []) {
            throw ValidationException::withMessages(array_fill_keys(
                $submittedRestrictedFields,
                'Solo administración puede asignar importes y garantías a una orden.'
            ));
        }

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')->where('customer_id', $request->input('customer_id')),
            ],
            'complaint' => ['required', 'string', 'max:3000'],
            'labor_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'parts_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'other_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'initial_payment' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'warranty_days' => ['nullable', 'integer', 'between:0,3650'],
            'warranty_terms' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = DB::transaction(function () use ($data, $canManageFinances): WorkOrder {
            $total = (float) ($data['labor_cost'] ?? 0) + (float) ($data['parts_cost'] ?? 0) + (float) ($data['other_cost'] ?? 0);
            $paid = (float) ($data['initial_payment'] ?? 0);

            if ($paid > $total) {
                throw ValidationException::withMessages([
                    'initial_payment' => 'El anticipo no puede superar el total de la orden.',
                ]);
            }

            $order = WorkOrder::create([
                'number' => 'OT-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(3))),
                'customer_id' => $data['customer_id'],
                'vehicle_id' => $data['vehicle_id'],
                'created_by' => auth()->id(),
                'status' => 'pending',
                'complaint' => $data['complaint'],
                'received_at' => now(),
                'labor_cost' => $canManageFinances ? ($data['labor_cost'] ?? 0) : 0,
                'parts_cost' => $canManageFinances ? ($data['parts_cost'] ?? 0) : 0,
                'other_cost' => $canManageFinances ? ($data['other_cost'] ?? 0) : 0,
                'total' => $total,
                'paid' => $paid,
                'warranty_days' => $canManageFinances ? ($data['warranty_days'] ?? 0) : 0,
                'warranty_terms' => $canManageFinances ? ($data['warranty_terms'] ?? null) : null,
            ]);

            if ($paid > 0) {
                Payment::create([
                    'work_order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'amount' => $paid,
                    'method' => 'cash',
                    'paid_at' => now(),
                ]);
            }

            foreach ([
                ['type' => 'labor', 'description' => 'Mano de obra inicial', 'amount' => (float) ($data['labor_cost'] ?? 0)],
                ['type' => 'part', 'description' => 'Refacciones iniciales', 'amount' => (float) ($data['parts_cost'] ?? 0)],
                ['type' => 'service', 'description' => 'Otros conceptos iniciales', 'amount' => (float) ($data['other_cost'] ?? 0)],
            ] as $baseItem) {
                if ($baseItem['amount'] > 0) {
                    $order->items()->create([
                        'type' => $baseItem['type'],
                        'description' => $baseItem['description'],
                        'quantity' => 1,
                        'unit_price' => $baseItem['amount'],
                        'unit_cost' => 0,
                    ]);
                }
            }

            $this->audit('work_order.created', $order, ['number' => $order->number, 'total' => $total]);

            return $order;
        });

        return redirect()->route('dashboard', ['panel' => 'orders'])->with('success', "Orden {$order->number} creada.");
    }

    public function updateStatus(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'delivered'])],
        ]);

        $previousStatus = $workOrder->status;
        $workOrder->status = $data['status'];
        $workOrder->delivered_at = $data['status'] === 'delivered' ? ($workOrder->delivered_at ?? now()) : null;
        $workOrder->save();
        $this->audit('work_order.status_changed', $workOrder, ['from' => $previousStatus, 'to' => $workOrder->status]);

        return redirect()->route('dashboard', ['panel' => 'orders'])->with('success', 'Estado de la orden actualizado.');
    }

    public function updateWorkOrderFinancials(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate([
            'labor_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'parts_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'other_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'warranty_days' => ['required', 'integer', 'between:0,3650'],
            'warranty_terms' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $workOrder): void {
            $order = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            $total = (float) $data['labor_cost'] + (float) $data['parts_cost'] + (float) $data['other_cost'];

            if ($total < (float) $order->paid) {
                throw ValidationException::withMessages([
                    'labor_cost' => 'El total no puede ser menor que los pagos ya registrados.',
                ]);
            }

            $order->update([
                'labor_cost' => $data['labor_cost'],
                'parts_cost' => $data['parts_cost'],
                'other_cost' => $data['other_cost'],
                'total' => $total,
                'warranty_days' => $data['warranty_days'],
                'warranty_terms' => $data['warranty_terms'] ?? null,
            ]);
            $this->audit('work_order.financials_updated', $order, ['total' => $total, 'warranty_days' => $data['warranty_days']]);
        });

        return redirect()->route('work-orders.show', $workOrder)->with('success', 'Costos y garantía actualizados.');
    }

    public function storeWorkOrderItem(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $canManageFinances = $request->user()->can('manage-finances');
        $data = $request->validate([
            'type' => ['required', Rule::in(['service', 'labor'])],
            'description' => ['required', 'string', 'max:190'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999'],
            'unit_price' => $canManageFinances ? ['required', 'numeric', 'min:0', 'max:99999999.99'] : ['prohibited'],
            'unit_cost' => $canManageFinances ? ['nullable', 'numeric', 'min:0', 'max:99999999.99'] : ['prohibited'],
        ]);
        $data['unit_price'] ??= 0;

        DB::transaction(function () use ($data, $workOrder): void {
            $order = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            abort_if($order->status === 'delivered', 422, 'No se pueden agregar conceptos a una orden entregada.');
            $lineTotal = (float) $data['quantity'] * (float) $data['unit_price'];
            $order->items()->create($data + ['unit_cost' => $data['unit_cost'] ?? 0]);
            $order->increment('labor_cost', $lineTotal);
            $order->increment('total', $lineTotal);
            $this->audit('work_order.item_added', $order, ['description' => $data['description'], 'amount' => $lineTotal]);
        });

        return redirect()->route('work-orders.show', $workOrder)->with('success', 'Concepto agregado y total actualizado.');
    }

    public function storePayment(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99']]);

        DB::transaction(function () use ($data, $workOrder): void {
            $lockedOrder = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $data['amount'];
            $balance = (float) $lockedOrder->total - (float) $lockedOrder->paid;

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'amount' => 'El pago no puede superar el saldo pendiente de la orden.',
                ]);
            }

            Payment::create([
                'work_order_id' => $lockedOrder->id,
                'user_id' => auth()->id(),
                'amount' => $amount,
                'method' => 'cash',
                'paid_at' => now(),
            ]);
            $lockedOrder->increment('paid', $amount);
            $this->audit('payment.created', $lockedOrder, ['amount' => $amount]);
        });

        return redirect()->route('dashboard', ['panel' => 'finance'])->with('success', 'Pago registrado y saldo actualizado.');
    }

    public function storeQuote(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_id' => ['required', Rule::exists('vehicles', 'id')->where('customer_id', $request->input('customer_id'))],
            'complaint' => ['required', 'string', 'max:3000'],
            'labor_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'parts_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'other_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'valid_days' => ['required', 'integer', 'between:1,365'],
        ]);
        $total = (float) $data['labor_cost'] + (float) $data['parts_cost'] + (float) $data['other_cost'];
        $quote = Quote::create([
            'number' => 'COT-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(3))),
            'customer_id' => $data['customer_id'],
            'vehicle_id' => $data['vehicle_id'],
            'created_by' => auth()->id(),
            'status' => 'drafted',
            'complaint' => $data['complaint'],
            'labor_cost' => $data['labor_cost'],
            'parts_cost' => $data['parts_cost'],
            'other_cost' => $data['other_cost'],
            'total' => $total,
            'valid_until' => now()->addDays((int) $data['valid_days'])->toDateString(),
        ]);
        $this->audit('quote.created', $quote, ['number' => $quote->number, 'total' => $total]);

        return redirect()->route('dashboard', ['panel' => 'quotes'])->with('success', "Cotización {$quote->number} creada.");
    }

    public function authorizeQuote(Request $request, Quote $quote): RedirectResponse
    {
        $data = $request->validate(['authorization_note' => ['required', 'string', 'max:190']]);

        abort_if($quote->status !== 'drafted' || $quote->valid_until?->isBefore(today()), 422, 'La cotización no se puede autorizar porque ya fue autorizada o venció.');
        $quote->update([
            'status' => 'authorized',
            'authorized_by' => auth()->id(),
            'authorized_at' => now(),
            'authorization_note' => $data['authorization_note'],
        ]);
        $this->audit('quote.authorized', $quote, ['authorization_note' => $data['authorization_note']]);

        return redirect()->route('dashboard', ['panel' => 'quotes'])->with('success', 'Autorización de cotización registrada.');
    }

    public function convertQuote(Quote $quote): RedirectResponse
    {
        $order = DB::transaction(function () use ($quote): WorkOrder {
            $lockedQuote = Quote::whereKey($quote->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedQuote->status !== 'authorized' || $lockedQuote->work_order_id, 422, 'La cotización debe estar autorizada y no haberse convertido antes.');
            abort_if($lockedQuote->valid_until?->isBefore(today()), 422, 'La cotización ya venció.');

            $order = WorkOrder::create([
                'number' => 'OT-'.now()->format('ymd').'-'.strtoupper(bin2hex(random_bytes(3))),
                'customer_id' => $lockedQuote->customer_id,
                'vehicle_id' => $lockedQuote->vehicle_id,
                'created_by' => auth()->id(),
                'status' => 'pending',
                'complaint' => $lockedQuote->complaint,
                'received_at' => now(),
                'labor_cost' => $lockedQuote->labor_cost,
                'parts_cost' => $lockedQuote->parts_cost,
                'other_cost' => $lockedQuote->other_cost,
                'total' => $lockedQuote->total,
            ]);

            foreach ([
                ['type' => 'labor', 'description' => 'Mano de obra cotizada', 'amount' => $lockedQuote->labor_cost],
                ['type' => 'part', 'description' => 'Refacciones cotizadas', 'amount' => $lockedQuote->parts_cost],
                ['type' => 'service', 'description' => 'Otros conceptos cotizados', 'amount' => $lockedQuote->other_cost],
            ] as $item) {
                if ((float) $item['amount'] > 0) {
                    $order->items()->create([
                        'type' => $item['type'],
                        'description' => $item['description'],
                        'quantity' => 1,
                        'unit_price' => $item['amount'],
                        'unit_cost' => 0,
                    ]);
                }
            }

            $lockedQuote->update(['status' => 'converted', 'work_order_id' => $order->id]);
            $this->audit('quote.converted', $lockedQuote, ['work_order_id' => $order->id]);

            return $order;
        });

        return redirect()->route('work-orders.show', $order)->with('success', "Cotización convertida en la orden {$order->number}.");
    }

    public function storeDebtAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id', 'required_without:company_name'],
            'company_name' => ['nullable', 'required_without:customer_id', 'string', 'max:160'],
            'reference' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'due_at' => ['nullable', 'date'],
            'agreement' => ['nullable', 'string', 'max:190'],
        ]);
        $account = DebtAccount::create($data + [
            'created_by' => auth()->id(),
            'balance' => $data['amount'],
            'status' => 'open',
        ]);
        $this->audit('debt_account.created', $account, ['amount' => $account->amount]);

        return redirect()->route('dashboard', ['panel' => 'finance'])->with('success', 'Convenio de crédito registrado.');
    }

    public function storeDebtPayment(Request $request, DebtAccount $debtAccount): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'notes' => ['nullable', 'string', 'max:190'],
        ]);

        DB::transaction(function () use ($data, $debtAccount): void {
            $account = DebtAccount::whereKey($debtAccount->id)->lockForUpdate()->firstOrFail();
            $amount = (float) $data['amount'];
            if ($amount > (float) $account->balance) {
                throw ValidationException::withMessages(['amount' => 'El abono no puede superar el saldo del convenio.']);
            }
            DebtPayment::create([
                'debt_account_id' => $account->id,
                'user_id' => auth()->id(),
                'amount' => $amount,
                'paid_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);
            $account->balance = (float) $account->balance - $amount;
            $account->status = $account->balance <= 0 ? 'paid' : 'open';
            $account->save();
            $this->audit('debt_account.payment', $account, ['amount' => $amount, 'balance' => $account->balance]);
        });

        return redirect()->route('dashboard', ['panel' => 'finance'])->with('success', 'Abono aplicado al convenio.');
    }

    public function storeInventoryItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['nullable', 'string', 'max:80', 'unique:inventory_items,sku'],
            'supplier' => ['nullable', 'string', 'max:160'],
            'stock' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data): void {
            $item = InventoryItem::create($data);
            if ((float) $item->stock > 0) {
                DB::table('inventory_movements')->insert([
                    'inventory_item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'direction' => 'in',
                    'quantity' => $item->stock,
                    'notes' => 'Existencia inicial',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('expenses')->insert([
                    'user_id' => auth()->id(),
                    'concept' => 'Compra inicial: '.$item->name,
                    'category' => 'purchase',
                    'supplier' => $item->supplier,
                    'amount' => (float) $item->stock * (float) $item->unit_cost,
                    'spent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->audit('inventory_item.created', $item, ['stock' => $item->stock]);
        });

        return redirect()->route('dashboard', ['panel' => 'inventory'])->with('success', 'Refacción agregada al inventario.');
    }

    public function storeInventoryMovement(Request $request, InventoryItem $inventoryItem): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', Rule::in(['in', 'out'])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:190'],
            'work_order_id' => ['nullable', 'exists:work_orders,id'],
        ]);

        DB::transaction(function () use ($data, $inventoryItem): void {
            $item = InventoryItem::whereKey($inventoryItem->id)->lockForUpdate()->firstOrFail();
            $quantity = (float) $data['quantity'];
            $orderId = $data['work_order_id'] ?? null;

            if ($data['direction'] === 'out' && $quantity > (float) $item->stock) {
                throw ValidationException::withMessages(['quantity' => 'No hay existencias suficientes.']);
            }
            if ($data['direction'] === 'out' && $orderId) {
                $order = WorkOrder::whereKey($orderId)->lockForUpdate()->firstOrFail();
                abort_if($order->status === 'delivered', 422, 'No se pueden asignar refacciones a una orden entregada.');
                $lineTotal = $quantity * (float) $item->sale_price;
                $order->items()->create([
                    'type' => 'part',
                    'description' => $item->name,
                    'quantity' => $quantity,
                    'unit_price' => $item->sale_price,
                    'unit_cost' => $item->unit_cost,
                ]);
                $order->increment('parts_cost', $lineTotal);
                $order->increment('total', $lineTotal);
            }

            $item->stock = $data['direction'] === 'in' ? (float) $item->stock + $quantity : (float) $item->stock - $quantity;
            $item->save();
            DB::table('inventory_movements')->insert([
                'inventory_item_id' => $item->id,
                'work_order_id' => $orderId,
                'user_id' => auth()->id(),
                'direction' => $data['direction'],
                'quantity' => $quantity,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($data['direction'] === 'in') {
                DB::table('expenses')->insert([
                    'user_id' => auth()->id(),
                    'concept' => 'Compra de refacción: '.$item->name,
                    'category' => 'purchase',
                    'supplier' => $item->supplier,
                    'amount' => $quantity * (float) $item->unit_cost,
                    'spent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->audit('inventory.movement', $item, ['direction' => $data['direction'], 'quantity' => $quantity, 'work_order_id' => $orderId]);
        });

        return redirect()->route('dashboard', ['panel' => 'inventory'])->with('success', 'Movimiento de inventario registrado.');
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'concept' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(['purchase', 'rent', 'utilities', 'other'])],
            'supplier' => ['nullable', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
        ]);
        $expenseId = DB::table('expenses')->insertGetId($data + [
            'user_id' => auth()->id(), 'spent_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit('expense.created', (object) ['id' => $expenseId, 'getKey' => fn () => $expenseId], ['amount' => $data['amount']]);

        return redirect()->route('dashboard', ['panel' => 'finance'])->with('success', 'Gasto registrado.');
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'position' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'salary' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'pay_frequency' => ['required', Rule::in(['weekly', 'biweekly', 'monthly'])],
            'email' => ['nullable', 'required_with:password', 'email', 'max:190', 'unique:users,email'],
            'password' => ['nullable', 'required_with:email', 'string', 'min:10', 'confirmed'],
            'role' => ['required_with:email', Rule::in(['manager', 'technician'])],
        ]);
        $employeeId = DB::transaction(function () use ($data): int {
            $user = isset($data['email']) ? User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
            ]) : null;

            $employeeId = DB::table('employees')->insertGetId([
                'user_id' => $user?->id,
                'name' => $data['name'],
                'position' => $data['position'],
                'phone' => $data['phone'] ?? null,
                'salary' => $data['salary'],
                'pay_frequency' => $data['pay_frequency'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit('employee.created', (object) ['id' => $employeeId], ['name' => $data['name'], 'user_id' => $user?->id]);

            return $employeeId;
        });

        return redirect()->route('dashboard', ['panel' => 'team'])->with('success', 'Empleado registrado.');
    }

    public function storePayrollPayment(Request $request, int $employee): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'paid_for' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:190'],
        ]);
        abort_unless(DB::table('employees')->where('id', $employee)->where('is_active', true)->exists(), 404);
        $paymentId = DB::table('payroll_payments')->insertGetId($data + [
            'employee_id' => $employee, 'user_id' => auth()->id(), 'paid_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->audit('payroll.paid', (object) ['id' => $paymentId, 'getKey' => fn () => $paymentId], ['employee_id' => $employee, 'amount' => $data['amount']]);

        return redirect()->route('dashboard', ['panel' => 'team'])->with('success', 'Pago de nómina registrado.');
    }

    private function audit(string $action, object $subject, array $details): void
    {
        $subjectId = method_exists($subject, 'getKey') ? $subject->getKey() : ($subject->id ?? null);

        DB::table('audit_logs')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subjectId,
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}
