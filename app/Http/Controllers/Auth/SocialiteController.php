<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User; // Import User model
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\Hash; // Import Hash facade
use Illuminate\Support\Str; // Import Str facade
use Laravel\Socialite\Facades\Socialite; // Import Socialite facade

class SocialiteController extends Controller
{
    /**
     * Handle the callback from Google authentication.
     */
    public function handleGoogleCallback()
    {
        try {
            // Retrieve user details from Google
            $googleUser = Socialite::driver('google')->user();

            // 1. Check if a user already exists with this Google ID
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                // 2. User found - Log them in
                Auth::login($user, true); // 'true' creates a remember token
                return redirect()->intended('/dashboard'); // Redirect to dashboard or intended page
            }

            // 3. No user with this Google ID, check if email exists
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                // 4. Email exists, but not linked to Google ID - Update the user
                $user->update(['google_id' => $googleUser->getId()]);
                Auth::login($user, true);
                return redirect()->intended('/dashboard');
            }

            // 5. User doesn't exist - Create a new user
            $newUser = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                // Generate a random, unusable password for safety if needed later
                'password' => Hash::make(Str::random(24)),
                // Mark email as verified since it came from Google
                'email_verified_at' => now(),
            ]);

            Auth::login($newUser, true);
            return redirect()->intended('/dashboard');

        } catch (\Exception $e) {
            // Log the error for debugging
            logger()->error('Google Login Error: ' . $e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile());

            // Redirect back to login page with a generic error
            // In production, you might want a more user-friendly error page
            return redirect('/login')->with('error', 'Unable to login using Google. Please try again.');
        }
    }
}