<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {

        // Validate email input
        $request->validate(['email' => 'required|email']);

        // Log email
        Log::info('Forgot Password request for: ' . $request->email);

        // Send password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Return response based on status
        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Password reset link sent!'])
            : response()->json(['message' => 'Failed to send reset link!'], 400);
    }

    public function resetPassword(Request $request)
    {
        // Validate the request data
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);
    
        // Attempt to reset the password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                // Update the user's password
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );
    
        // Check the response status
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully!']);
        } else {
            return response()->json(['message' => __($status)], 400);
        }
    }
}
