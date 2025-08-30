<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('available_exams')->onDelete('cascade');
            $table->string('name'); // Template name for identification
            $table->enum('type', ['general', 'customized']); // Mail type
            $table->string('subject');
            $table->text('content');
            $table->json('assigned_courses')->nullable(); // For customized mails - stores course IDs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_templates');
    }
};
