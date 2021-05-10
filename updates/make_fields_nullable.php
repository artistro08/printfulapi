<?php namespace Artistro08\PrintfulAPI\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class MakeFieldsNullable extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_product_variants', function (Blueprint $table) {
            $table->string('printful_variant_id')->nullable()->change();
            $table->string('printful_variant_printfile')->nullable()->change();
            $table->json('printful_variant_placements')->nullable()->change();
            $table->json('printful_variant_placement')->nullable()->change();
        });
    }

    public function down()
    {

    }
}
