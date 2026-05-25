<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                    $countQuery = Lead::where('type', 'lead')->where('lead_status_id', $status->id);
                    // if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Admin') && !Auth::user()->hasPermissionTo('lead-access-all')) {
                    //     $countQuery->where('created_by', Auth::user()->id)->orWhere('assigned_to', Auth::user()->id);
                    // }
                    $count = $countQuery->whereYear('created_at', $year)->whereMonth('created_at', $month)->count();
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

    #[OA\Get(
        path: "/api/dashboard/activity-highlights",
        summary: "Get activity highlights for dashboard",
        tags: ["Dashboard"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function activityHighlights()
    {
        try {
            $today = \Carbon\Carbon::today();
            $weekStart = \Carbon\Carbon::now()->startOfWeek();
            $weekEnd = \Carbon\Carbon::now()->endOfWeek();

            $upcomingFollowups = FollowUp::where('status', 'schedule')
                ->whereDate('next_follow_up_date_time', $today);
            // if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Admin') && !Auth::user()->hasPermissionTo('lead-access-all')) {
            //     $upcomingFollowups->where('created_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            // }
            $upcomingFollowups = $upcomingFollowups->count();

            $missedFollowups = FollowUp::where('status', 'schedule')
                ->where('next_follow_up_date_time', '<', \Carbon\Carbon::now());
            // if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Admin') && !Auth::user()->hasPermissionTo('lead-access-all')) {
            //     $missedFollowups->where('created_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            // }
            $missedFollowups = $missedFollowups->count();

            $siteVisitsThisWeek = SiteVisit::whereDate('visit_date', '>=', $weekStart)
                ->whereDate('visit_date', '<=', $weekEnd);
            // if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Admin') && !Auth::user()->hasPermissionTo('lead-access-all')) {
            //     $siteVisitsThisWeek->where('added_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            // }
            $siteVisitsThisWeek = $siteVisitsThisWeek->count();

            $missedVisits = SiteVisit::where('visited', 0)
                ->where('visit_date', '<', $today);
            // if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Admin') && !Auth::user()->hasPermissionTo('lead-access-all')) {
            //     $missedVisits->where('added_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            // }
            $missedVisits = $missedVisits->count();

            return response()->json([
                'status' => 'success',
                'results' => [
                    'upcoming_followups' => $upcomingFollowups,
                    'missed_followups' => $missedFollowups,
                    'site_visits_this_week' => $siteVisitsThisWeek,
                    'missed_visits' => $missedVisits,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch activity highlights',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/dashboard/activity-schedule",
        summary: "Get upcoming and recent activities for dashboard schedule",
        tags: ["Dashboard"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function activitySchedule()
    {
        try {
            $schedule = [];

            // 1. Upcoming Site Visits
            $upcomingSiteVisits = \App\Models\SiteVisit::with(['lead', 'property'])
                ->where('visited', 0)
                ->where('visit_date', '>=', \Carbon\Carbon::today());
            // if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Admin') && !Auth::user()->hasPermissionTo('lead-access-all')) {
            //     $upcomingSiteVisits->where('added_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            // }
            $upcomingSiteVisits = $upcomingSiteVisits->orderBy('visit_date', 'asc')
                ->limit(5)
                ->get();

            foreach ($upcomingSiteVisits as $visit) {
                $schedule[] = [
                    'type' => 'upcoming_site_visit',
                    'title' => "Upcoming Site Visit: " . ($visit->lead->name ?? 'Unknown') . " @ " . ($visit->property->name ?? 'Project'),
                    'description' => $visit->notes ?? "Scheduled site tour.",
                    'time_display' => \Carbon\Carbon::parse($visit->visit_date)->diffForHumans(),
                    'timestamp' => $visit->visit_date,
                    'color' => 'red'
                ];
            }

            // 2. Upcoming Follow-ups
            $upcomingFollowUps = \App\Models\FollowUp::with(['lead'])
                ->where('status', 'schedule')
                ->where('next_follow_up_date_time', '>=', \Carbon\Carbon::now());
            // if (!Auth::user()->hasRole('Super Admin') && !Auth::user()->hasRole('Admin') && !Auth::user()->hasPermissionTo('lead-access-all')) {
            //     $upcomingFollowUps->where('created_by', Auth::user()->id)->orWhere('user_id', Auth::user()->id);
            // }
            $upcomingFollowUps = $upcomingFollowUps->orderBy('next_follow_up_date_time', 'asc')
                ->limit(5)
                ->get();

            foreach ($upcomingFollowUps as $followUp) {
                $schedule[] = [
                    'type' => 'upcoming_follow_up',
                    'title' => "Upcoming Follow-up: " . ($followUp->lead->name ?? 'Unknown'),
                    'description' => $followUp->notes ?? "Regular follow-up call.",
                    'time_display' => \Carbon\Carbon::parse($followUp->next_follow_up_date_time)->diffForHumans(),
                    'timestamp' => $followUp->next_follow_up_date_time,
                    'color' => 'orange'
                ];
            }

            // 3. Recent Lead Activity
            $recentActivities = \App\Models\LeadActivity::with(['lead', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($recentActivities as $activity) {
                $type = 'recent_activity';
                $color = 'blue';

                if (str_contains(strtolower($activity->description), 'booking confirmed')) {
                    $type = 'booking_confirmed';
                    $color = 'green';
                }

                $schedule[] = [
                    'type' => $type,
                    'title' => $activity->description,
                    'description' => "By " . ($activity->user->name ?? 'System'),
                    'time_display' => \Carbon\Carbon::parse($activity->created_at)->diffForHumans(),
                    'timestamp' => $activity->created_at->toDateTimeString(),
                    'color' => $color
                ];
            }

            // Sort everything by timestamp, but we want a logical order: Upcoming first (asc), then Recent (desc)
            // Actually, usually a feed sorts everything descending. 
            // But if we want "Upcoming" on top, we might need a custom sort.

            usort($schedule, function ($a, $b) {
                return strcmp($b['timestamp'], $a['timestamp']);
            });

            return response()->json([
                'status' => 'success',
                'results' => $schedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch activity schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
