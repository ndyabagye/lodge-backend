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
        Schema::create("bookings", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->string("booking_number")->unique();
            $table
                ->foreignUuid("user_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table
                ->foreignUuid("accommodation_id")
                ->constrained()
                ->cascadeOnDelete();
            $table->date("check_in_date");
            $table->date("check_out_date");
            $table->integer("num_guests");
            $table->integer("num_adults")->default(1);
            $table->integer("num_children")->default(0);
            $table->decimal("subtotal", 10, 2);
            $table->decimal("tax_amount", 10, 2)->default(0);
            $table->decimal("service_fee", 10, 2)->default(0);
            $table->decimal("cleaning_fee", 10, 2)->default(0);
            $table->decimal("discount", 10, 2)->default(0);
            $table->decimal("total_amount", 10, 2);
            $table
                ->enum("payment_status", [
                    "pending",
                    "paid",
                    "partially_paid",
                    "refunded",
                    "failed",
                ])
                ->default("pending");
            $table->string("payment_method")->nullable();
            $table
                ->enum("status", [
                    "pending",
                    "confirmed",
                    "checked_in",
                    "checked_out",
                    "cancelled",
                ])
                ->default("pending");
            $table->string("guest_first_name")->nullable();
            $table->string("guest_last_name")->nullable();
            $table->string("guest_email")->nullable();
            $table->string("guest_phone")->nullable();
            $table->text("special_requests")->nullable();
            $table->text("internal_notes")->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index([
                "accommodation_id",
                "check_in_date",
                "check_out_date",
            ]);
            $table->index(["user_id", "status"]);
            $table->index(["status", "payment_status"]);
            $table->index("booking_number");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("bookings");
    }
};
