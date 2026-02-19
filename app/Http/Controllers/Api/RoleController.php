<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(
        path: "/api/roles",
        summary: "Get list of roles",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index()
    {
        $roles = Role::with('permissions')->get();
        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    #[OA\Post(
        path: "/api/roles",
        summary: "Create a new role",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "permissions"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Manager"),
                    new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string"), example: ["role-list", "role-create"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Role created successfully")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $role = Role::create(['name' => $request->name]);
            $role->syncPermissions($request->permissions);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Role created successfully',
                'data' => $role->load('permissions')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/roles/{id}",
        summary: "Get role details",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Role ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Role not found")
        ]
    )]
    public function show(Role $role)
    {
        return response()->json([
            'status' => 'success',
            'data' => $role->load('permissions')
        ]);
    }

    #[OA\Put(
        path: "/api/roles/{id}",
        summary: "Update role",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Role ID", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "permissions"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Updated Manager"),
                    new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string"), example: ["role-list"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Role updated successfully"),
            new OA\Response(response: 404, description: "Role not found")
        ]
    )]
    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();
            $role->name = $request->name;
            $role->save();
            $role->syncPermissions($request->permissions);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Role updated successfully',
                'data' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: "/api/roles/{id}",
        summary: "Delete role",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Role ID", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Role deleted successfully"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Role not found")
        ]
    )]
    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Super Admin role cannot be deleted'
            ], 403);
        }

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully'
        ]);
    }

    #[OA\Get(
        path: "/api/permissions",
        summary: "Get list of permissions",
        tags: ["Roles"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function getPermissions()
    {
        $permissions = Permission::all();
        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }
}
