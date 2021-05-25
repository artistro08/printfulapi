<?php namespace Artistro08\PrintfulAPI\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreatePrintfulsTable extends Migration
{
    public function up()
    {
        Schema::table('offline_mall_products', function (Blueprint $table) {
            $table->string('printful_product_id');
            $table->text('print_files')->nullable();
        });
        Schema::table('offline_mall_product_variants', function (Blueprint $table) {
            $table->string('printful_variant_id');
            $table->string('printful_variant_printfile');
            $table->string('printful_variant_option_id')->nullable();
            $table->string('printful_variant_option_value')->nullable();
        });
    }

    public function down()
    {
        // product drops
        if (Schema::hasColumn('offline_mall_products', 'printful_product_id')) {
            Schema::table('offline_mall_products', function ($table) {
                $table->dropColumn('printful_product_id');
            });
        }
        if (Schema::hasColumn('offline_mall_products', 'print_files')) {
            Schema::table('offline_mall_products', function ($table) {
                $table->dropColumn('print_files');
            });
        }

        // Product Variant drops
        if (Schema::hasColumn('offline_mall_product_variants', 'printful_variant_id')) {
            Schema::table('offline_mall_product_variants', function ($table) {
                $table->dropColumn('printful_variant_id');
            });
        }
        if (Schema::hasColumn('offline_mall_product_variants', 'printful_variant_printfile')) {
            Schema::table('offline_mall_product_variants', function ($table) {
                $table->dropColumn('printful_variant_printfile');
            });
        }
        if (Schema::hasColumn('offline_mall_product_variants', 'printful_variant_option_id')) {
            Schema::table('offline_mall_product_variants', function ($table) {
                $table->dropColumn('printful_variant_option_id');
            });
        }
        if (Schema::hasColumn('offline_mall_product_variants', 'printful_variant_option_value')) {
            Schema::table('offline_mall_product_variants', function ($table) {
                $table->dropColumn('printful_variant_option_value');
            });
        }
    }
}
