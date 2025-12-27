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
        Schema::create("activities", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->string("slug")->unique();
            $table->string("category");
            $table->text("description");
            $table->string("short_description", 200);
            $table
                ->integer("duration")
                ->nullable()
                ->comment("Duration in minutes");
            $table->decimal("price", 10, 2)->nullable();
            $table->decimal("adult_price", 10, 2)->nullable();
            $table->decimal("child_price", 10, 2)->nullable();
            $table->decimal("group_price", 10, 2)->nullable();
            $table->integer("max_participants")->nullable();
            $table->integer("min_age")->nullable();
            $table->integer("max_age")->nullable();
            $table->text("requirements")->nullable();
            $table->text("safety_info")->nullable();
            $table->text("included")->nullable();
            $table->text("excluded")->nullable();
            $table
                ->enum("status", ["available", "unavailable", "coming_soon"])
                ->default("available");
            $table->boolean("featured")->default(false);
            $table->decimal("rating", 3, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(["status", "featured"]);
            $table->index("category");
            $table->fullText(["name", "short_description", "description"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("activities");
    }
};
