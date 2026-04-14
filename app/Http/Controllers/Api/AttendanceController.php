<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceLog;
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
        $query = Attendance::with(['user', 'logs']);

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
        path: "/api/attendances/{id}",
        summary: "Get attendance details with logs",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'logs'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'results' => $attendance
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

        $attendance = Attendance::with('logs')->where('user_id', $request->user_id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        return response()->json([
            'status' => 'success',
            'results' => $attendance
        ]);
    }

    #[OA\Get(
        path: "/api/attendances/weekly-record",
        summary: "Get weekly attendance record for a user",
        tags: ["Attendance"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "user_id", in: "query", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    public function weeklyRecord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $attendances = Attendance::with('logs')
            ->where('user_id', $request->user_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'results' => $attendances
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
            'status' => 'checked_in'
        ]);

        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'status' => 'checked_in',
            'log_time' => Carbon::now()->toTimeString()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Checked in successfully',
            'results' => $attendance->load('logs')
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
            'status' => 'on_break'
        ]);

        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'status' => 'on_break',
            'log_time' => Carbon::now()->toTimeString()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Break started',
            'results' => $attendance->load('logs')
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

        $attendance->update([
            'status' => 'checked_in'
        ]);

        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'status' => 'checked_in',
            'log_time' => Carbon::now()->toTimeString()
        ]);

        $this->calculateTotals($attendance);

        return response()->json([
            'status' => 'success',
            'message' => 'Break ended',
            'results' => $attendance->load('logs')
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

        $attendance->update([
            'status' => 'checked_out'
        ]);

        AttendanceLog::create([
            'attendance_id' => $attendance->id,
            'status' => 'checked_out',
            'log_time' => Carbon::now()->toTimeString()
        ]);

        $this->calculateTotals($attendance);

        return response()->json([
            'status' => 'success',
            'message' => 'Checked out successfully',
            'results' => $attendance->load('logs')
        ]);
    }

    private function calculateTotals($attendance)
    {
        $logs = $attendance->logs()->orderBy('id', 'asc')->get();

        $totalBreakSeconds = 0;
        $firstCheckIn = null;
        $lastCheckOut = null;

        $breakStart = null;

        foreach ($logs as $log) {
            $currentTime = Carbon::parse($attendance->date . ' ' . $log->log_time);

            if ($log->status === 'checked_in') {
                if ($firstCheckIn === null) {
                    $firstCheckIn = $currentTime;
                }
                // If we were on break, add duration to totalBreakSeconds
                if ($breakStart !== null) {
                    $totalBreakSeconds += abs($currentTime->diffInSeconds($breakStart));
                    $breakStart = null;
                }
            } elseif ($log->status === 'on_break') {
                $breakStart = $currentTime;
            } elseif ($log->status === 'checked_out') {
                $lastCheckOut = $currentTime;
                // If checking out while on break, close the break
                if ($breakStart !== null) {
                    $totalBreakSeconds += abs($currentTime->diffInSeconds($breakStart));
                    $breakStart = null;
                }
            }
        }

        if ($firstCheckIn !== null) {
            $endPoint = $lastCheckOut ?? Carbon::now();
            $totalWorkingSeconds = abs($endPoint->diffInSeconds($firstCheckIn)) - $totalBreakSeconds;

            $attendance->update([
                'total_working_hours' => $this->formatDuration(max(0, $totalWorkingSeconds)),
                'total_break_hours' => $this->formatDuration($totalBreakSeconds),
            ]);
        }
    }

    private function formatDuration($seconds)
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
