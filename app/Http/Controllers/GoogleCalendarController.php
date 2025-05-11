<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect; // Use facade for redirects
use Google\Client as GoogleClient; // Use alias for Google Client
use Google\Service\Calendar; // Use alias for Calendar service
use Carbon\Carbon;

class GoogleCalendarController extends Controller
{
    protected $client;

    /**
     * Constructor to set up Google Client
     */
    public function __construct()
    {
        $client = new GoogleClient();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        // Use the *new* callback URL for calendar auth
        $client->setRedirectUri(route('google.calendar.callback'));
        $client->setScopes([
            Calendar::CALENDAR_READONLY, // Allows reading events
            Calendar::CALENDAR_EVENTS    // Allows creating/editing/deleting events
        ]);
        // Allows getting a refresh token Rrequired for long-term offline access
        $client->setAccessType('offline');
        // Forces consent screen every time - good for dev, remove for prod?
        $client->setApprovalPrompt('force');
        $this->client = $client;
    }

    /**
     * Redirect user to Google to grant calendar permissions.
     */
    public function connect()
    {
        // Generate the URL to Google's OAuth 2.0 server
        $authUrl = $this->client->createAuthUrl();
        return Redirect::to($authUrl);
    }

    /**
     * Handle callback from Google after user grants/denies permission.
     * Store the obtained tokens.
     */
    public function store(Request $request)
    {
        // Check if user denied permission or if there's an error
        if ($request->has('error')) {
            Log::error('Google Calendar connection error: ' . $request->input('error'));
            return redirect()->route('profile.edit') // Redirect to profile/settings
                ->with('error', 'Failed to connect Google Calendar: ' . $request->input('error'));
        }

        // Get the authorization code from the request query string
        $code = $request->input('code');
        if (empty($code)) {
             Log::error('Google Calendar callback missing authorization code.');
            return redirect()->route('profile.edit')
                ->with('error', 'Failed to connect Google Calendar: Authorization code missing.');
        }

        try {
            // Exchange authorization code for an access token and refresh token
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);

            // Check if fetchAccessTokenWithAuthCode returned an error
            if (isset($accessToken['error'])) {
                 Log::error('Google Calendar token fetch error: ' . $accessToken['error_description'] ?? $accessToken['error']);
                return redirect()->route('profile.edit')
                    ->with('error', 'Failed to get access token: ' . ($accessToken['error_description'] ?? $accessToken['error']));
            }

            // Get the authenticated user
            $user = Auth::user();

            // Update user's tokens (encryption happens via $casts in User model)
            $user->google_access_token = $accessToken['access_token'];

            // Refresh token is only provided on the *first* authorization.
            // Store it if we get it, otherwise keep the existing one if any.
            if (isset($accessToken['refresh_token'])) {
                $user->google_refresh_token = $accessToken['refresh_token'];
            }

            // Calculate expiry time
            $user->google_token_expires_at = Carbon::now()->addSeconds($accessToken['expires_in'] - 60); // Subtract buffer

            $user->save();

             return redirect()->route('profile.edit') // Redirect back to profile/settings
                ->with('success', 'Google Calendar connected successfully!');

        } catch (\Exception $e) {
            Log::error('Google Calendar token exchange exception: ' . $e->getMessage());
            return redirect()->route('profile.edit')
                ->with('error', 'An exception occurred while connecting Google Calendar: ' . $e->getMessage());
        }
    }

    /**
     * Optional: Disconnect Google Calendar by removing tokens.
     */
    public function destroy()
    {
        $user = Auth::user();
        // Optionally, try to revoke the token with Google API first
        // $this->client->setAccessToken($user->google_access_token); // Requires valid access token
        // $this->client->revokeToken(); // This might fail if token expired etc.

        // Clear stored tokens
        $user->google_access_token = null;
        $user->google_refresh_token = null;
        $user->google_token_expires_at = null;
        $user->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Google Calendar disconnected.');
    }
}