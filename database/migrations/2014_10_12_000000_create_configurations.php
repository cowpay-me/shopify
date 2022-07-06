<?php use Illuminate\Support\Facades\Schema; use Illuminate\Database\Schema\Blueprint; use Illuminate\Database\Migrations\Migration; class CreateConfigurations 
extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('configurations', function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('store_id')->default(0);
                    $table->string('meta_key')->nullable();
                    $table->string('meta_value')->nullable();
                    $table->timestamps();
                });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('configurations');
    }
}
