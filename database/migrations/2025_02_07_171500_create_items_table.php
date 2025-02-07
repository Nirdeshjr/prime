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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_id')->constrained()->onDelete('cascade');
            $table->foreignId('sender_id')->constrained()->onDelete('cascade');
            $table->string('item');
            $table->string('hs_code')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_rate', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps(0);
        });
        
        
    }
    
    public function down()
    {
        Schema::dropIfExists('items');
    }
    
};
