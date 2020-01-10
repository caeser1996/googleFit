<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSocialiteFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('id');
            $table->string('provider_id')->nullable()->after('provider_name');
            $table->string('token')->nullable()->after('provider_name');
            $table->string('access_token')->nullable()->after('token');
            $table->string('refresh_token')->nullable()->after('access_token');
            $table->string('expires_in')->nullable()->after('refresh_token');
            $table->string('password')->nullable()->change();
            $table->string('avatar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
