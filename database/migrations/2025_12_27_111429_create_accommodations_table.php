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
        Schema::create("accommodations", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("name");
            $table->string("slug")->unique();
            $table->string("type");
            $table->text("description");
            $table->string("short_description", 200);
            $table->integer("max_guests");
            $table->integer("num_bedrooms");
            $table->integer("num_bathrooms");
            $table->integer("num_beds");
            $table->integer("size_sqft")->nullable();
            $table->decimal("base_price", 10, 2);
            $table->decimal("weekend_price", 10, 2);
            $table->decimal("cleaning_fee", 10, 2)->default(0);
            $table->integer("minimum_stay")->default(1);
            $table->integer("maximum_stay")->nullable();
            $table->time("check_in_time")->default("14:00:00");
            $table->time("check_out_time")->default("11:00:00");
            $table
                ->enum("status", [
                    "available",
                    "maintenance",
                    "coming_soon",
                    "archived",
                ])
                ->default("available");
            $table->boolean("featured")->default(false);
            $table->decimal("rating", 3, 2)->default(0);
            $table->integer("views")->default(0);
            $table->integer("bookings")->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(["status", "featured"]);
            $table->index("type");
            $table->fullText(["name", "short_description", "description"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("accommodations");
    }
};
