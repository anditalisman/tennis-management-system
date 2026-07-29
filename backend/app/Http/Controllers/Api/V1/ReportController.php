<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class ReportController extends Controller
{
    public function attendance(Request $request): JsonResponse
    {
        [$summary, $byClass] = $this->buildAttendanceReport($request);

        return response()->json(['data' => [
            'by_status' => $summary,
            'by_class' => $byClass,
        ]]);
    }

    public function revenue(Request $request): JsonResponse
    {
        [$total, $byPeriod, $byBranch] = $this->buildRevenueReport($request);

        return response()->json(['data' => [
            'total' => $total,
            'by_period' => $byPeriod,
            'by_branch' => $byBranch,
        ]]);
    }

    public function export(Request $request, string $type): Response
    {
        $format = $request->string('format', 'csv')->toString();
        abort_unless(in_array($type, ['attendance', 'revenue'], true), 404);
        abort_if(
            $format !== 'csv',
            422,
            "Format {$format} memerlukan dependency tambahan yang belum terpasang (mis. maatwebsite/excel atau barryvdh/laravel-dompdf). Gunakan format=csv.",
        );

        $rows = $type === 'attendance'
            ? $this->attendanceCsvRows($request)
            : $this->revenueCsvRows($request);

        $csv = fopen('php://temp', 'w+');
        foreach ($rows as $row) {
            fputcsv($csv, $row);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$type}-report.csv\"",
        ]);
    }

    /**
     * @return array{0: Collection<string, int>, 1: Collection<int, array<string, mixed>>}
     */
    private function buildAttendanceReport(Request $request): array
    {
        $query = Attendance::query()
            ->join('training_sessions', 'attendance.session_id', '=', 'training_sessions.id')
            ->join('training_schedules', 'training_sessions.schedule_id', '=', 'training_schedules.id')
            ->join('classes', 'training_schedules.class_id', '=', 'classes.id')
            ->when($request->string('from')->isNotEmpty(), fn ($q) => $q->whereDate('training_schedules.session_date', '>=', $request->string('from')))
            ->when($request->string('to')->isNotEmpty(), fn ($q) => $q->whereDate('training_schedules.session_date', '<=', $request->string('to')))
            ->when($request->string('program_id')->isNotEmpty(), fn ($q) => $q->where('classes.program_id', $request->integer('program_id')))
            ->when($request->string('class_id')->isNotEmpty(), fn ($q) => $q->where('classes.id', $request->integer('class_id')));

        $user = $request->user();
        if ($user->coach && ! $user->hasRole(Role::SUPER_ADMIN) && ! $user->hasRole(Role::MANAGEMENT)) {
            $query->where('training_schedules.coach_id', $user->coach->id);
        }

        $summary = (clone $query)
            ->selectRaw('attendance.status as status, count(*) as total')
            ->groupBy('attendance.status')
            ->pluck('total', 'status');

        $byClass = (clone $query)
            ->selectRaw('classes.id as class_id, classes.name as class_name, attendance.status as status, count(*) as total')
            ->groupBy('classes.id', 'classes.name', 'attendance.status')
            ->get()
            ->groupBy('class_id')
            ->map(fn ($rows) => [
                'class_id' => $rows->first()->class_id,
                'class_name' => $rows->first()->class_name,
                'by_status' => $rows->pluck('total', 'status'),
            ])
            ->values();

        return [$summary, $byClass];
    }

    /**
     * @return array{0: float, 1: Collection<string, float>, 2: Collection<string, float>}
     */
    private function buildRevenueReport(Request $request): array
    {
        $payments = Payment::query()
            ->where('status', Payment::STATUS_VERIFIED)
            ->whereHas('invoice', fn ($q) => $q->when(
                $request->string('branch_id')->isNotEmpty(),
                fn ($q2) => $q2->where('branch_id', $request->integer('branch_id')),
            ))
            ->when($request->string('from')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '>=', $request->string('from')))
            ->when($request->string('to')->isNotEmpty(), fn ($q) => $q->whereDate('created_at', '<=', $request->string('to')))
            ->with('invoice')
            ->get();

        $total = (float) $payments->sum('amount');
        $byPeriod = $payments->groupBy(fn (Payment $p) => $p->created_at->format('Y-m'))->map(fn ($g) => (float) $g->sum('amount'));
        $byBranch = $payments->groupBy(fn (Payment $p) => $p->invoice->branch_id)->map(fn ($g) => (float) $g->sum('amount'));

        return [$total, $byPeriod, $byBranch];
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    private function attendanceCsvRows(Request $request): array
    {
        [$summary] = $this->buildAttendanceReport($request);

        $rows = [['status', 'total']];
        foreach ($summary as $status => $total) {
            $rows[] = [$status, $total];
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string|float>>
     */
    private function revenueCsvRows(Request $request): array
    {
        [, $byPeriod] = $this->buildRevenueReport($request);

        $rows = [['period', 'total']];
        foreach ($byPeriod as $period => $total) {
            $rows[] = [$period, $total];
        }

        return $rows;
    }
}
