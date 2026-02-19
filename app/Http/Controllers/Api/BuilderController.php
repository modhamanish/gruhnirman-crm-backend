<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class BuilderController extends Controller
{
    #[OA\Get(
        path: "/api/builders",
        summary: "Get all builders",
        tags: ["Builders"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index()
    {
        $builders = Builder::all();
        return response()->json([
            'status' => 'success',
            'data' => $builders
        ]);
    }

    #[OA\Post(
        path: "/api/builders",
        summary: "Create a new builder",
        tags: ["Builders"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["company_name", "name", "contact_number", "email", "office_address"],
                    properties: [
                        new OA\Property(property: "company_name", type: "string"),
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "company_logo", type: "string", format: "binary", description: "Builder Company Logo File"),
                        new OA\Property(property: "experience", type: "string", nullable: true),
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive"]),
                        new OA\Property(property: "contact_number", type: "string"),
                        new OA\Property(property: "email", type: "string", format: "email"),
                        new OA\Property(property: "website", type: "string", nullable: true),
                        new OA\Property(property: "office_address", type: "string"),
                        new OA\Property(property: "total_project_completed", type: "integer"),
                        new OA\Property(property: "ongoing_projects", type: "integer"),
                    ]
                )
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
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'experience' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'contact_number' => 'required|string|max:20',
            'email' => 'required|email|unique:builders,email',
            'website' => 'nullable|url',
            'office_address' => 'required|string',
            'total_project_completed' => 'nullable|integer',
            'ongoing_projects' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        if ($request->hasFile('company_logo')) {
            $imageName = time() . '.' . $request->company_logo->extension();
            $request->company_logo->move(public_path('uploads/builders'), $imageName);
            $data['company_logo'] = 'uploads/builders/' . $imageName;
        }

        $builder = Builder::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Builder created successfully',
            'data' => $builder
        ], 201);
    }

    #[OA\Get(
        path: "/api/builders/{id}",
        summary: "Get builder details",
        tags: ["Builders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show(Builder $builder)
    {
        return response()->json([
            'status' => 'success',
            'data' => $builder
        ]);
    }

    #[OA\Put(
        path: "/api/builders/{id}",
        summary: "Update builder",
        tags: ["Builders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "company_name", type: "string"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "status", type: "string", enum: ["active", "inactive"]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function update(Request $request, Builder $builder)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'sometimes|required|string|max:255',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:builders,email,' . $builder->id,
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $builder->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Builder updated successfully',
            'data' => $builder
        ]);
    }

    #[OA\Delete(
        path: "/api/builders/{id}",
        summary: "Delete builder",
        tags: ["Builders"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function destroy(Builder $builder)
    {
        $builder->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Builder deleted successfully'
        ]);
    }
}
