<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('set null');
            $table->enum('transaction_type', ['buy', 'sell']);
            $table->float('amount');
            $table->float('usdt_amount');
            $table->float('jod_amount');
            $table->float('exchange_rate');
            $table->enum('payment_method', [
                'credit_card',
                'bank_transfer',
                'voda_cash',
                'payments_jo',
                'paypal',
                'cashu',
                'wallet',
                'other'
            ]);
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->enum('status', ['pending', 'completed', 'cancelled', 'disputed'])->default('pending');
            $table->string('reference_code')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
