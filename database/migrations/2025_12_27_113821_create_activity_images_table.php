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
        Schema::create("activity_images", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table
                ->foreignUuid("activity_id")
                ->constrained()
                ->onDelete("cascade");
            $table->string("url");
            $table->string("thumbnail_url")->nullable();
            $table->string("alt_text")->nullable();
            $table->string("caption")->nullable();
            $table->integer("order")->default(0);
            $table->boolean("is_featured")->default(false);
            $table->timestamps();

            $table->index(["activity_id", "order"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("activity_images");
    }
};
