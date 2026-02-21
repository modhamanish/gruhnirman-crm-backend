<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: "/api/users",
        summary: "Get all users",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    #[OA\Post(
        path: "/api/users",
        summary: "Create a new user",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["name", "email", "password", "role"],
                    properties: [
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "email", type: "string", format: "email"),
                        new OA\Property(property: "password", type: "string", format: "password"),
                        new OA\Property(property: "contact_number", type: "string", nullable: true),
                        new OA\Property(property: "address", type: "string", nullable: true),
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive"]),
                        new OA\Property(property: "role", type: "string", example: "Admin"),
                        new OA\Property(property: "profile_image", type: "string", format: "binary"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "User created"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'contact_number' => 'nullable|string|max:10|min:10',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'role' => 'required|exists:roles,name',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'contact_number.min' => 'Contact number must be 10 digits',
            'contact_number.max' => 'Contact number must be 10 digits',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['password'] = Hash::make($request->password);

            $folderPath = public_path('uploads/users');
            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            if ($request->hasFile('profile_image')) {
                $imageName = time() . '.' . $request->profile_image->extension();
                $request->profile_image->move($folderPath, $imageName);
                $data['profile_image'] = $imageName;
            }

            $user = User::create($data);
            $user->assignRole($request->role);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $user->load('roles')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: "/api/users/{id}",
        summary: "Get user details",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function show(User $user)
    {
        return response()->json([
            'status' => 'success',
            'data' => $user->load('roles', 'permissions')
        ]);
    }

    #[OA\Post(
        path: "/api/users/{id}",
        summary: "Update user",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["name", "email", "role"],
                    properties: [
                        new OA\Property(property: "name", type: "string"),
                        new OA\Property(property: "email", type: "string", format: "email"),
                        new OA\Property(property: "contact_number", type: "string", nullable: true),
                        new OA\Property(property: "address", type: "string", nullable: true),
                        new OA\Property(property: "status", type: "string", enum: ["active", "inactive"]),
                        new OA\Property(property: "role", type: "string", example: "Admin"),
                        new OA\Property(property: "profile_image", type: "string", format: "binary"),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Updated"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'contact_number' => 'nullable|string|max:10|min:10',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'role' => 'required|exists:roles,name',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'contact_number.min' => 'Contact number must be 10 digits',
            'contact_number.max' => 'Contact number must be 10 digits',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            // if ($request->filled('password')) {
            //     $data['password'] = Hash::make($request->password);
            // }

            $folderPath = public_path('uploads/users');
            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0777, true, true);
            }

            if ($request->hasFile('profile_image')) {
                $imageName = time() . '.' . $request->profile_image->extension();
                $request->profile_image->move($folderPath, $imageName);
                $data['profile_image'] = $imageName;

                if ($user->profile_image && file_exists($folderPath . '/' . $user->profile_image)) {
                    @unlink($folderPath . '/' . $user->profile_image);
                }
            }

            $user->update($data);

            if ($request->filled('role')) {
                $user->syncRoles([$request->role]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $user->load('roles')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    #[OA\Delete(
        path: "/api/users/{id}",
        summary: "Delete user",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not Found")
        ]
    )]
    public function destroy(User $user)
    {
        if ($user->hasRole('Super Admin')) {
            return response()->json(['status' => 'error', 'message' => 'Super Admin cannot be deleted'], 403);
        }

        $user->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }
}
