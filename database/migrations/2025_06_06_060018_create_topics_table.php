<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
           // Status: Approved, Declined, Ready for review
            $table->enum('status', ['Approved', 'Declined', 'Ready for review', 'Generated'])->default('Ready for review');
            // Preferred framework: What? What So? What Now?, Issue–Impact–Resolution, Problem–Agitate–Solution, Situation–Impact–Action
            $table->enum('preferred_framework', [
                'What? What So? What Now?',
                'Issue–Impact–Resolution',
                'Problem–Agitate–Solution',
                'Situation–Impact–Action'
            ])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
