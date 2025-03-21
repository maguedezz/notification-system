<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Notifications\UserProfileUpdatedNotification;

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
}
