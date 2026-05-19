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
            $table->auditings();
        });

        Schema::create('base_wallet', function ($table) {
            $table->primaryKey();
            $table->integer('member_id');
            $table->integer('currency_id');
            $table->decimal('frozen', 28, 8);
            $table->decimal('balance', 28, 8);
            $table->auditings();

            $table->unique(['member_id', 'currency_id']);
        });

        Schema::create('base_wallet_log', function ($table) {
            $table->primaryKey();
            $table->integer('wallet_id');
            $table->date('the_date');
            $table->text('type'); // wallet-log-type
            $table->decimal('amount', 28, 8);
            $table->decimal('balance', 28, 8);
            $table->text('remark')->nullable();
            $table->jsonb('data')->nullable();
            $table->auditings(false);

            $table->index('wallet_id');
        });

        Schema::create('base_frozen_log', function ($table) {
            $table->primaryKey();
            $table->integer('wallet_id');
            $table->date('the_date');
            $table->text('type'); // frozen-log-type
            $table->decimal('amount', 28, 8);
            $table->text('remark')->nullable();
            $table->jsonb('data')->nullable();
            $table->auditings(false);

            $table->index('wallet_id');
        });
    }

    public function down() {
        Schema::dropIfExists('base_frozen_log');
        Schema::dropIfExists('base_wallet_log');
        Schema::dropIfExists('base_wallet');
        Schema::dropIfExists('base_currency');
    }

};
