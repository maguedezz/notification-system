<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\UserRegisteredNotification;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed',
            'password_confirmation' => 'required|string'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        // **Send the notification** 🚀
        $user->notify(new UserRegisteredNotification($user));

        $token = Auth::guard('api')->login($user);


        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        // get the currently authenticated user

        $user = auth()->user();

        // create a token for the user

        $token = auth()->attempt($request->only('email', 'password'));

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => $user
        ], 201);
    }

    public function logout()
    {
        auth()->logout(); // invalidate the token

        return response()->json([
            'message' => 'User logged out successfully',
        ], 201);
    }

    public function refresh()
    {
        return response()->json([
            'access_token' => auth()->refresh(),
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function userProfile()
    {
        return response()->json(auth()->user());
    }
}
