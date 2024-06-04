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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->text('identifier')->index()->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('hospital_id');
            $table->datetime('start_date')->index();
            $table->decimal('no_of_points', 15, 2)->default(0);
            $table->datetime('end_date')->index()->nullable();
            $table->string('purpose')->nullable();
            $table->string('doctor_name')->nullable();
            $table->text('details')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('hospital_id')->references('id')->on('hospitals')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('SET NULL');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};