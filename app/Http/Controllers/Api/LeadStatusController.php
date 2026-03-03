<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class LeadStatusController extends Controller
{
    #[OA\Get(
        path: "/api/lead-statuses",
        summary: "Get all lead statuses",
        tags: ["Lead Statuses"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["active", "inactive"])),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = LeadStatus::query();

        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('page') && $request->has('per_page')) {
            $leadStatuses = $query->orderBy('name', 'asc')->paginate($request->per_page);
        } else {
            $leadStatuses = $query->orderBy('name', 'asc')->get();
        }

        return response()->json([
            'status' => 'success',
            'results' => $leadStatuses
        ]);
    }

    #[OA\Post(
        path: "/api/lead-statuses",
        summary: "Create a new lead status",
        tags: ["Lead Statuses"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "New Lead"),
                    new OA\Property(property: "is_initial", type: "boolean", example: true),
                    new OA\Property(property: "is_final", type: "boolean", example: false),
                    new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active"),
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
            'name' => 'required|string|max:255|unique:lead_statuses,name',
            'is_initial' => 'nullable|boolean',
            'is_final' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Optional: If this is an initial stage, ensure no other status is the initial stage
        if ($request->is_initial) {
            LeadStatus::where('is_initial', true)->update(['is_initial' => false]);
        }

        $leadStatus = LeadStatus::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Lead status created successfully',
            'results' => $leadStatus
        ], 201);
    }

    #[OA\Get(
        path: "/api/lead-statuses/{id}",
        summary: "Get lead status details",
        tags: ["Lead Statuses"],
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
        $leadStatus = LeadStatus::find($id);
        if ($leadStatus) {
            return response()->json([
                'status' => 'success',
                'results' => $leadStatus
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead status not found'
            ], 404);
        }
    }

    #[OA\Put(
        path: "/api/lead-statuses/{id}",
        summary: "Update lead status",
        tags: ["Lead Statuses"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Qualified"),
                    new OA\Property(property: "is_initial", type: "boolean", example: false),
                    new OA\Property(property: "is_final", type: "boolean", example: false),
                    new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 422, description: "Validation Error"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function update(Request $request, $id)
    {
        $leadStatus = LeadStatus::find($id);
        if (!$leadStatus) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead status not found'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:lead_statuses,name,' . $leadStatus->id,
            'is_initial' => 'nullable|boolean',
            'is_final' => 'nullable|boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->is_initial) {
            LeadStatus::where('is_initial', true)->where('id', '!=', $leadStatus->id)->update(['is_initial' => false]);
        }

        $leadStatus->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Lead status updated successfully',
            'results' => $leadStatus
        ]);
    }

    #[OA\Delete(
        path: "/api/lead-statuses/{id}",
        summary: "Delete lead status",
        tags: ["Lead Statuses"],
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
        $leadStatus = LeadStatus::find($id);
        if (!$leadStatus) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead status not found'
            ], 404);
        }
        $leadStatus->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Lead status deleted successfully'
        ]);
    }
}
