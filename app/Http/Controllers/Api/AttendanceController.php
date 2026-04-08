<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class AttendanceController extends Controller
{
    #[OA\Get(
        path: "/api/attendances",
        summary: "Get attendance list",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "user_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "date", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function index(Request $request)
    {
        $query = Attendance::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        $perPage = $request->input('per_page', 10);
        $attendances = $query->orderBy('date', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'results' => $attendances
        ]);
    }

    #[OA\Get(
        path: "/api/attendances/today-status",
        summary: "Get today's attendance status for a user",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "user_id", in: "query", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function todayStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $attendance = Attendance::where('user_id', $request->user_id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        return response()->json([
            'status' => 'success',
            'results' => $attendance
        ]);
    }

    #[OA\Post(
        path: "/api/attendances/check-in",
        summary: "Mark check-in",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id"],
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 422, description: "Validation Error")
        ]
    )]
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $today = Carbon::today()->toDateString();
        $existing = Attendance::where('user_id', $request->user_id)->where('date', $today)->first();

        if ($existing) {
            return response()->json(['status' => 'error', 'message' => 'Already checked in for today'], 422);
        }

        $attendance = Attendance::create([
            'user_id' => $request->user_id,
            'date' => $today,
            'check_in' => Carbon::now()->toTimeString(),
            'status' => 'checked_in'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Checked in successfully',
            'results' => $attendance
        ]);
    }

    #[OA\Post(
        path: "/api/attendances/break-start",
        summary: "Start break",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id"],
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function breakStart(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user_id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        if (!$attendance) {
            return response()->json(['status' => 'error', 'message' => 'Not checked in today'], 422);
        }

        if ($attendance->status === 'on_break') {
            return response()->json(['status' => 'error', 'message' => 'Already on break'], 422);
        }

        if ($attendance->status === 'checked_out') {
            return response()->json(['status' => 'error', 'message' => 'Already checked out'], 422);
        }

        $attendance->update([
            'break_start' => Carbon::now()->toTimeString(),
            'status' => 'on_break'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Break started',
            'results' => $attendance
        ]);
    }

    #[OA\Post(
        path: "/api/attendances/break-end",
        summary: "End break",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id"],
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function breakEnd(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user_id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        if (!$attendance || $attendance->status !== 'on_break') {
            return response()->json(['status' => 'error', 'message' => 'Not on break'], 422);
        }

        $now = Carbon::now();
        $breakStart = Carbon::parse($attendance->break_start);
        $durationSeconds = $now->diffInSeconds($breakStart);

        // Accumulate break hours? For now assuming 1 break.
        // If we want multiple breaks, we need a separate table.
        // But per requirements "break, break end", I'll just store this one.

        $attendance->update([
            'break_end' => $now->toTimeString(),
            'total_break_hours' => gmdate("H:i:s", $durationSeconds),
            'status' => 'checked_in'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Break ended',
            'results' => $attendance
        ]);
    }

    #[OA\Post(
        path: "/api/attendances/check-out",
        summary: "Mark check-out",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id"],
                properties: [
                    new OA\Property(property: "user_id", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function checkOut(Request $request)
    {
        $attendance = Attendance::where('user_id', $request->user_id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        if (!$attendance) {
            return response()->json(['status' => 'error', 'message' => 'Not checked in today'], 422);
        }

        if ($attendance->status === 'checked_out') {
            return response()->json(['status' => 'error', 'message' => 'Already checked out'], 422);
        }

        $now = Carbon::now();
        $checkIn = Carbon::parse($attendance->check_in);

        // Calculate total time
        $totalSeconds = $now->diffInSeconds($checkIn);

        // Substract break time if exists
        $breakSeconds = 0;
        if ($attendance->total_break_hours) {
            $parts = explode(':', $attendance->total_break_hours);
            $breakSeconds = ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }

        $workingSeconds = $totalSeconds - $breakSeconds;

        $attendance->update([
            'check_out' => $now->toTimeString(),
            'total_working_hours' => gmdate("H:i:s", $workingSeconds),
            'status' => 'checked_out'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Checked out successfully',
            'results' => $attendance
        ]);
    }
}
