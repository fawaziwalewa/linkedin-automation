<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LinkedInController;
use Symfony\Component\HttpFoundation\JsonResponse;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/linkedin/auth', [LinkedInController::class, 'redirectToLinkedIn'])->name('linkedin.auth');
Route::get('/linkedin/callback', [LinkedInController::class, 'handleLinkedInCallback'])->name('linkedin.callback');

/* Route::get('openai-assistant', function () {
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'OpenAI-Beta' => 'assistants=v2',
    ])->get('https://api.openai.com/v1/assistants', [
                'order' => 'desc',
                'limit' => 20,
            ]);

    $assistants = $response->json('data');
   return new JsonResponse($assistants);
}); */


/* Route::get('models', function () {
     $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        'OpenAI-Beta' => 'assistants=v2',
    ])->get('https://api.openai.com/v1/models', [
        'order' => 'desc',
        'limit' => 20,
    ]);
    $models = $response->json('data');
    return new JsonResponse($models);
});
 */
