<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function update(Request $request, string $id)
    {
        $member = Member::find($id);

        if (!$member) {
            throw ValidationException::withMessages(['id' => 'Member not found']);
        }

        $validated = $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|string|date_format:Y-m-d',
            'address' => 'nullable|string',
            'phone_number' => [
                'nullable',
                'string',
                'phone:mobile',
                Rule::unique('members')->ignore($member->id)
            ],
            'personal_id_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('members')->ignore($member->id)
            ],
            'picture' => 'nullable|string|max:255'
        ]);


        $member->first_name = $validated['first_name'];
        $member->last_name = $validated['last_name'];
        $member->birth_place = $validated['birth_place'];
        $member->birth_date = $validated['birth_date'];
        $member->address = $validated['address'];
        $member->phone_number = $validated['phone_number'];
        $member->personal_id_number = $validated['personal_id_number'];
        $member->picture = $validated['picture'];
        $member->save();

        return $member;
    }
}
