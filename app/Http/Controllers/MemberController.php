<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use chillerlan\QRCode\{QRCode, QROptions};
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function list(Request $request)
    {
        $members = Member::paginate(10);

        return $members;
    }

    public function detail(Request $request, string $id)
    {
        $member = Member::find($id);

        if (!$member) {
            throw ValidationException::withMessages(['id' => 'Member not found']);
        }

        return $member;
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

        $qrcode = $this->generateQRCode($member);

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

    public function delete(Request $request, string $id)
    {
        $member = Member::find($id);

        if (!$member) {
            throw ValidationException::withMessages(['id' => 'Member not found']);
        }

        $member->delete();

        return response()->json([
            'message' => 'Success deleting member'
        ]);
    }

    public function regenerateQRCode(Request $request, string $id)
    {
        $member = Member::find($id);

        if (!$member) {
            throw ValidationException::withMessages(['id' => 'Member not found']);
        }

        $qrcode = $this->generateQRCode($member);

        return response()->json([
            'qr_code' => $member->qr_code
        ]);
    }

    private function generateQRCode(Member $member)
    {
        $qrCodeString = config('app.name') . ':' . $member->id;
        $qrcode = (new QRCode)->render($qrCodeString);

        $image = str_replace('data:image/svg+xml;base64,', '', $qrcode);

        $qrCodeFileName = $member->getQRCodeFileName();
        Storage::disk('public')->put($qrCodeFileName, base64_decode($image));

        return $qrCodeFileName;
    }
}
