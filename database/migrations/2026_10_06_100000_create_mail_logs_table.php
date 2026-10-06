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
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('available_exams')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_email');
            $table->string('mail_type'); // general or customized
            $table->string('template_name')->nullable();
            $table->string('subject', 500);
            $table->longText('content'); // Personalized body as it was sent
            $table->json('attachment_names')->nullable();
            $table->string('status')->index(); // sent or failed
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('resent_from_id')->nullable(); // Failed log this attempt retried
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
        Schema::dropIfExists('mail_logs');
    }
};
