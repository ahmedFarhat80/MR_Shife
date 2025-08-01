<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('preferred_language');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->index('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropIndex(['last_login_at']);
            $table->dropColumn(['last_login_at', 'last_login_ip']);
        });
    }
};
