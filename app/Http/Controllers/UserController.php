<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{

    public function activeUser(Request $request)
    {
        return $request->user();
    }

    public function list(Request $request)
    {
        $users = User::paginate(10);

        return $users;
    }

    public function detail(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            throw ValidationException::withMessages(['id' => 'User not found']);
        }

        return $user;
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return $user;
    }



    public function update(Request $request, string $id)
    {

        $user = User::find($id);

        if (!$user) {
            throw ValidationException::withMessages(['id' => 'User not found']);
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
        ]);

        if ($request->email != $user->email) {
            throw ValidationException::withMessages(['email' => 'Wrong user email']);
        }

        $user->name = $request->name;
        if ($request->password != '') {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return $user;
    }

    public function delete(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            throw ValidationException::withMessages(['id' => 'User not found']);
        }

        $user->delete();

        return response()->json([
            'message' => 'Success deleting user'
        ]);
    }
}
