<?php use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint; use Illuminate\Database\Migrations\Migration; class CreatePaymentsTable 
extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('order_id')->unique();
            $table->string('email')->nullable();
            $table->string('txid')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('customer_id')->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->integer('processed')->nullable();
            $table->text('order_status_url')->nullable();
            $table->integer('store_id')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('payments');
    }
}
