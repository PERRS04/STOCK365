<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ActivityLogExport;

class ActivityLogController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        return view('admin.activity-logs.index');
    }

    public function export()
    {
        abort_unless(auth()->user()->can('reports.export'), 403);

        return Excel::download(new ActivityLogExport, 'auditoria-' . now()->format('Y-m-d') . '.xlsx');
    }
}
