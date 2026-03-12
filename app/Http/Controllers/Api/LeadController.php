<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class LeadController extends Controller
{
    #[OA\Get(
        path: "/api/leads",
        summary: "Get all leads",
        tags: ["Leads"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "category_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "property_type_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lead_status_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "lead_source_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "assigned_to", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = Lead::with(['category', 'propertyType', 'leadStatus', 'leadSource', 'assignedTo', 'creator']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('contact_number', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('property_type_id')) {
            $query->where('property_type_id', $request->input('property_type_id'));
        }

        if ($request->has('lead_status_id')) {
            $query->where('lead_status_id', $request->input('lead_status_id'));
        }

        if ($request->has('lead_source_id')) {
            $query->where('lead_source_id', $request->input('lead_source_id'));
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        $perPage = $request->input('per_page', 10);
        $leads = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'results' => $leads
        ]);
    }

    #[OA\Post(
        path: "/api/leads",
        summary: "Create a new lead",
        tags: ["Leads"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "contact_number", "category_id", "property_type_id", "lead_status_id", "lead_source_id"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "cast", type: "string", example: "Patel"),
                    new OA\Property(property: "contact_number", type: "string", example: "9876543210"),
                    new OA\Property(property: "inquiry_for", type: "string", example: "3BHK Flat"),
                    new OA\Property(property: "interested_area", type: "string", example: "Raiya Road"),
                    new OA\Property(property: "area_latitude", type: "string", example: "22.3039"),
                    new OA\Property(property: "area_longitude", type: "string", example: "70.8022"),
                    new OA\Property(property: "min_budget", type: "string", example: "5000000"),
                    new OA\Property(property: "max_budget", type: "string", example: "6000000"),
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "property_type_id", type: "integer", example: 1),
                    new OA\Property(property: "lead_status_id", type: "integer", example: 1),
                    new OA\Property(property: "lead_source_id", type: "integer", example: 1),
                    new OA\Property(property: "assigned_to", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cast' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:20',
            'inquiry_for' => 'nullable|string|max:255',
            'interested_area' => 'nullable|string|max:255',
            'area_latitude' => 'nullable|string|max:255',
            'area_longitude' => 'nullable|string|max:255',
            'min_budget' => 'nullable|string|max:255',
            'max_budget' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'property_type_id' => 'required|exists:property_types,id',
            'lead_status_id' => 'required|exists:lead_statuses,id',
            'lead_source_id' => 'required|exists:lead_sources,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['created_by'] = Auth::id();

        $lead = Lead::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Lead created successfully',
            'results' => $lead->load(['category', 'propertyType', 'leadStatus', 'leadSource', 'assignedTo', 'creator'])
        ], 201);
    }

    #[OA\Get(
        path: "/api/leads/{id}",
        summary: "Get lead details",
        tags: ["Leads"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show($id)
    {
        $lead = Lead::find($id);
        if ($lead) {
            $lead->site_visits_count = $lead->siteVisits()->count();
            // get next site visit
            $lead->next_site_visit = $lead->siteVisits()->where('visit_date', '>=', date('Y-m-d H:i:s'))->orderBy('visit_date', 'asc')->first();
            return response()->json([
                'status' => 'success',
                'results' => $lead->load(['category', 'propertyType', 'leadStatus', 'leadSource', 'assignedTo', 'creator'])
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead not found'
            ], 404);
        }
    }

    #[OA\Put(
        path: "/api/leads/{id}",
        summary: "Update lead",
        tags: ["Leads"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "contact_number", "category_id", "property_type_id", "lead_status_id", "lead_source_id"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "cast", type: "string", example: "Patel"),
                    new OA\Property(property: "contact_number", type: "string", example: "9876543210"),
                    new OA\Property(property: "inquiry_for", type: "string", example: "3BHK Flat"),
                    new OA\Property(property: "interested_area", type: "string", example: "Raiya Road"),
                    new OA\Property(property: "area_latitude", type: "string", example: "22.3039"),
                    new OA\Property(property: "area_longitude", type: "string", example: "70.8022"),
                    new OA\Property(property: "min_budget", type: "string", example: "5000000"),
                    new OA\Property(property: "max_budget", type: "string", example: "6000000"),
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "property_type_id", type: "integer", example: 1),
                    new OA\Property(property: "lead_status_id", type: "integer", example: 1),
                    new OA\Property(property: "lead_source_id", type: "integer", example: 1),
                    new OA\Property(property: "assigned_to", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $lead = Lead::find($id);
        if (!$lead) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead not found'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cast' => 'nullable|string|max:255',
            'contact_number' => 'required|string|max:20',
            'inquiry_for' => 'nullable|string|max:255',
            'interested_area' => 'nullable|string|max:255',
            'area_latitude' => 'nullable|string|max:255',
            'area_longitude' => 'nullable|string|max:255',
            'min_budget' => 'nullable|string|max:255',
            'max_budget' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'property_type_id' => 'required|exists:property_types,id',
            'lead_status_id' => 'required|exists:lead_statuses,id',
            'lead_source_id' => 'required|exists:lead_sources,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $lead->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Lead updated successfully',
            'results' => $lead->load(['category', 'propertyType', 'leadStatus', 'leadSource', 'assignedTo', 'creator'])
        ]);
    }

    #[OA\Delete(
        path: "/api/leads/{id}",
        summary: "Delete lead",
        tags: ["Leads"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function destroy($id)
    {
        $lead = Lead::find($id);
        if ($lead) {
            $lead->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Lead deleted successfully'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead not found'
            ], 404);
        }
    }
}
