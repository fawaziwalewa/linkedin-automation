<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class HumanizePostContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:humanize-post-content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Use a fine-tuned GPT model to humanize AI-generated post content and update humanized_content column.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $posts = Post::whereNull('humanized_content')->whereNotNull('content')->where('status', 'Pending')->take(5)->get();

        if ($posts->isEmpty()) {
            $this->info("✅ No posts to humanize.");
            return;
        }

        foreach ($posts as $post) {
            $this->info("🔄 Humanizing post: {$post->id}");

            $humanized = $this->humanizeWithFineTune($post->content);

            if ($humanized) {
                $post->humanized_content = $humanized;
                $post->status = 'Humanized'; // Update status to Humanized
                $post->save();
                $this->info("✅ Humanized content saved for post ID {$post->id}");
            } else {
                $this->error("❌ Failed to humanize content for post ID {$post->id}");
            }
        }
    }

    public function humanizeWithFineTune(string $content): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'ft:gpt-4.1-2025-04-14:personal::BesvVycG',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => <<<PROMPT
                        You are a rewriting assistant.

                        You receive AI-generated LinkedIn content and reverse-engineer the tell-tale patterns used in AI-generated content to make it sound more human, authentic, and natural — with no summary, explanation, or headings. Avoid "inspirational" tone. Your reply should **only include the rewritten content**, nothing else.

                        Avoid poetic conclusions. Let the tone be thoughtful but raw. Break the flow. Leave some thoughts unfinished.

                        Return just the rewritten post with emojis. No markdown. No labels. No commentary.
                        PROMPT
                    ],
                    ['role' => 'user', 'content' => $content],
                ],
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                \Log::error("OpenAI error: " . $response->body());
                return null;
            }

            return $response->json('choices.0.message.content');
        } catch (\Throwable $e) {
            \Log::error("Exception during humanization: " . $e->getMessage());
            return null;
        }
    }
}
