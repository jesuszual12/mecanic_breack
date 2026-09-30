<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DebtAccount;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    public function test_quote_must_be_authorized_before_it_can_become_one_work_order(): void
    {
        [$customer, $vehicle] = $this->customerWithVehicle();
        $this->post('/cotizaciones', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Afinación y revisión',
            'labor_cost' => 800,
            'parts_cost' => 1250,
            'other_cost' => 150,
            'valid_days' => '15',
        ])->assertRedirect();
        $quote = Quote::firstOrFail();

        $this->assertSame('drafted', $quote->status);
        $this->assertEquals(2200, $quote->total);
        $this->assertSame(now()->addDays(15)->toDateString(), $quote->valid_until->toDateString());
        $this->post("/cotizaciones/{$quote->id}/convertir")->assertStatus(422);

        $this->patch("/cotizaciones/{$quote->id}/autorizar", [
            'authorization_note' => 'Cliente autorizó por escrito en recepción.',
        ])->assertRedirect();
        $this->post("/cotizaciones/{$quote->id}/convertir")->assertRedirect();
        $order = WorkOrder::firstOrFail();

        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'converted',
            'work_order_id' => $order->id,
            'authorization_note' => 'Cliente autorizó por escrito en recepción.',
        ]);
        $this->assertDatabaseHas('work_orders', [
            'id' => $order->id,
            'labor_cost' => 800,
            'parts_cost' => 1250,
            'other_cost' => 150,
            'total' => 2200,
        ]);
        $this->assertDatabaseCount('work_order_items', 3);
        $this->post("/cotizaciones/{$quote->id}/convertir")->assertStatus(422);
    }

    public function test_company_agreement_tracks_payments_and_prevents_overpayment(): void
    {
        $this->post('/convenios', [
            'company_name' => 'Transportes del Valle',
            'reference' => 'CONV-2026-17',
            'amount' => 1500,
            'due_at' => now()->addMonth()->toDateString(),
            'agreement' => 'Liquidación en tres exhibiciones.',
        ])->assertRedirect();
        $account = DebtAccount::firstOrFail();

        $this->post("/convenios/{$account->id}/abonos", ['amount' => 500, 'notes' => 'Primer abono'])->assertRedirect();
        $this->post("/convenios/{$account->id}/abonos", ['amount' => 1001])->assertSessionHasErrors('amount');

        $account->refresh();
        $this->assertEquals(1000, $account->balance);
        $this->assertDatabaseHas('debt_payments', [
            'debt_account_id' => $account->id,
            'user_id' => auth()->id(),
            'amount' => 500,
            'notes' => 'Primer abono',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'debt_account.payment']);
        $this->get('/?panel=finance')->assertOk()->assertSee('$500.00');
    }

    public function test_administration_can_set_work_order_costs_and_warranty_after_creation(): void
    {
        [$customer, $vehicle] = $this->customerWithVehicle();
        $this->post('/ordenes', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'complaint' => 'Revisión de motor',
        ])->assertRedirect();
        $order = WorkOrder::firstOrFail();

        $this->patch(route('work-orders.financials.update', $order), [
            'labor_cost' => 900,
            'parts_cost' => 450,
            'other_cost' => 100,
            'warranty_days' => 45,
            'warranty_terms' => 'Cubre mano de obra.',
        ])->assertRedirect(route('work-orders.show', $order));

        $this->assertDatabaseHas('work_orders', [
            'id' => $order->id,
            'labor_cost' => 900,
            'parts_cost' => 450,
            'other_cost' => 100,
            'total' => 1450,
            'warranty_days' => 45,
            'warranty_terms' => 'Cubre mano de obra.',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'work_order.financials_updated']);

        $this->get(route('work-orders.receipt', $order))
            ->assertOk()
            ->assertSee('Garantía:')
            ->assertSee('45 días.')
            ->assertSee('Servicio automotriz')
            ->assertDontSee('Recibí de conformidad')
            ->assertDontSee('Firma del cliente');

        $this->actingAs(User::factory()->create(['role' => 'technician']))
            ->patch(route('work-orders.financials.update', $order), [
                'labor_cost' => 1,
                'parts_cost' => 0,
                'other_cost' => 0,
                'warranty_days' => 0,
            ])->assertForbidden();
    }

    private function customerWithVehicle(): array
    {
        $customer = Customer::create(['name' => 'María Torres', 'phone' => '5559001100']);
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'make' => 'Volkswagen',
            'model' => 'Jetta',
            'plate' => 'COT-448-D',
        ]);

        return [$customer, $vehicle];
    }
}
