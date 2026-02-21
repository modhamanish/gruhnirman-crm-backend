<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class PropertyController extends Controller
{
    #[OA\Get(
        path: "/api/properties",
        summary: "Get all properties",
        tags: ["Properties"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "builder_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "category_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "property_type_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["active", "inactive"])),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = Property::with(['builder', 'category', 'propertyType']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('builder_id')) {
            $query->where('builder_id', $request->input('builder_id'));
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('property_type_id')) {
            $query->where('property_type_id', $request->input('property_type_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = $request->input('per_page', 10);
        $properties = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'results' => $properties
        ]);
    }

    #[OA\Post(
        path: "/api/properties",
        summary: "Create a new property",
        tags: ["Properties"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["builder_id", "category_id", "property_type_id", "name", "starting_price"],
                    properties: [
                        new OA\Property(property: "builder_id", type: "integer", example: 1),
                        new OA\Property(property: "category_id", type: "integer", example: 1),
                        new OA\Property(property: "property_type_id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Sunrise Heights"),
                        new OA\Property(property: "sq_feet", type: "string", example: "1250 sqft"),
                        new OA\Property(property: "starting_price", type: "number", format: "float", example: 4500000),
                        new OA\Property(property: "ending_price", type: "number", format: "float", example: 6000000),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Property Image"),
                        new OA\Property(property: "address", type: "string", example: "123 Street, City"),
                        new OA\Property(property: "latitude", type: "string", example: "22.3421061"),
                        new OA\Property(property: "longitude", type: "string", example: "70.7299631"),
                        new OA\Property(property: "youtube_link", type: "string", example: "https://youtu.be/..."),
                        new OA\Property(property: "brochure", type: "string", format: "binary", description: "Property Brochure"),
                        new OA\Property(property: "additional_note", type: "string", example: "Near Metro station"),
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive"]),
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
            'builder_id' => 'required|exists:builders,id',
            'category_id' => 'required|exists:categories,id',
            'property_type_id' => 'required|exists:property_types,id',
            'name' => 'required|string|max:255',
            'starting_price' => 'required|numeric',
            'ending_price' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'brochure' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'status' => 'required|in:active,inactive',
        ], [
            'builder_id.exists' => 'Builder not found'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['image', 'brochure']);
        $propertyFolder = public_path('uploads/properties');
        if (!File::exists($propertyFolder)) {
            File::makeDirectory($propertyFolder, 0777, true);
        }
        if ($request->hasFile('image')) {
            $imageName = time() . '_property.' . $request->image->extension();
            $request->image->move($propertyFolder, $imageName);
            $data['image'] = $imageName;
        }

        $brochureFolder = public_path('uploads/brochures');
        if (!File::exists($brochureFolder)) {
            File::makeDirectory($brochureFolder, 0777, true);
        }
        if ($request->hasFile('brochure')) {
            $brochureName = time() . '_brochure.' . $request->brochure->extension();
            $request->brochure->move($brochureFolder, $brochureName);
            $data['brochure'] = $brochureName;
        }

        $property = Property::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'results' => $property->load(['builder', 'category', 'propertyType'])
        ], 201);
    }

    #[OA\Get(
        path: "/api/properties/{id}",
        summary: "Get property details",
        tags: ["Properties"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show(Property $property)
    {
        return response()->json([
            'status' => 'success',
            'results' => $property->load(['builder', 'category', 'propertyType'])
        ]);
    }

    #[OA\Post(
        path: "/api/properties/{id}",
        summary: "Update property (Use POST with _method=PUT for file uploads)",
        tags: ["Properties"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["builder_id", "category_id", "property_type_id", "name", "starting_price"],
                    properties: [
                        new OA\Property(property: "builder_id", type: "integer", example: 1),
                        new OA\Property(property: "category_id", type: "integer", example: 1),
                        new OA\Property(property: "property_type_id", type: "integer", example: 1),
                        new OA\Property(property: "name", type: "string", example: "Sunrise Heights"),
                        new OA\Property(property: "sq_feet", type: "string", example: "1250 sqft"),
                        new OA\Property(property: "starting_price", type: "number", format: "float", example: 4500000),
                        new OA\Property(property: "ending_price", type: "number", format: "float", example: 6000000),
                        new OA\Property(property: "image", type: "string", format: "binary", description: "Property Image"),
                        new OA\Property(property: "address", type: "string", example: "123 Street, City"),
                        new OA\Property(property: "latitude", type: "string", example: "22.3421061"),
                        new OA\Property(property: "longitude", type: "string", example: "70.7299631"),
                        new OA\Property(property: "youtube_link", type: "string", example: "https://youtu.be/..."),
                        new OA\Property(property: "brochure", type: "string", format: "binary", description: "Property Brochure"),
                        new OA\Property(property: "additional_note", type: "string", example: "Near Metro station"),
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive"]),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function update(Request $request, Property $property)
    {
        $validator = Validator::make($request->all(), [
            'builder_id' => 'required|exists:builders,id',
            'category_id' => 'required|exists:categories,id',
            'property_type_id' => 'required|exists:property_types,id',
            'name' => 'required|string|max:255',
            'starting_price' => 'required|numeric',
            'ending_price' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'brochure' => 'nullable|mimes:pdf,doc,docx|max:5120',
            'status' => 'required|in:active,inactive',
        ], [
            'builder_id.exists' => 'Builder not found',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except(['image', 'brochure']);
        $propertyFolder = public_path('uploads/properties');
        if (!File::exists($propertyFolder)) {
            File::makeDirectory($propertyFolder, 0777, true, true);
        }
        if ($request->hasFile('image')) {
            $imageName = time() . '_property.' . $request->image->extension();
            $request->image->move($propertyFolder, $imageName);
            $data['image'] = $imageName;

            if ($property->image && file_exists($propertyFolder . '/' . $property->image)) {
                @unlink($propertyFolder . '/' . $property->image);
            }
        }

        $brochureFolder = public_path('uploads/brochures');
        if (!File::exists($brochureFolder)) {
            File::makeDirectory($brochureFolder, 0777, true, true);
        }
        if ($request->hasFile('brochure')) {
            $brochureName = time() . '_brochure.' . $request->brochure->extension();
            $request->brochure->move($brochureFolder, $brochureName);
            $data['brochure'] = $brochureName;

            if ($property->brochure && file_exists($brochureFolder . '/' . $property->brochure)) {
                @unlink($brochureFolder . '/' . $property->brochure);
            }
        }

        $property->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Property updated successfully',
            'results' => $property->load(['builder', 'category', 'propertyType'])
        ]);
    }

    #[OA\Delete(
        path: "/api/properties/{id}",
        summary: "Delete property",
        tags: ["Properties"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function destroy(Property $property)
    {
        $propertyFolder = 'uploads/properties';
        $brochureFolder = 'uploads/brochures';
        if ($property->image && file_exists(public_path($propertyFolder . '/' . $property->image))) {
            @unlink(public_path($propertyFolder . '/' . $property->image));
        }
        if ($property->brochure && file_exists(public_path($brochureFolder . '/' . $property->brochure))) {
            @unlink(public_path($brochureFolder . '/' . $property->brochure));
        }

        $property->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Property deleted successfully'
        ]);
    }
}
