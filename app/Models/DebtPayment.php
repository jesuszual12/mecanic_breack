<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtPayment extends Model
{
    protected $fillable = ['debt_account_id', 'user_id', 'amount', 'paid_at', 'notes'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(DebtAccount::class, 'debt_account_id');
    }
}
