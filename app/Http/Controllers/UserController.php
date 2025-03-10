<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function changePassword()
    {
        return view('users.change_password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password'             => 'required|string|min:8|confirmed',
        ]);

        // Find the record first
        $user = User::find(Auth::user()->id);
        $user->password = Hash::make($request->password);
        $user->update();
        if ($user) {
            return redirect()->back()->with('success', 'Password updated successfully!');
        }

        return redirect()->back()->with('error', 'Something went wrong!');
    }
}
