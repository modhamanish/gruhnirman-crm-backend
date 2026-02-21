<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PropertyTypeController extends Controller
{
    #[OA\Get(
        path: "/api/property-types",
        summary: "Get all property types",
        tags: ["Property Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "category_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
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
        $query = PropertyType::with('category');

        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('page') && $request->has('per_page')) {
            $propertyTypes = $query->orderBy('name', 'asc')->paginate($request->per_page);
        } else {
            $propertyTypes = $query->orderBy('name', 'asc')->get();
        }

        return response()->json([
            'status' => 'success',
            'results' => $propertyTypes
        ]);
    }

    #[OA\Post(
        path: "/api/property-types",
        summary: "Create a new property type",
        tags: ["Property Types"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id", "name"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Apartment"),
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
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $propertyType = PropertyType::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Property type created successfully',
            'results' => $propertyType->load('category')
        ], 201);
    }

    #[OA\Get(
        path: "/api/property-types/{id}",
        summary: "Get property type details",
        tags: ["Property Types"],
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
        $propertyType = PropertyType::with('category')->find($id);

        if (!$propertyType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property type not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'results' => $propertyType
        ]);
    }

    #[OA\Put(
        path: "/api/property-types/{id}",
        summary: "Update property type",
        tags: ["Property Types"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["category_id", "name"],
                properties: [
                    new OA\Property(property: "category_id", type: "integer", example: 1),
                    new OA\Property(property: "name", type: "string", example: "Apartment Updated"),
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
        $propertyType = PropertyType::find($id);

        if (!$propertyType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property type not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $propertyType->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Property type updated successfully',
            'results' => $propertyType->load('category')
        ]);
    }

    #[OA\Delete(
        path: "/api/property-types/{id}",
        summary: "Delete property type",
        tags: ["Property Types"],
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
        $propertyType = PropertyType::find($id);

        if (!$propertyType) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property type not found'
            ], 404);
        }

        $propertyType->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Property type deleted successfully'
        ]);
    }
}
