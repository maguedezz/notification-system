<?php

namespace App\Http\Controllers;

use App\Notifications\PasswordChangedNotification;
use App\Notifications\UserProfileUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    //

    public function updateProfile(Request $request)
    {

        $user = JWTAuth::user(); // Get the authenticated user

        // Validate the request data

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|string|max:255|unique:users,email,' . $user->id,
        ]);

        // Track changes

        $changes = [];

        if ($request->has('name') && $request->name != $user->name) {
            $changes[] = 'Name changed';
            $user->name = $request->name;
        }

        if($request->has('email') && $request->email != $user->email) {
            $chages[] = 'Email changed';
            $user->email = $request->email;
        }

        if (empty($changes)) {
            return response()->json(['message' => 'No changes detected'], 200);
        }

        $user->save();

        // Send notification

        $user->notify(new UserProfileUpdatedNotification($user));

        return response()->json(['message' => 'Profile updated successfully', 'changes' => $changes], 200);
    }

    public function changePassword(Request $request)
    {
        $user = JWTAuth::user(); // Get the authenticated user

        // Validates input.
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);
        // Verifies current password is correct.
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
      }
        // Hashes and saves the new password.

        $user->password = Hash::make($request->new_password);
        $user->save();

        $user->notify(new PasswordChangedNotification($user));

        // Responds with success or failure.
        return response()->json(['message' => 'Password changed successfully'], 200);
    }
}