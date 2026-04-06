<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class SiteVisitController extends Controller
{
    #[OA\Get(
        path: "/api/site-visits",
        summary: "Get all site visits",
        tags: ["Site Visits"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "lead_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "property_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "user_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "visited", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["Yes", "No"])),
            new OA\Parameter(name: "interest_status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["Thinking", "Interested", "Highly Interested", "Not Interested"])),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = SiteVisit::with(['lead', 'property', 'executive']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('lead', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            })->orWhereHas('property', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('lead_id')) {
            $query->where('lead_id', $request->input('lead_id'));
        }

        if ($request->has('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('visited')) {
            $query->where('visited', $request->input('visited'));
        }

        if ($request->has('interest_status')) {
            $query->where('interest_status', $request->input('interest_status'));
        }

        $perPage = $request->input('per_page', 10);
        $siteVisits = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'results' => $siteVisits
        ]);
    }

    #[OA\Post(
        path: "/api/site-visits",
        summary: "Create a new site visit",
        tags: ["Site Visits"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["lead_id", "property_id", "user_id", "visit_date"],
                properties: [
                    new OA\Property(property: "lead_id", type: "integer", example: 1),
                    new OA\Property(property: "property_id", type: "integer", example: 1),
                    new OA\Property(property: "user_id", type: "integer", example: 1, description: "Site Visit Executive"),
                    new OA\Property(property: "unit_type", type: "array", items: new OA\Items(type: "integer"), example: [1, 2]),
                    new OA\Property(property: "visit_date", type: "string", format: "date-time", example: "2026-03-12 16:00:00"),
                    new OA\Property(property: "visited", type: "string", enum: ["Yes", "No"], example: "No"),
                    new OA\Property(property: "interest_status", type: "string", enum: ["Thinking", "Interested", "Highly Interested", "Not Interested"], example: "Thinking"),
                    new OA\Property(property: "notes", type: "string", example: "Client is interested in the project."),
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
            'lead_id' => 'required|exists:leads,id',
            'property_id' => 'required|exists:properties,id',
            'user_id' => 'required|exists:users,id',
            'unit_type' => 'nullable|array',
            'unit_type.*' => 'exists:property_items,id',
            'visit_date' => 'required|date_format:Y-m-d H:i:s',
            'visited' => 'required|in:Yes,No',
            'interest_status' => 'required|in:Thinking,Interested,Highly Interested,Not Interested',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }
        $input = $request->all();
        $input['visit_date'] = date('Y-m-d H:i:s', strtotime($request->visit_date));
        $input['added_by'] = Auth::user()->id;
        $siteVisit = SiteVisit::create($input);

        return response()->json([
            'status' => 'success',
            'message' => 'Site visit created successfully',
            'results' => $siteVisit->load(['lead', 'property', 'executive'])
        ], 201);
    }

    #[OA\Get(
        path: "/api/site-visits/{id}",
        summary: "Get site visit details",
        tags: ["Site Visits"],
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
        $siteVisit = SiteVisit::with(['lead', 'property', 'executive'])->find($id);
        if ($siteVisit) {
            return response()->json([
                'status' => 'success',
                'results' => $siteVisit
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Site visit not found'
            ], 404);
        }
    }

    #[OA\Put(
        path: "/api/site-visits/{id}",
        summary: "Update site visit",
        tags: ["Site Visits"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["lead_id", "property_id", "user_id", "visit_date"],
                properties: [
                    new OA\Property(property: "lead_id", type: "integer", example: 1),
                    new OA\Property(property: "property_id", type: "integer", example: 1),
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "unit_type", type: "array", items: new OA\Items(type: "integer"), example: [1, 2]),
                    new OA\Property(property: "visit_date", type: "string", format: "date-time", example: "2026-03-12 16:00:00"),
                    new OA\Property(property: "visited", type: "string", enum: ["Yes", "No"], example: "Yes"),
                    new OA\Property(property: "interest_status", type: "string", enum: ["Thinking", "Interested", "Highly Interested", "Not Interested"], example: "Interested"),
                    new OA\Property(property: "notes", type: "string", example: "Updated interaction notes."),
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
        $siteVisit = SiteVisit::find($id);
        if (!$siteVisit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Site visit not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'property_id' => 'required|exists:properties,id',
            'user_id' => 'required|exists:users,id',
            'unit_type' => 'nullable|array',
            'unit_type.*' => 'exists:property_items,id',
            'visit_date' => 'required|date_format:Y-m-d H:i:s',
            'visited' => 'required|in:Yes,No',
            'interest_status' => 'required|in:Thinking,Interested,Highly Interested,Not Interested',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $input = $request->all();
        $input['visit_date'] = date('Y-m-d H:i:s', strtotime($request->visit_date));
        $siteVisit->update($input);

        return response()->json([
            'status' => 'success',
            'message' => 'Site visit updated successfully',
            'results' => $siteVisit->load(['lead', 'property', 'executive'])
        ]);
    }

    #[OA\Delete(
        path: "/api/site-visits/{id}",
        summary: "Delete site visit",
        tags: ["Site Visits"],
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
        $siteVisit = SiteVisit::find($id);
        if ($siteVisit) {
            $siteVisit->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Site visit deleted successfully'
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Site visit not found'
            ], 404);
        }
    }
}
