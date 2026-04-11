<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LeadActivityController extends Controller
{
    #[OA\Get(
        path: "/api/activities",
        summary: "Get all activity logs with filtering",
        tags: ["Activities"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["inquiry", "lead", "follow_up", "site_visit"])),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "lead_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = LeadActivity::with(['user', 'lead']);

        if ($request->has('type')) {
            $type = $request->input('type');
            if ($type === 'inquiry') {
                $query->whereHas('lead', function ($q) {
                    $q->where('type', 'inquiry');
                })->where('loggable_type', 'App\Models\Lead');
            } elseif ($type === 'lead') {
                $query->whereHas('lead', function ($q) {
                    $q->where('type', 'lead');
                })->where('loggable_type', 'App\Models\Lead');
            } elseif ($type === 'follow_up') {
                $query->where('loggable_type', 'App\Models\FollowUp');
            } elseif ($type === 'site_visit') {
                $query->where('loggable_type', 'App\Models\SiteVisit');
            }
        }

        if ($request->has('lead_id')) {
            $query->where('lead_id', $request->input('lead_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('lead', function ($l) use ($search) {
                        $l->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('contact_number', 'LIKE', "%{$search}%");
                    });
            });
        }

        $perPage = $request->input('per_page', 15);
        $activities = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'results' => $activities
        ]);
    }
}
