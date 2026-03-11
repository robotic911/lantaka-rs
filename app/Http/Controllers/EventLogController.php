<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EventLog;
use Illuminate\Support\Facades\Auth;
class EventLogController extends Controller
{
    /**
     * Save a new event log.
     */
    public static function log($action, $message)
    {
        EventLog::create([
            'user_id' => Auth::id(),
            'action'  => strtolower($action),
            'message' => $message,
        ]);
    }

    /**
     * Display event logs list.
     */
    public function index(Request $request)
    {
        $query = EventLog::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('action', 'ILIKE', "%{$search}%")
                  ->orWhere('message', 'ILIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'ILIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('action')) {
            $query->where('action', strtolower($request->action));
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('employee.eventlogs', compact('logs'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}