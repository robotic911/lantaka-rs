<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $table = 'Event_Logs';
    protected $primaryKey = 'Event_Logs_ID';
    protected $fillable = [
        'user_id',
        'Event_Logs_Notifiable_User_ID',// from 'notifiable_user_id',
        'Event_Logs_Action',   // from'action',
        'Event_Logs_Title',   // from 'title',
        'Event_Logs_Message',// from 'message',
        'Event_Logs_Type',    // from 'type',
        'Event_Logs_Link',  // from 'link',
        'Event_Logs_isRead' // from 'is_read',
    ];

    protected $casts = [
        'Event_Logs_isRead' => 'boolean',
    ];

    /** The admin / staff / client who performed the action */
    public function user()
    {
        return $this->belongsTo(Account::class, 'user_id');
    }

    /** The client who should see this as a notification */
    public function notifiableUser()
    {
        return $this->belongsTo(Account::class, 'Event_Logs_Notifiable_User_ID');
    }
}
