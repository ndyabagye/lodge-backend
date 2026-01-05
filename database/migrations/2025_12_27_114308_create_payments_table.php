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
        Schema::create("payments", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->foreignUuid("booking_id")->constrained()->cascadeOnDelete();
            $table->string("transaction_id")->unique()->nullable();
            $table->decimal("amount", 10, 2);
            $table->string("currency", 3)->default("ZMW");
            $table->string("payment_method")->nullable();
            $table->string("payment_gateway")->nullable();
            $table
                ->enum("status", ["pending", "completed", "failed", "refunded"])
                ->default("pending");
            $table->json("metadata")->nullable();
            $table->timestamps();

            $table->index(["booking_id", "status"]);
            $table->index("transaction_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("payments");
    }
};
