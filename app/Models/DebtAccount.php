<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebtAccount extends Model
{
    protected $fillable = [
        'customer_id', 'company_name', 'created_by', 'reference', 'amount', 'balance', 'due_at', 'agreement', 'status',
    ];

    protected function casts(): array
    {
        return ['due_at' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }
}
