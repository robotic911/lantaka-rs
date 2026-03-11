<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $fillable = ['user_id', 'action', 'message'];

    // Relationship: The user who triggered the log
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
