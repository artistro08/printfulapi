<?php namespace Artistro08\PrintfulAPI\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddPrintfulVariantPlacements extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_product_variants', function (Blueprint $table) {
            $table->json('printful_variant_placements');
            $table->json('printful_variant_placement');
        });
    }

    public function down()
    {
        // variant drops
        if (Schema::hasColumn('offline_mall_product_variants', 'printful_variant_placements')) {
            Schema::table('offline_mall_product_variants', function ($table) {
                $table->dropColumn('printful_variant_placements');
            });
        }
        if (Schema::hasColumn('offline_mall_product_variants', 'printful_variant_placement')) {
            Schema::table('offline_mall_product_variants', function ($table) {
                $table->dropColumn('printful_variant_placement');
            });
        }

    }
}
