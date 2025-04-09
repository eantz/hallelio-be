<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function registerAttendance(Request $request)
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
}
