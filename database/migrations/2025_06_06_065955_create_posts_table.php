<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->text('content')->nullable();
            $table->text('humanized_content')->nullable();
            $table->string('image')->nullable();
            $table->enum('framework', [
                'What? What So? What Now?',
                'Issue–Impact–Resolution',
                'Problem–Agitate–Solution',
                'Situation–Impact–Action'
            ])->nullable();
            $table->enum('status', ['Approved', 'Declined', 'Humanized', 'Pending', 'Posted'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
