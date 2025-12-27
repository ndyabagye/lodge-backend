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
        Schema::create("blocked_dates", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table
                ->foreignUuid("accommodation_id")
                ->constrained()
                ->cascadeOnDelete();
            $table->date("start_date");
            $table->date("end_date");
            $table
                ->enum("reason", ["maintenance", "private_booking", "other"])
                ->default("other");
            $table->text("notes")->nullable();
            $table->timestamps();

            $table->index(["accommodation_id", "start_date", "end_date"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("blocked_dates");
    }
};
