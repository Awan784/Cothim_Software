<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsLogController extends Controller
{
    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));
        $allowed = [
            SmsLog::STATUS_SENT,
            SmsLog::STATUS_FAILED,
            SmsLog::STATUS_BLOCKED,
            SmsLog::STATUS_SKIPPED,
        ];
        if ($status !== '' && ! in_array($status, $allowed, true)) {
            $status = '';
        }

        $logs = SmsLog::query()
            ->with(['invoice:id,invoice_no', 'user:id,name'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $counts = SmsLog::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('sms-logs.index', [
            'logs' => $logs,
            'statusFilter' => $status,
            'counts' => $counts,
        ]);
    }
}
