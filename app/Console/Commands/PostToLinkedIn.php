<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PostToLinkedIn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-to-linkedin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish posts to LinkedIn';

    protected ?string $accessToken;
    protected ?string $authorUrn;

    public function __construct()
    {
        parent::__construct();

        $config = json_decode(Storage::disk('local')->get('linkedin.json'), true);

        $this->accessToken = $config['access_token'] ?? null;
        $this->authorUrn = $config['person_urn'] ?? null;
    }

    /**
     * Execute the console command.
     */

    public function handle()
    {
        if (!$this->accessToken || !$this->authorUrn) {
            $this->error("❌ Missing LinkedIn access token or person URN.");
            return;
        }

        $posts = \App\Models\Post::whereNotNull('humanized_content')
            ->where('status', 'Approved')
            ->take(1)
            ->get();

        foreach ($posts as $post) {
            $this->info("📤 Posting Post ID {$post->id} to LinkedIn...");

            $asset = null;

            if (!empty($post->image) && Storage::disk('public')->exists($post->image)) {
                $asset = $this->uploadImageToLinkedIn($post->image);
            } else {
                $this->warn("⚠️ No valid image found for Post ID {$post->id}. Posting as text-only.");
            }

            $success = $this->publishToLinkedIn($post->humanized_content, $asset);

            if ($success) {
                $post->status = 'Posted';
                $post->save();
                $this->info("✅ Successfully posted Post ID {$post->id}");
            } else {
                $this->error("❌ Failed to publish Post ID {$post->id}");
            }
        }
    }

    public function uploadImageToLinkedIn(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            \Log::warning("⚠️ Skipping image upload: Image path is empty.");
            return null;
        }

        if (!Storage::disk('public')->exists($imagePath)) {
            \Log::warning("⚠️ Skipping image upload: File does not exist at path: $imagePath");
            return null;
        }

        // Step 1: Register image upload
        $register = Http::withToken($this->accessToken)->post("https://api.linkedin.com/v2/assets?action=registerUpload", [
            'registerUploadRequest' => [
                'owner' => $this->authorUrn,
                'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                'serviceRelationships' => [
                    [
                        'identifier' => 'urn:li:userGeneratedContent',
                        'relationshipType' => 'OWNER'
                    ]
                ],
            ],
        ]);

        if (!$register->successful()) {
            \Log::error("LinkedIn register upload failed: " . $register->body());
            return null;
        }

        $uploadUrl = $register->json('value.uploadMechanism')['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
        $assetUrn = $register->json('value.asset');

        // Step 2: Upload image content
        $imageFile = Storage::disk('public')->get($imagePath);
        $upload = Http::withHeaders([
            'Authorization' => "Bearer {$this->accessToken}",
            'Content-Type' => 'image/jpeg',
        ])->withBody($imageFile, 'image/jpeg')->put($uploadUrl);

        if (!$upload->successful()) {
            \Log::error("Image upload failed: " . $upload->body());
            return null;
        }

        return $assetUrn;
    }


    public function publishToLinkedIn(string $text, ?string $assetUrn): bool
    {
        $payload = [
            'author' => $this->authorUrn,
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => ['text' => $text],
                    'shareMediaCategory' => $assetUrn ? 'IMAGE' : 'NONE',
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        if ($assetUrn) {
            $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                [
                    'status' => 'READY',
                    'description' => ['text' => ''],
                    'media' => $assetUrn,
                    'title' => ['text' => ''],
                ]
            ];
        }

        $response = Http::withToken($this->accessToken)
            ->post("https://api.linkedin.com/v2/ugcPosts", $payload);

        if (!$response->successful()) {
            \Log::error("LinkedIn post failed: " . $response->body());
        }

        return $response->successful();
    }
}
