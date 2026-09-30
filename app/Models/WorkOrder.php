<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    protected $fillable = [
        'number', 'customer_id', 'vehicle_id', 'created_by', 'status', 'complaint', 'diagnosis',
        'received_at', 'delivered_at', 'labor_cost', 'parts_cost', 'other_cost', 'total', 'paid',
        'warranty_days', 'warranty_terms',
    ];

    protected function casts(): array
    {
        return ['received_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
