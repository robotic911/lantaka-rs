<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EventLogController extends Controller
{
    /**
     * Record a system action in the event log.
     *
     * @param  string      $action            Slug-style action key (e.g. 'account_approved')
     * @param  string      $message           Human-readable description
     * @param  int|null    $actorId           Account who performed the action (defaults to Auth::id())
     * @param  int|null    $notifiableUserId  Client to notify via the bell (null = audit-only)
     * @param  array       $extra             Optional: ['title', 'type', 'link']
     */
    public static function log(
        string $action,
        string $message,
        ?int   $actorId          = null,
        ?int   $notifiableUserId = null,
        array  $extra            = []
    ): void {
        if (! Schema::hasTable('Event_Logs')) {
            return; // Table not yet created — skip silently
        }

        EventLog::create([
            'user_id'                        => $actorId ?? Auth::id(),
            'Event_Logs_Notifiable_User_ID'  => $notifiableUserId,
            'Event_Logs_Action'              => strtolower($action),
            'Event_Logs_Title'               => $extra['title'] ?? null,
            'Event_Logs_Message'             => $message,
            'Event_Logs_Type'                => $extra['type']  ?? null,
            'Event_Logs_Link'                => $extra['link']  ?? null,
            'Event_Logs_isRead'              => false,
        ]);
    }

    /**
     * Display the event log audit trail (admin / staff view).
     */
    public function index(Request $request)
    {
        $query = EventLog::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('Event_Logs_Action',   'ILIKE', "%{$search}%")
                  ->orWhere('Event_Logs_Message', 'ILIKE', "%{$search}%")
                  ->orWhere('Event_Logs_Title',   'ILIKE', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('Account_Name', 'ILIKE', "%{$search}%"));
            });
        }

        if ($request->filled('action')) {
            $query->where('Event_Logs_Action', strtolower($request->action));
        }

        // Default: audit rows only (Event_Logs_Notifiable_User_ID IS NULL)
        // Pass ?show_notifications=1 to include client notification rows too
        if ($request->show_notifications !== '1') {
            $query->whereNull('Event_Logs_Notifiable_User_ID');
        }

        $logs = $query->paginate(25)->withQueryString();

        // Build dynamic action list for the filter dropdown
        $actions = EventLog::whereNull('Event_Logs_Notifiable_User_ID')
            ->select('Event_Logs_Action')
            ->distinct()
            ->orderBy('Event_Logs_Action')
            ->pluck('Event_Logs_Action');

        return view('employee.eventlogs', compact('logs', 'actions'));
    }
}
