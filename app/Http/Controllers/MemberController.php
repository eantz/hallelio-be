<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function list(Request $request)
    {
        $members = Member::paginate(10);

        return $members;
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|string|date_format:Y-m-d',
            'address' => 'nullable|string',
            'phone_number' => 'nullable|string|phone:mobile|unique:members',
            'personal_id_number' => 'nullable|string|max:255|unique:members',
            'picture' => 'nullable|string|max:255'
        ]);

        $member = Member::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'address' => $validated['address'],
            'phone_number' => $validated['phone_number'],
            'personal_id_number' => $validated['personal_id_number'],
            'picture' => $validated['picture']
        ]);

        return $member;
    }
}
