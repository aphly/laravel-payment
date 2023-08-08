<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment', function (Blueprint $table){
            $table->unsignedBigInteger('id')->primary();
            $table->integer('method_id');
            $table->string('method_name',32);
            $table->string('transaction_id',128)->nullable();
            $table->string('cred_id',128)->nullable();
            $table->string('notify_func',255);
            $table->string('success_url',255);
            $table->string('fail_url',255);
            $table->string('cancel_url',255);
            $table->tinyInteger('status')->nullable()->default(0);
            $table->string('notify_type')->nullable();
            $table->decimal('amount',15,2);
            //$table->decimal('fee',15,2)->nullable();
            $table->char('currency_code',3)->nullable()->default('USD');
            $table->unsignedBigInteger('created_at');
            $table->unsignedBigInteger('updated_at');
            $table->index(['method_id','transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment');
    }
};
