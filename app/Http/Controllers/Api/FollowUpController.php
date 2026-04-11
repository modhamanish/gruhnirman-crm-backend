<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class FollowUpController extends Controller
{
    #[OA\Get(
        path: "/api/follow-ups",
        summary: "Get all follow ups",
        tags: ["Follow Ups"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "lead_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "user_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["Current Only", "Both", "Next Only"])),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = FollowUp::with(['lead', 'user']);

        if ($request->has('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->input('per_page', 10);
        $followUps = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'results' => $followUps
        ]);
    }

    #[OA\Post(
        path: "/api/follow-ups",
        summary: "Create a new follow up",
        tags: ["Follow Ups"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["lead_id", "user_id", "type"],
                properties: [
                    new OA\Property(property: "lead_id", type: "integer", example: 1),
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "type", type: "string", enum: ["Current Only", "Both", "Next Only"], example: "Current Only"),
                    new OA\Property(property: "follow_up_type", type: "string", example: "Call"),
                    new OA\Property(property: "interaction_date_time", type: "string", format: "date-time", example: "2026-04-06 18:13:00"),
                    new OA\Property(property: "duration", type: "string", example: "05:20"),
                    new OA\Property(property: "recording_link", type: "string", example: "https://example.com/recording.mp3"),
                    new OA\Property(property: "notes", type: "string", example: "Discussed project pricing"),
                    new OA\Property(property: "next_follow_up_date_time", type: "string", format: "date-time", example: "2026-04-10 10:00:00"),
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
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:Current Only,Both,Next Only',

            // Current Interaction Details - required if type is 'Current Only' or 'Both'
            'follow_up_type' => 'required_if:type,Current Only,Both|string|max:255',
            'interaction_date_time' => 'required_if:type,Current Only,Both|date_format:Y-m-d H:i:s',
            'duration' => 'nullable|string|max:255',
            'recording_link' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            // Next Follow Up Schedule - required if type is 'Both' or 'Next Only'
            'next_follow_up_date_time' => 'required_if:type,Next Only|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        // If type is Both, we store the current interaction and also create a separate Next Only record if date is provided
        if ($data['type'] === 'Both') {
            // First record (Current Interaction) - status is 'complete'
            $currentData = $data;
            $currentData['status'] = 'complete';
            $followUp = FollowUp::create($currentData);

            // Second record (Next Follow Up) - status is 'schedule'
            if (!empty($request->next_follow_up_date_time)) {
                FollowUp::create([
                    'lead_id' => $data['lead_id'],
                    'user_id' => $data['user_id'],
                    'type' => 'Next Only',
                    'status' => 'schedule',
                    'next_follow_up_date_time' => $request->next_follow_up_date_time,
                ]);
            }
        } elseif ($data['type'] === 'Next Only') {
            // Status is 'schedule' for Next Only
            $data['status'] = 'schedule';
            // Nullify current interaction fields for Next Only
            $data['follow_up_type'] = null;
            $data['interaction_date_time'] = null;
            $data['duration'] = null;
            $data['recording_link'] = null;
            $data['notes'] = null;
            $followUp = FollowUp::create($data);
        } else { // Current Only
            // Status is 'complete' for Current Only
            $data['status'] = 'complete';
            // Nullify next follow up for Current Only
            $data['next_follow_up_date_time'] = null;
            $followUp = FollowUp::create($data);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Follow up created successfully',
            'results' => $followUp->load(['lead', 'user'])
        ], 201);
    }

    #[OA\Get(
        path: "/api/follow-ups/{id}",
        summary: "Get follow up details",
        tags: ["Follow Ups"],
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
        $followUp = FollowUp::with(['lead', 'user'])->find($id);
        if (!$followUp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Follow up not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'results' => $followUp
        ]);
    }

    #[OA\Put(
        path: "/api/follow-ups/{id}",
        summary: "Update follow up",
        tags: ["Follow Ups"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["lead_id", "user_id", "type"],
                properties: [
                    new OA\Property(property: "lead_id", type: "integer", example: 1),
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                    new OA\Property(property: "type", type: "string", enum: ["Current Only", "Both", "Next Only"], example: "Current Only"),
                    new OA\Property(property: "follow_up_type", type: "string", example: "Call"),
                    new OA\Property(property: "interaction_date_time", type: "string", format: "date-time", example: "2026-04-06 18:13:00"),
                    new OA\Property(property: "duration", type: "string", example: "05:20"),
                    new OA\Property(property: "recording_link", type: "string", example: "https://example.com/recording.mp3"),
                    new OA\Property(property: "notes", type: "string", example: "Discussed project pricing"),
                    new OA\Property(property: "next_follow_up_date_time", type: "string", format: "date-time", example: "2026-04-10 10:00:00"),
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
        $followUp = FollowUp::find($id);
        if (!$followUp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Follow up not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'lead_id' => 'required|exists:leads,id',
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:Current Only,Both,Next Only',

            // Current Interaction Details
            'follow_up_type' => 'required_if:type,Current Only,Both|string|max:255',
            'interaction_date_time' => 'required_if:type,Current Only,Both|date_format:Y-m-d H:i:s',
            'duration' => 'nullable|string|max:255',
            'recording_link' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            // Next Follow Up Schedule
            'next_follow_up_date_time' => 'required_if:type,Both,Next Only|date_format:Y-m-d H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        // Nullify fields if not required by type
        if ($data['type'] === 'Next Only') {
            $data['follow_up_type'] = null;
            $data['interaction_date_time'] = null;
            $data['duration'] = null;
            $data['recording_link'] = null;
            $data['notes'] = null;
        } elseif ($data['type'] === 'Current Only') {
            $data['next_follow_up_date_time'] = null;
        }

        $followUp->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Follow up updated successfully',
            'results' => $followUp->load(['lead', 'user'])
        ]);
    }

    #[OA\Delete(
        path: "/api/follow-ups/{id}",
        summary: "Delete follow up",
        tags: ["Follow Ups"],
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
        $followUp = FollowUp::find($id);
        if (!$followUp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Follow up not found'
            ], 404);
        }

        $followUp->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Follow up deleted successfully'
        ]);
    }
}
