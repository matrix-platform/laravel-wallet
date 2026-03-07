<?php //>

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up() {
        Schema::create('base_currency', function ($table) {
            $table->primaryKey();
            $table->text('title');
            $table->text('code')->unique();
            $table->text('symbol')->nullable();
            $table->text('icon')->nullable();
            $table->integer('precision');
            $table->schedules();
            $table->ranking();
        });

        Schema::create('base_wallet', function ($table) {
            $table->primaryKey();
            $table->integer('member_id');
            $table->integer('currency_id');
            $table->double('frozen');
            $table->double('balance');
            $table->timestamp('modify_time');
        });

        Schema::create('base_wallet_log', function ($table) {
            $table->primaryKey();
            $table->integer('wallet_id');
            $table->date('the_date');
            $table->text('type');
            $table->double('amount');
            $table->double('balance');
            $table->text('remark')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamp('create_time');
        });

        Schema::create('base_frozen_log', function ($table) {
            $table->primaryKey();
            $table->integer('wallet_id');
            $table->date('the_date');
            $table->text('type');
            $table->double('amount');
            $table->text('remark')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamp('create_time');
        });
    }

    public function down() {
        Schema::dropIfExists('base_frozen_log');
        Schema::dropIfExists('base_wallet_log');
        Schema::dropIfExists('base_wallet');
        Schema::dropIfExists('base_currency');
    }

};
