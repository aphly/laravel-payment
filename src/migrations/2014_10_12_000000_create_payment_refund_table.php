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
        Schema::create('payment_refund', function (Blueprint $table){
            $table->id();
            $table->char('payment_id',32)->index();
            $table->decimal('amount',15,2);
            $table->string('amount_format',255);
            $table->string('reason',255)->nullable();
            $table->string('cred_id',255)->nullable();
            $table->string('cred_status',255)->nullable();
            $table->tinyInteger('status')->nullable()->default(1);
            $table->unsignedBigInteger('created_at');
            $table->unsignedBigInteger('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_refund');
    }
};
