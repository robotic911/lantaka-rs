<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationRequest extends Model
{
    protected $table = 'cancellation_requests';

    protected $fillable = [
        'reservation_id',
        'reservation_type',
        'client_id',
        'reason',
        'status',        // pending | approved | rejected
        'admin_note',
        'processed_by',
        'processed_at',
    ];

    protected $dates = ['processed_at'];

    /* ── Relationships ── */

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', 'Account_ID');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by', 'Account_ID');
    }

    /* ── Scopes ── */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /* ── Helpers ── */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
