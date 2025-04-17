<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\EventOccurence;
use App\Models\Member;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'event_occurence_id' => 'required|numeric',
            'attendance_type' => 'required|string|max:20',
            'member_id' => 'nullable|string',
            'guest_name' => 'required_without:member_id|nullable|string|max:255',
            'attended_at' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $attendance_data = [
            'event_occurence_id' => $validated['event_occurence_id'],
            'attendance_type' => $validated['attendance_type'],
            'attended_at' => $validated['attended_at'],
        ];

        if (isset($validated['member_id'])) {
            $member_id = Member::getMemberIDFromQRCodeValue($validated['member_id']);

            $member = Member::where('id', $member_id)->first();
            if (!$member) {
                return response()->json(['error' => 'QR Code Not Detected'], 422);
            }

            $existing_attendance = Attendance::where('event_occurence_id', $validated['event_occurence_id'])
                ->where('member_id', $member_id)
                ->first();

            if ($existing_attendance) {
                $existing_attendance->load('member');
                return $existing_attendance;
            }

            $attendance_data['member_id'] = $member_id;
        } else {
            $attendance_data['guest_name'] = $validated['guest_name'];
        }

        $attendance = Attendance::create($attendance_data);

        $attendance->load('member');

        return $attendance;
    }

    function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'event_occurence_id' => 'required|numeric',
            'attendance_type' => 'required|string|max:20',
            'member_id' => 'nullable|string',
            'guest_name' => 'required_without:member_id|nullable|string|max:255',
            'attended_at' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $attendance = Attendance::where('event_occurence_id', $validated['event_occurence_id'])
            ->where('id', $id)
            ->first();

        if (!$attendance) {
            return response(['message' => 'Attendance not found'], 404);
        }

        $attendance->member_id = $validated['member_id'];
        $attendance->guest_name = $validated['guest_name'];
        $attendance->attended_at = $validated['attended_at'];
        $attendance->save();

        $attendance->load('member');

        return response()->json($attendance);
    }

    public function list(Request $request)
    {
        $validated = $request->validate([
            'event_occurence_id' => 'numeric|nullable',
            'event_id' => 'required_without:event_occurence_id|numeric',
            'start_time' => 'required_without:event_occurence_id|date_format:Y-m-d H:i:s'
        ]);

        $occurence = null;

        if (isset($validated['event_occurence_id'])) {
            $occurence = EventOccurence::where('id', $validated['event_occurence_id'])
                ->first();
        } else {
            $occurence = EventOccurence::where('event_id', $validated['event_id'])
                ->where('start_time', $validated['start_time'])
                ->first();
        }

        if (!$occurence) {
            return response()->json(['error' => 'Event Occurence Not Found'], 422);
        }

        $attendances = Attendance::where('event_occurence_id', $occurence->id)
            ->with('member')
            ->paginate(10);

        $occurence->load('event');

        return response()->json([
            'event' => $occurence->event,
            'attendances' => $attendances,
        ]);
    }

    public function detail(Request $request, string $event_occurence_id, string $id)
    {
        $attendance = Attendance::where('event_occurence_id', $event_occurence_id)
            ->where('id', $id)
            ->first();

        if (!$attendance) {
            return response(['message' => 'Attendance not found'], 404);
        }

        $attendance->load('member');

        return response()->json($attendance);
    }
}
