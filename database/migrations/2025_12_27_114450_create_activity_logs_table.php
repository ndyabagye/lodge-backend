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
        Schema::create("activity_logs", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table
                ->foreignUuid("user_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string("action");
            $table->string("model_type")->nullable();
            $table->uuid("model_id")->nullable();
            $table->text("description")->nullable();
            $table->json("changes")->nullable();
            $table->string("ip_address", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->timestamps();

            $table->index(["model_type", "model_id"]);
            $table->index(["user_id", "created_at"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("activity_logs");
    }
};
