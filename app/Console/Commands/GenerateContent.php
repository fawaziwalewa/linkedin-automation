<?php

namespace App\Console\Commands;

use App\Models\Topic;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GenerateContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'topics:generate-content';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate content for topics and create posts based on the approved topics using OpenAI Assistants API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating content for topics...');
        $topics = Topic::where('status', 'Approved')->take(5)->get();

        foreach ($topics as $topic) {
            $content = $this->generate($topic->topic, $topic->preferred_framework);
            $post = new \App\Models\Post();
            $post->topic = $topic->topic;
            $post->content = $content['content'] ?? '';
            $post->framework = $content['framework'] ?? $topic->preferred_framework;
            if ($content) {
                $post->save();
                $topic->status = 'Generated';
                $topic->save();
                $this->info("Content generated for topic: {$topic->topic}");
            } else {
                \Log::error("Failed to generate content for topic: {$topic->topic}");
            }
        }
        $this->info('Content generation completed...');
    }

    public function generate($topic, $framework)
    {
        if(empty($framework)){
            $frameworks = [
                'What? What So? What Now?',
                'Issue–Impact–Resolution',
                'Problem–Agitate–Solution',
                'Situation–Impact–Action'
            ];

            $framework = $frameworks[array_rand($frameworks)];
        }

        $assistantId = $this->getAssistantId($framework);
        $prompt = $this->getPrompt($framework, $topic);

        try {
            // Step 1: Create Thread
            $threadRes = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'OpenAI-Beta' => 'assistants=v2',
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/threads');

            if (!$threadRes->successful()) {
                return null;
            }

            $threadId = $threadRes->json('id');

            // Step 2: Add Message
            $msgRes = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'OpenAI-Beta' => 'assistants=v2',
                'Content-Type' => 'application/json',
            ])->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
                'role' => 'user',
                'content' => $prompt,
            ]);

            if (!$msgRes->successful()) {
                \Log::error("Failed to add message to thread: " . $msgRes->body());
                return null;
            }

            // Step 3: Run the Assistant
            $runRes = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'OpenAI-Beta' => 'assistants=v2',
                'Content-Type' => 'application/json',
            ])->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
                'assistant_id' => $assistantId,
            ]);

            if (!$runRes->successful()) {
                return null;
            }

            $runId = $runRes->json('id');

            // Step 4: Poll for Completion
            do {
                sleep(1);
                $statusRes = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                    'OpenAI-Beta' => 'assistants=v2',
                ])->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");

                $status = $statusRes->json('status');
            } while ($status !== 'completed' && $status !== 'failed');

            if ($status !== 'completed') {
                return null;
            }

            // Step 5: Fetch Message
            $messagesRes = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'OpenAI-Beta' => 'assistants=v2',
            ])->get("https://api.openai.com/v1/threads/{$threadId}/messages");

            $messages = $messagesRes->json('data');

            foreach ($messages as $message) {
                if ($message['role'] === 'assistant') {
                    return [
                        'content' => $message['content'][0]['text']['value'] ?? null,
                        'framework' => $framework,
                    ];
                }
            }

            return null;

        } catch (\Throwable $e) {
            $this->error("Exception: " . $e->getMessage());
            return null;
        }
    }

    protected function getAssistantId($framework)
    {
        $assistantId = match ($framework) {
            'Issue–Impact–Resolution' => 'asst_W4xiyZNDDdNOD6TMwKZGLyta', // LinkedIn IIR (Issue–Impact–Resolution) Writer
            'Problem–Agitate–Solution' => 'asst_Sx3EtL0YIH8NaoeODt30MN0M', // LinkedIn PAS (Problem–Agitate–Solution) Writer
            'Situation–Impact–Action' => 'asst_YRA0CZlh25KeqZzoCVtNt3D8', // LinkedIn SIA (Situation–Impact–Action) Writer
            default => 'asst_OG1uBiGsZHJq95HjR15vRbS3', // Default to LinkedIn WWW (What–So What–Now What) Writer if no match
        };

        return $assistantId;
    }

    protected function getPrompt($framework, $topic)
    {
        $prompt = match ($framework) {
            'Issue–Impact–Resolution' => <<<PROMPT
            Write a short, scroll-stopping LinkedIn post based on the Issue–Impact–Resolution framework, but don’t make it sound like a template.

            Keep the tone thoughtful, honest, and very “me”—no fluff or forced lessons. Word count must stay under 150 words. Start like a conversation or a journal entry. Use emojis like ⚠️, 🧩, or 💭 to express emotion when it fits naturally.
            Let the post flow like a moment of vulnerability or clarity, ending with a line that opens the floor:
            “What would you have done in that situation?” or “Would love to hear your take. 💬”

            Use this topic: {$topic}
            PROMPT,
            'Problem–Agitate–Solution' => <<<PROMPT
            Write a compelling LinkedIn post that follows the Problem–Agitate–Solution style without mentioning the structure. The tone should sound like me—casual, real, slightly personal, and sometimes witty.

            Limit it to 150 words max. Begin with a relatable or emotional hook—a short sentence, a bold truth, or a rhetorical question. Don’t be afraid to use emojis like 😩, 🧠, or 🤯 when it adds energy. Highlight the struggle, then share the insight or action that turned things around.

            Wrap the post with a soft prompt or reflection like:
            “Have you ever felt this way?” or “Curious to hear your experience 👇🏽”

            The topic is: {$topic}
            PROMPT,
            'Situation–Impact–Action' => <<<PROMPT
            Craft a real, story-driven LinkedIn post inspired by the Situation–Impact–Action format—but no structure language, no “situation/impact/action” labels.

            Let the post feel like a genuine behind-the-scenes moment from my journey. Stay within 150 words. Open with a hook or specific moment. Add light emojis like 👨🏽‍💻, 🔄, 😅, or 🛠️ if they help bring it to life.

            It should feel like something I’d tell over coffee, not like an article. End it with a human touch:
            “That moment stuck with me.” or “Funny how the smallest things teach the biggest lessons.”

            Here’s the situation to write from: {$topic}
            PROMPT,
            default => <<<PROMPT
            Write a deeply reflective LinkedIn post using the principles of What–So What–Now What, but don’t signal the framework at all. It should feel natural, thoughtful, and true to my voice.

            Stay under 150 words. Begin with what happened (no labels). Use gentle tone and emojis like 🤔, 🧘🏽, ✨, or ❤️‍🔥 only where they genuinely add to the mood. Share the insight or the shift in thinking that moment created.

            Close it with warmth and curiosity. Something like:
            “Ever experienced something similar?” or “That changed how I see things. 🌱”

            Reflecting on: {$topic}
            PROMPT,
        };

        return $prompt;
    }
}
