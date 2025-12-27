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
        Schema::create("reviews", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid("booking_id")->constrained()->cascadeOnDelete();
            $table
                ->foreignUuid("accommodation_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table
                ->foreignUuid("activity_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignUuid("user_id")->constrained()->cascadeOnDelete();
            $table->integer("rating")->comment("1-5 stars");
            $table->string("title")->nullable();
            $table->text("comment")->nullable();
            $table
                ->enum("status", ["pending", "approved", "rejected"])
                ->default("pending");
            $table->timestamps();
            $table->softDeletes();

            $table->index(["accommodation_id", "status"]);
            $table->index(["activity_id", "status"]);
            $table->index(["user_id", "status"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("reviews");
    }
};
