<?php

namespace App\Http\Controllers;

use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LinkedInController extends Controller
{
    public function redirectToLinkedIn()
    {
        $clientId = env('LINKEDIN_CLIENT_ID');
        $redirectUri = env('LINKEDIN_REDIRECT_URI');
        $scopes = 'openid profile email w_member_social'; // Adjust scopes as needed
        $state = csrf_token();

        $authUrl = "https://www.linkedin.com/oauth/v2/authorization"
            . "?response_type=code"
            . "&client_id={$clientId}"
            . "&redirect_uri={$redirectUri}"
            . "&scope={$scopes}"
            . "&state={$state}";

        return redirect($authUrl);
    }

    public function handleLinkedInCallback(Request $request)
    {
        if ($request->has('error')) {
            return response()->json([
                'error' => $request->query('error'),
                'description' => $request->query('error_description'),
            ]);
        }

        $state = $request->query('state');
        if ($state !== csrf_token()) {
            return response()->json(['error' => 'Invalid state parameter']);
        }

        $code = $request->query('code');
        if (!$code) {
            return response()->json(['error' => 'Authorization code missing']);
        }

        // Exchange authorization code for access token
        $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
            'client_id' => env('LINKEDIN_CLIENT_ID'),
            'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Failed to obtain access token',
                'details' => $response->json(),
            ]);
        }

        $accessToken = $response->json('access_token');

        // Retrieve user's LinkedIn profile
        $profile = Http::withToken($accessToken)
            ->get('https://api.linkedin.com/v2/userinfo');

        if (!$profile->successful()) {
            return response()->json([
                'error' => 'Failed to fetch user profile',
                'details' => $profile->json(),
            ]);
        }

        $personId = $profile->json('sub');
        $personUrn = "urn:li:person:{$personId}";

        // Store access token and person URN securely
        Storage::disk('local')->put('linkedin.json', json_encode([
            'access_token' => $accessToken,
            'person_urn' => $personUrn,
        ]));

        Notification::make()
            ->title('LinkedIn Connected')
            ->body('Your LinkedIn account has been successfully connected.')
            ->success()
            ->send();

        return redirect()->route('filament.admin.pages.dashboard');
    }
}
