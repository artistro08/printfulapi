<?php

namespace Artistro08\PrintfulAPI\Console;

use Exception;
use Illuminate\Console\Command;
use OFFLINE\Mall\Models\Product;
use Printful\Exceptions\PrintfulApiException;
use Printful\Exceptions\PrintfulException;
use Printful\Exceptions\PrintfulSdkException;
use Printful\PrintfulApiClient;
use Printful\PrintfulProducts;
use Printful\Structures\Sync\SyncProductCreationParameters;
use Printful\Structures\Sync\SyncProductUpdateParameters;

class SyncProducts extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'printfulapi:syncproducts';
    /**
     * @var string The console command description.
     */
    protected $description = 'Create products using the Printful E-Commerce Sync API';
    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        echo "\n\e[32mSyncing Products...\n" . PHP_EOL;
        $apiKey = env('PRINTFUL_API_KEY', '');
        try {
            // create ApiClient
            $pf = new PrintfulApiClient($apiKey);



            // create Products Api object
            $productsApi = new PrintfulProducts($pf);
            $products = Product::get();

            foreach ($products as $product) {

                // create a fresh variant container for each product
                $modify_variants[];
                $create_variants[]


                // Continue if we have a printful product id for the product
                if(empty($product->printful_product_id)){
                    continue;
                }

                foreach ($product->variants as $variant) {

                    $placements = $variant->printful_variant_placements;
                    $filePlacements = [];


                    if(is_array($placements) || is_object($placements)) {
                        foreach ($placements as $placement) {
                            $filePlacements[] = [
                                'type' => $placement['printful_variant_placement'],
                                'url' => $placement['printful_variant_printfile'],
                                'options' => [
                                    'id' => $placement['printful_variant_option_id'],
                                    'value' => $placement['printful_variant_option_value']
                                ],
                            ];
                        }

                    }



                    // Continue if the variants have printful product ids
                    if(!empty($variant->printful_variant_id)) {
                        $printFiles = $variant->printful_variant_printfile;



                        $price = str_replace('$', '', $variant->price['USD']);


                        $modify_variants[] = [
                            'id'           => '@'.$variant->id,
                            'retail_price' => $price,                         // set retail price that this item is sold for (optional)
                            'variant_id'   => $variant->printful_variant_id, // set variant in from Printful Catalog(https://www.printful.com/docs/catalog)
                            'files'        => $filePlacements,
                            'options' => [
                                [
                                    'id'  => $variant->printful_variant_option_id,
                                    'value' => $variant->printful_variant_option_value,
                                ]
                            ]
                        ];
                        $create_variants[] = [
                            'external_id'  => $variant->id,
                            'retail_price' => $price,                         // set retail price that this item is sold for (optional)
                            'variant_id'   => $variant->printful_variant_id, // set variant in from Printful Catalog(https://www.printful.com/docs/catalog)
                            'files'        => $filePlacements,
                            'options' => [
                                [
                                    'id'  => $variant->printful_variant_option_id,
                                    'value' => $variant->printful_variant_option_value,
                                ]
                            ]
                        ];
                    }
                }

                // get the image if it exists, otherwise, return empty
                if(!$product->image->getPath() == null) {
                    $image = $product->image->getPath();
                } else {
                    $image = '';
                }

                $updateParams = SyncProductUpdateParameters::fromArray([
                    'sync_product'  => [
                        'external_id' => $product->id,   // set id in my store for this product (optional)
                        'name'        => $product->name,
                        'thumbnail'   => $image,         // set thumbnail url
                    ],
                    'sync_variants' => $sync_variants
                ]);

                $printfulProduct = $productsApi->updateProduct('@'.$product->id, $updateParams);
            }
        } catch (PrintfulApiException $e) { // API response status code was not successful

            if($e->getCode() == '404') {

                // if product doesn't exist, create
                $creationParams  = SyncProductCreationParameters::fromArray([
                    'sync_product'  => [
                        'external_id' => $product->id,     // set id in my store for this product (optional)
                        'name'        => $product->name,
                        'thumbnail'   => $image,           // set thumbnail url
                    ],
                    'sync_variants' => $create_variants
                ]);

                $printfulProduct = $productsApi->createProduct($creationParams);
            }
            else {
                // shit out of luck..
                $this->error($e->getCode().' error encountered.');
                throw new Exception($e->getMessage());
            }


        } catch (PrintfulSdkException $e) { // SDK did not call API
            echo 'Printful SDK Exception: ' . $e->getMessage() . PHP_EOL;
        } catch (PrintfulException $e) { // API call failed
            echo 'Printful Exception: ' . $e->getMessage() . PHP_EOL;
            var_export($pf->getLastResponseRaw()) . PHP_EOL;
        }

        echo "\e[32mProducts Synced successfully\n" . PHP_EOL;


    }
    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
