<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: "/api/dashboard/lead-stats",
        summary: "Get lead statistics for dashboard",
        tags: ["Dashboard"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function leadStats()
    {
        try {
            $totalLeads = Lead::where('type', 'lead')->count();

            // Get all statuses and their lead counts
            // $statuses = LeadStatus::where('status', 'active')->get()->map(function ($status) {
            //     return [
            //         'id' => $status->id,
            //         'name' => $status->name,
            //         'color' => $status->color,
            //         'icon' => $status->icon,
            //         'count' => Lead::where('type', 'lead')->where('lead_status_id', $status->id)->count(),
            //     ];
            // });

            $statuses = LeadStatus::where('status', 'active')->get();

            return response()->json([
                'status' => 'success',
                'results' => [
                    'total_leads' => $totalLeads,
                    'status_wise' => $statuses
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch lead stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/dashboard/conversion-stats",
        summary: "Get lead conversion stats (monthly data for the year)",
        tags: ["Dashboard"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "year", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lead_status_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function conversionStats(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $statusId = $request->input('lead_status_id');

            $query = LeadStatus::where('status', 'active');
            if ($statusId) {
                $query->where('id', $statusId);
            }
            $statuses = $query->get();

            $results = $statuses->map(function ($status) use ($year) {
                $monthlyData = [];
                for ($month = 1; $month <= 12; $month++) {
                    $count = Lead::where('type', 'lead')
                        ->where('lead_status_id', $status->id)
                        ->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->count();
                    $monthlyData[] = $count;
                }

                return [
                    'id' => $status->id,
                    'name' => $status->name,
                    'color' => $status->color,
                    'icon' => $status->icon,
                    'total_count' => array_sum($monthlyData),
                    'monthly_data' => $monthlyData
                ];
            });

            return response()->json([
                'status' => 'success',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch conversion stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
