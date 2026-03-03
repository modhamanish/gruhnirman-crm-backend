<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class LeadSourceController extends Controller
{
    #[OA\Get(
        path: "/api/lead-sources",
        summary: "Get all lead sources",
        tags: ["Lead Sources"],
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
        $query = LeadSource::query();

        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('page') && $request->has('per_page')) {
            $leadSources = $query->orderBy('name', 'asc')->paginate($request->per_page);
        } else {
            $leadSources = $query->orderBy('name', 'asc')->get();
        }

        return response()->json([
            'status' => 'success',
            'results' => $leadSources
        ]);
    }

    #[OA\Post(
        path: "/api/lead-sources",
        summary: "Create a new lead source",
        tags: ["Lead Sources"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Google Ads"),
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
            'name' => 'required|string|max:255|unique:lead_sources,name',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $leadSource = LeadSource::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Lead source created successfully',
            'results' => $leadSource
        ], 201);
    }

    #[OA\Get(
        path: "/api/lead-sources/{id}",
        summary: "Get lead source details",
        tags: ["Lead Sources"],
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
        $leadSource = LeadSource::find($id);
        if ($leadSource) {
            return response()->json([
                'status' => 'success',
                'results' => $leadSource
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead source not found'
            ], 404);
        }
    }

    #[OA\Put(
        path: "/api/lead-sources/{id}",
        summary: "Update lead source",
        tags: ["Lead Sources"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Google Ads Updated"),
                    new OA\Property(property: "status", type: "string", enum: ["active", "inactive"], example: "active"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 404, description: "Not Found"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $leadSource = LeadSource::find($id);
        if (!$leadSource) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead source not found'
            ], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:lead_sources,name,' . $leadSource->id,
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $leadSource->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Lead source updated successfully',
            'results' => $leadSource
        ]);
    }

    #[OA\Delete(
        path: "/api/lead-sources/{id}",
        summary: "Delete lead source",
        tags: ["Lead Sources"],
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
        $leadSource = LeadSource::find($id);
        if (!$leadSource) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lead source not found'
            ], 404);
        }
        $leadSource->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Lead source deleted successfully'
        ]);
    }
}
