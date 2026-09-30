<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $fillable = [
        'number', 'customer_id', 'vehicle_id', 'created_by', 'authorized_by', 'work_order_id',
        'status', 'complaint', 'labor_cost', 'parts_cost', 'other_cost', 'total', 'valid_until',
        'authorized_at', 'authorization_note',
    ];

    protected function casts(): array
    {
        return ['valid_until' => 'date', 'authorized_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
