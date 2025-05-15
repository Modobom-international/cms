<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\Boards;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any existing roles to either admin or member
        DB::table('board_users')
            ->whereNotIn('role', [Boards::ROLE_ADMIN, Boards::ROLE_MEMBER])
            ->update(['role' => Boards::ROLE_MEMBER]);

        // Then modify the column to only accept these two values
        Schema::table('board_users', function (Blueprint $table) {
            $table->enum('role', [Boards::ROLE_ADMIN, Boards::ROLE_MEMBER])
                ->default(Boards::ROLE_MEMBER)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the column to accept any string value
        Schema::table('board_users', function (Blueprint $table) {
            $table->string('role')->change();
        });
    }
};