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
        Schema::create("amenities", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("name")->unique();
            $table->string("icon")->nullable();
            $table->string("category")->nullable();
            $table->integer("order")->default(0);
            $table->boolean("active")->default(true);
            $table->timestamps();

            $table->index(["active", "order"]);
        });

        Schema::create("accommodation_amenity", function (Blueprint $table) {
            $table
                ->foreignUuid("accommodation_id")
                ->constrained()
                ->onDelete("cascade");
            $table
                ->foreignUuid("amenity_id")
                ->constrained()
                ->onDelete("cascade");

            $table->primary(["accommodation_id", "amenity_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("accommodation_amenity");
        Schema::dropIfExists("amenities");
    }
};
