<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_dashboard_shows_the_workshop_summary(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Resumen del taller')->assertSee('ÓRDENES ACTIVAS');
    }

    public function test_customer_vehicle_and_work_order_can_be_registered(): void
    {
        $this->post('/clientes', [
            'name' => 'Ana López',
            'phone' => '5551234567',
            'email' => 'ana@example.com',
        ])->assertRedirect();

        $customerId = Customer::firstOrFail()->id;
        $this->post('/vehiculos', [
            'customer_id' => $customerId,
            'make' => 'Nissan',
            'model' => 'Versa',
            'year' => 2022,
            'plate' => 'ABC-123-A',
        ])->assertRedirect();

        $vehicleId = Vehicle::firstOrFail()->id;
        $this->post('/ordenes', [
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'complaint' => 'Ruido al frenar',
            'labor_cost' => 800,
            'parts_cost' => 450,
            'initial_payment' => 300,
            'warranty_days' => 30,
        ])->assertRedirect();

        $this->assertDatabaseHas('work_orders', [
            'customer_id' => $customerId,
            'vehicle_id' => $vehicleId,
            'total' => 1250,
            'paid' => 300,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('payments', ['amount' => 300]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'work_order.created']);
    }

    public function test_stock_usage_and_payments_keep_order_totals_consistent(): void
    {
        $customer = Customer::create(['name' => 'Luis Pérez', 'phone' => '5557654321']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'plate' => 'XYZ-987-B',
        ]);
        $this->post('/ordenes', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Servicio de frenos',
            'labor_cost' => 100,
            'parts_cost' => 0,
        ])->assertRedirect();
        $order = WorkOrder::firstOrFail();

        $this->post('/inventario', [
            'name' => 'Balata delantera',
            'sku' => 'BAL-001',
            'stock' => 5,
            'minimum_stock' => 1,
            'unit_cost' => 10,
            'sale_price' => 20,
        ])->assertRedirect();
        $item = InventoryItem::firstOrFail();

        $this->post("/inventario/{$item->id}/movimientos", [
            'direction' => 'out',
            'quantity' => 2,
            'work_order_id' => $order->id,
            'notes' => 'Instaladas en eje delantero',
        ])->assertRedirect();
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'stock' => 3]);
        $this->assertDatabaseHas('work_orders', ['id' => $order->id, 'total' => 140, 'parts_cost' => 40]);

        $this->post("/ordenes/{$order->id}/pagos", ['amount' => 100])->assertRedirect();
        $this->post("/ordenes/{$order->id}/pagos", ['amount' => 41])->assertSessionHasErrors('amount');
        $this->assertDatabaseHas('work_orders', ['id' => $order->id, 'paid' => 100]);
        $this->assertDatabaseHas('payments', ['work_order_id' => $order->id, 'amount' => 100]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'inventory.movement']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.created']);
    }

    public function test_work_order_stores_body_damage_and_scheduled_time(): void
    {
        $customer = Customer::create(['name' => 'Eva Torres', 'phone' => '5554443322']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'make' => 'Seat',
            'model' => 'Ibiza',
            'plate' => 'DAN-123-A',
        ]);
        $scheduledAt = now()->addDay()->setTime(10, 30);

        $this->post('/ordenes', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Revisión de carrocería',
            'scheduled_at' => $scheduledAt->format('Y-m-d\TH:i'),
            'damage_points_json' => json_encode([
                ['part' => 'left_front_door', 'detail' => 'Rayón profundo en la puerta'],
            ], JSON_THROW_ON_ERROR),
        ])->assertRedirect();

        $order = WorkOrder::firstOrFail();
        $this->assertEquals('left_front_door', $order->damage_points[0]['part']);
        $this->assertEquals('Rayón profundo en la puerta', $order->damage_points[0]['detail']);
        $this->assertEquals($scheduledAt->format('Y-m-d H:i'), $order->scheduled_at->format('Y-m-d H:i'));
        $this->get(route('work-orders.show', $order))
            ->assertOk()
            ->assertSee('DAÑOS DE CARROCERÍA')
            ->assertSee('Rayón profundo en la puerta');
        $this->get(route('dashboard', [
            'panel' => 'calendar',
            'date' => $scheduledAt->toDateString(),
        ]))->assertOk()->assertSee('10:30')->assertSee('DAN-123-A');
    }

    public function test_direct_inventory_sales_reduce_stock_and_count_as_daily_income(): void
    {
        $item = InventoryItem::create([
            'name' => 'Filtro de aire',
            'sku' => 'FIL-009',
            'stock' => 5,
            'minimum_stock' => 1,
            'unit_cost' => 40,
            'sale_price' => 75,
        ]);

        $this->post(route('inventory.sales.store', $item), [
            'quantity' => 2,
            'payment_method' => 'card',
        ])->assertRedirect();

        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'stock' => 3]);
        $this->assertDatabaseHas('inventory_sales', [
            'inventory_item_id' => $item->id,
            'quantity' => 2,
            'total' => 150,
            'payment_method' => 'card',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'direction' => 'out',
            'quantity' => 2,
        ]);
        $this->get(route('dashboard', ['panel' => 'calendar', 'date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('150.00')
            ->assertSee('VENTAS DE REFACCIONES');
    }

    public function test_expenses_and_payroll_are_saved_for_financial_reports(): void
    {
        $this->post('/gastos', [
            'concept' => 'Reparación de compresor',
            'category' => 'other',
            'amount' => 850,
        ])->assertRedirect();

        $this->post('/empleados', [
            'name' => 'Marco Ruiz',
            'position' => 'Mecánico',
            'salary' => 3200,
            'pay_frequency' => 'weekly',
            'email' => 'marco@example.com',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
            'role' => 'technician',
        ])->assertRedirect();
        $employee = (object) DB::table('employees')->first();

        $this->post("/empleados/{$employee->id}/nomina", [
            'amount' => 3200,
            'paid_for' => now()->toDateString(),
            'notes' => 'Pago semanal',
        ])->assertRedirect();

        $this->assertDatabaseHas('expenses', ['concept' => 'Reparación de compresor', 'amount' => 850]);
        $this->assertDatabaseHas('payroll_payments', ['employee_id' => $employee->id, 'amount' => 3200]);
        $this->assertDatabaseHas('users', ['email' => 'marco@example.com', 'role' => 'technician']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payroll.paid']);
        $this->get(route('dashboard', ['panel' => 'calendar', 'date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('850.00')
            ->assertSee('3,200.00')
            ->assertSee('NETO DEL DÍA');
    }

    public function test_vehicle_history_and_printable_work_order_note_are_available(): void
    {
        $customer = Customer::create(['name' => 'Sofía Díaz', 'phone' => '5551010101']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'make' => 'Mazda',
            'model' => '3',
            'year' => 2021,
            'plate' => 'HST-321-C',
        ]);
        $order = WorkOrder::create([
            'number' => 'OT-260928-HST321',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'delivered',
            'complaint' => 'Vibración al frenar',
            'received_at' => now()->subDay(),
            'delivered_at' => now(),
            'labor_cost' => 300,
            'parts_cost' => 150,
            'total' => 450,
            'paid' => 450,
            'warranty_days' => 30,
            'warranty_terms' => 'Cubre instalación y mano de obra.',
        ]);
        $order->items()->create([
            'type' => 'service',
            'description' => 'Rectificado de discos',
            'quantity' => 1,
            'unit_price' => 300,
        ]);
        $order->payments()->create(['amount' => 450, 'method' => 'cash', 'paid_at' => now()]);

        $this->get(route('dashboard', ['q' => 'HST-321-C']))->assertOk()->assertSee($order->number);
        $this->get(route('dashboard', ['panel' => 'customers']))->assertOk()->assertSee('Sofía Díaz')->assertSee('1 vehículos');
        $this->get(route('dashboard', ['panel' => 'vehicles']))->assertOk()->assertSee('HST-321-C')->assertSee('Ver historial');
        $this->get(route('work-orders.show', $order))->assertOk()->assertSee('Rectificado de discos')->assertSee('Pagos recibidos');
        $this->get(route('work-orders.receipt', $order))->assertOk()->assertSee('NOTA DE SERVICIO')->assertSee('Sofía Díaz')->assertSee('Cubre instalación y mano de obra.');
        $this->get(route('vehicles.show', $vehicle))->assertOk()->assertSee('HISTORIAL DEL VEHÍCULO')->assertSee($order->number);
    }
}
