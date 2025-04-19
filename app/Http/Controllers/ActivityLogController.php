<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');
        
        // Filter berdasarkan modul
        if ($request->has('module') && $request->module) {
            $query->where('module', $request->module);
        }
        
        // Filter berdasarkan aksi
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }
        
        // Filter berdasarkan user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $logs = $query->paginate(15);
        
        // Data untuk dropdown filter
        $users = \App\Models\User::where('role', 'karyawan')->get();
        $modules = ActivityLog::distinct('module')->pluck('module');
        $actions = ['create', 'update', 'delete'];
        
        return view('logs.index', compact('logs', 'users', 'modules', 'actions'));
    }
    
    public function show($id)
    {
        $log = ActivityLog::with('user')->findOrFail($id);
        return view('logs.show', compact('log'));
    }
}