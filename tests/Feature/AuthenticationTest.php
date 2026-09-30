<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admin_can_be_configured_once(): void
    {
        $this->get('/')->assertRedirect('/ingresar');
        $this->get('/ingresar')->assertRedirect(route('setup'));
        $this->get(route('setup'))->assertOk()->assertSee('Crear administrador');

        $this->post(route('setup.store'), [
            'name' => 'Administración',
            'email' => 'admin@tallernorte.mx',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'admin@tallernorte.mx', 'role' => 'admin']);
        $this->get(route('setup'))->assertRedirect(route('login'));
        $this->post(route('setup.store'), [])->assertNotFound();
    }

    public function test_technicians_cannot_access_financial_or_user_management_actions(): void
    {
        $technician = User::factory()->create(['role' => 'technician']);
        $this->actingAs($technician);

        $this->get('/')->assertOk()->assertDontSee('COBRADO ESTE MES')->assertDontSee('Ingresos cobrados');
        $this->post('/gastos', [
            'concept' => 'Gasto no autorizado',
            'category' => 'other',
            'amount' => 100,
        ])->assertForbidden();
        $this->post('/empleados', [])->assertForbidden();
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_technicians_cannot_submit_costs_when_creating_orders(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'technician']));
        $customer = Customer::create(['name' => 'Cliente', 'phone' => '5552223333']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'make' => 'Kia',
            'model' => 'Rio',
            'plate' => 'TEC-111-A',
        ]);

        $this->post('/ordenes', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Revisión general',
            'labor_cost' => 500,
            'parts_cost' => 100,
        ])->assertSessionHasErrors(['labor_cost', 'parts_cost']);

        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_technicians_can_add_work_details_without_setting_prices(): void
    {
        $technician = User::factory()->create(['role' => 'technician']);
        $this->actingAs($technician);
        $customer = Customer::create(['name' => 'Cliente', 'phone' => '5552223333']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'make' => 'Kia',
            'model' => 'Rio',
            'plate' => 'TEC-222-A',
        ]);
        $this->post('/ordenes', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Revisión general',
        ])->assertRedirect();
        $order = \App\Models\WorkOrder::firstOrFail();

        $this->get(route('work-orders.show', $order))->assertOk()->assertSee('Agregar trabajo')->assertDontSee('name="unit_price"');
        $this->post(route('work-orders.items.store', $order), [
            'type' => 'labor',
            'description' => 'Cambio de balatas',
            'quantity' => 1,
        ])->assertRedirect(route('work-orders.show', $order));

        $this->assertDatabaseHas('work_order_items', [
            'work_order_id' => $order->id,
            'description' => 'Cambio de balatas',
            'unit_price' => 0,
        ]);
        $this->post(route('work-orders.items.store', $order), [
            'type' => 'labor',
            'description' => 'Servicio no autorizado',
            'quantity' => 1,
            'unit_price' => 50,
        ])->assertSessionHasErrors('unit_price');
    }

    public function test_user_can_sign_in_and_sign_out(): void
    {
        User::factory()->create(['email' => 'mecanico@example.com', 'password' => 'clave-segura-123']);

        $this->post(route('login.store'), [
            'email' => 'mecanico@example.com',
            'password' => 'clave-segura-123',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
