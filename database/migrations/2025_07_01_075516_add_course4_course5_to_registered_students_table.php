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
        Schema::table('registered_students', function (Blueprint $table) {
            $table->integer('course4')->nullable();
            $table->integer('course5')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('registered_students', function (Blueprint $table) {
            $table->dropColumn(['course4', 'course5']);
        });
    }
};
