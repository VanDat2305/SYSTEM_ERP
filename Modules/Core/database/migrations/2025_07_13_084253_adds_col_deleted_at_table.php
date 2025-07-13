<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('user_two_factor_codes', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('team_user', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('teams', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('customers', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('customer_contacts', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('customer_representatives', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('service_packages', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('service_package_features', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
            Schema::table('payments', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });

        } catch (\Exception $e) {
            // In ra lỗi hoặc xử lý theo nhu cầu của bạn
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('user_two_factor_codes', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('team_user', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('teams', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('customers', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('customer_contacts', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('customer_representatives', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('service_packages', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('service_package_features', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
            Schema::table('payments', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });

        } catch (\Exception $e) {
            // In ra lỗi hoặc xử lý theo nhu cầu của bạn
            throw $e;
        }
    }
};
