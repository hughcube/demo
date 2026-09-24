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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('type')->default(0)->index();
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
            $table->string('appid')->default('');
            $table->string('openid')->default('');
            $table->string('sub_openid')->default('');
            $table->string('unionid')->default('');
            $table->string('sub_unionid')->default('');
            $table->string('access_secret', 64)->default('');
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('data_version')->default(0);

            $table->index(['appid', 'openid']);
            $table->index(['openid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['appid', 'openid']);
            $table->dropIndex(['openid']);
            $table->dropColumn([
                'type',
                'appid',
                'openid',
                'sub_openid',
                'unionid',
                'sub_unionid',
                'access_secret',
                'last_login_at',
                'data_version',
            ]);
        });
    }
};
