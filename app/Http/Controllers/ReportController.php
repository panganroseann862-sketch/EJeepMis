<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateDailyReportRequest;
use App\Http\Requests\GenerateWeeklyReportRequest;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Display report generation form
     */
    public function index(): View
    {
        return view('admin.reports.index');
    }

    /**
     * Generate daily report
     */
    public function generateDaily(GenerateDailyReportRequest $request): Response|JsonResponse
    {
        try {
            $date = Carbon::parse($request->validated('date'));
            $report = $this->reportService->generateDailyReport($date);

            $format = $request->validated('format', 'csv');

            if ($format === 'csv') {
                return $this->exportCsv($report, 'daily_report_' . $date->format('Y-m-d') . '.csv');
            }

            return response()->json($report);
        } catch (\Exception $e) {
            \Log::error('Failed to generate daily report', [
                'date' => $request->validated('date'),
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to generate report. Please try again.');
        }
    }

    /**
     * Generate weekly report
     */
    public function generateWeekly(GenerateWeeklyReportRequest $request): Response|JsonResponse
    {
        try {
            $startDate = Carbon::parse($request->validated('start_date'));
            $endDate = Carbon::parse($request->validated('end_date'));
            $report = $this->reportService->generateWeeklyReport($startDate, $endDate);

            $format = $request->validated('format', 'csv');

            if ($format === 'csv') {
                return $this->exportCsv($report, 'weekly_report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv');
            }

            return response()->json($report);
        } catch (\Exception $e) {
            \Log::error('Failed to generate weekly report', [
                'start_date' => $request->validated('start_date'),
                'end_date' => $request->validated('end_date'),
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to generate report. Please try again.');
        }
    }

    /**
     * Export report as CSV
     */
    private function exportCsv(array $report, string $filename): Response
    {
        $csv = $this->generateCsvContent($report);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate CSV content from report data
     */
    private function generateCsvContent(array $report): string
    {
        $output = fopen('php://temp', 'r+');

        // Write summary section
        fputcsv($output, ['Report Summary']);
        fputcsv($output, ['']);
        
        if (isset($report['date'])) {
            fputcsv($output, ['Date', $report['date']]);
        } else {
            fputcsv($output, ['Start Date', $report['start_date']]);
            fputcsv($output, ['End Date', $report['end_date']]);
        }
        
        fputcsv($output, ['Total Trips', $report['total_trips']]);
        fputcsv($output, ['Completed Trips', $report['completed_trips']]);
        fputcsv($output, ['Cancelled Trips', $report['cancelled_trips']]);
        fputcsv($output, ['Total Passengers', $report['total_passengers']]);
        fputcsv($output, ['Average Passengers', round($report['average_passengers'], 2)]);
        fputcsv($output, ['Overcrowding Incidents', $report['overcrowding_incidents']]);
        fputcsv($output, ['Schedule Compliance %', $report['schedule_compliance']]);
        fputcsv($output, ['']);

        // Write capacity statistics
        fputcsv($output, ['Capacity Statistics']);
        fputcsv($output, ['']);
        fputcsv($output, ['Average Load', $report['capacity_statistics']['average_load']]);
        fputcsv($output, ['Max Load', $report['capacity_statistics']['max_load']]);
        fputcsv($output, ['Overcrowding Incidents', $report['capacity_statistics']['overcrowding_incidents']]);
        fputcsv($output, ['Average Capacity Utilization %', $report['capacity_statistics']['average_capacity_utilization_percentage']]);
        fputcsv($output, ['']);

        // Write route efficiency
        fputcsv($output, ['Route Efficiency']);
        fputcsv($output, ['']);
        fputcsv($output, ['Route Name', 'Total Trips', 'Completed Trips', 'Avg Duration (min)', 'On-Time %']);
        foreach ($report['route_efficiency'] as $route) {
            fputcsv($output, [
                $route['route_name'],
                $route['total_trips'],
                $route['completed_trips'],
                $route['average_duration_minutes'],
                $route['on_time_percentage'],
            ]);
        }
        fputcsv($output, ['']);

        // Write driver performance
        fputcsv($output, ['Driver Performance']);
        fputcsv($output, ['']);
        fputcsv($output, ['Driver Name', 'Total Trips', 'Completed Trips', 'Schedule Adherence %', 'Avg Passenger Load']);
        foreach ($report['driver_performance'] as $driver) {
            fputcsv($output, [
                $driver['driver_name'],
                $driver['total_trips'],
                $driver['completed_trips'],
                $driver['schedule_adherence_percentage'],
                $driver['average_passenger_load'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
