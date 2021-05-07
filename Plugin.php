<?php namespace Artistro08\PrintfulAPI;


use Illuminate\Support\Facades\Event;
use OFFLINE\Mall\Models\Product as ProductModel;
use Offline\Mall\Controllers\Products as ProductsController;
use OFFLINE\Mall\Models\Variant as VariantModel;
use System\Classes\PluginBase;
use Printful\PrintfulApiClient;
use System\Models\File;


/**
 * PrintfulAPI Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['Offline.Mall'];
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'PrintfulAPI',
            'description' => 'Plugin to use API calls to make orders',
            'author'      => 'artistro08',
            'icon'        => 'icon-leaf'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('artistro08.syncproducts', 'Artistro08\PrintfulAPI\Console\SyncProducts');
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */






    public function boot()
    {


        function getProductCatalog() {
            $apiKey = env('PRINTFUL_API_KEY', '');

            // create ApiClient
            $pf = new PrintfulApiClient($apiKey);

            $printfulProducts = [];

            // attempt to load from cache
            $pfProducts = cache()->get('printful_products');

            if(!$pfProducts) {
                // no cache found, fetch new
                $pfProducts = $pf->get('/products');

                // cache for 1hr
                cache()->put('printful_products',$pfProducts,2592000);
            }

            foreach ($pfProducts as $pfProduct) {


                $printfulProducts[] = [
                    'name' => ltrim($pfProduct['brand'] . " " . $pfProduct['model']),
                    'id' => $pfProduct['id'],
                ];
            }
            array_unshift($printfulProducts, ['name' => 'None', 'id' => '']);
            return $printfulProducts;
        }

        function getProductVariants() {

            // Get the current product. It's pretty hacky, but it works
            $url = url()->current();
            $currentProduct    = preg_replace('/\D/', '', $url);
            $printfulProductID = ProductModel::where('id', $currentProduct)->get()[0]->printful_product_id;


            $apiKey = env('PRINTFUL_API_KEY', '');

            // create ApiClient
            $pf = new PrintfulApiClient($apiKey);

            // declare array
            $printfulVariants = [];

            // load variants only if printful product id is set
            if(!empty($printfulProductID)) {
                $pfVariants = $pf->get('/products' . '/' . $printfulProductID);

                foreach ($pfVariants['variants'] as $pfVariant) {

                    $printfulVariants[] = [
                        'name' => $pfVariant['name'],
                        'id'   => $pfVariant['id'],
                    ];
                }
                array_unshift($printfulVariants, ['name' => 'None', 'id' => '']);
                return $printfulVariants;
            }
        }

        function getProductVariantOptions() {

            // Get the current product. It's pretty hacky, but it works
            $url = url()->current();
            $currentProduct    = preg_replace('/\D/', '', $url);
            $printfulProductID = ProductModel::where('id', $currentProduct)->get()[0]->printful_product_id;


            $apiKey = env('PRINTFUL_API_KEY', '');

            // create ApiClient
            $pf = new PrintfulApiClient($apiKey);

            // declare array
            $printfulVariantOptions = [];

            // load variant options only if printful product id is set
            if(!empty($printfulProductID)) {
                $pfVariantOptions = $pf->get('/products' . '/' . $printfulProductID);


                foreach ($pfVariantOptions['product']['files'] as $pfVariantOption) {


                    $printfulVariantOptions[] = [
                        'name' => $pfVariantOption['title'],
                        'type'   => $pfVariantOption['type'],
                    ];
                }

                return $printfulVariantOptions;
            }
        }

        function getPrintFiles() {

            // Get the current product. It's pretty hacky, but it works
            $url = url()->current();
            $currentProductFromUrl = preg_replace('/\D/', '', $url);
            $currentProducts = ProductModel::where('id', $currentProductFromUrl)->get();

            // Declare print files array
            $printFiles = [];

            // collect the print files for the variant
            foreach ($currentProducts as $currentProduct) {
                $getFiles = File::where('field', 'print_files')->where('attachment_id', $currentProduct->id)->get();

                foreach($getFiles as $file) {
                    $printFiles[] = [
                        'name' => $file->file_name,
                        'url'  => $file['path'],
                    ];
                }
            }
            return $printFiles;
        }

        // extend the product model for multiple file uploads
        ProductModel::extend(function($model) {
            $model->attachMany['print_files'] = File::class;
        });

        ProductsController::extendFormFields(function($form, $model){

            if(!$model instanceof ProductModel)
                return;

            $form->addTabFields([
                'printful_product_id' => [
                    'label'   => 'Printful Product',
                    'tab'     => 'Printful',
                    'type'    => 'dropdown',
                    'options' =>  array_pluck(getProductCatalog(),'name','id'),
                    'span'    => 'left'

                ],
                'print_files' => [
                    'label' => 'Print Files',
                    'tab'   => 'Printful',
                    'type'  => 'fileupload',
                    'span'  => 'right'

                ],
            ]);
        });

        // Add columns to product backend to quickly see if product is set
        ProductsController::extendListColumns(function($list, $model) {
            if(!$model instanceof ProductModel)
                return;

            $list->addColumns([
                'printful_product_id' => [
                    'label' => 'Printful Product ID'
                ]
            ]);
        });

        ProductsController::extendFormFields(function ($form, $model) {

            if (!$model instanceof VariantModel)
                return;

            if (getProductVariants() == null)
                return;

            $form->addTabFields([
                'printful_variant_id' => [
                    'label'   => 'Printful Variant',
                    'tab'     => 'Printful Variant',
                    'type'    => 'dropdown',
                    'options' => array_pluck(getProductVariants(),'name','id'),
                    'span'    => 'left'

                ],
                'printful_variant_printfile' => [
                    'label'   => 'Print Files',
                    'tab'     => 'Printful Variant',
                    'type'    => 'dropdown',
                    'options' => array_pluck(getPrintFiles(),'name','url'),
                    'span'    => 'right'

                ],
                'printful_variant_option_id' => [
                    'label' => 'Option ID',
                    'tab'   => 'Printful Variant',
                    'type'  => 'text',
                    'span'  => 'left'
                ],
                'printful_variant_option_value' => [
                    'label' => 'Option Value',
                    'tab'   => 'Printful Variant',
                    'type'  => 'text',
                    'span'  => 'left'
                ],
            ]);
        });

        // Add columns to variant backend to quickly see if variant is set
        ProductsController::extendListColumns(function($list, $model) {
            if(!$model instanceof VariantModel)
                return;

            $list->addColumns([
                'printful_variant_id' => [
                    'label' => 'Printful Variant ID'
                ]
            ]);
        });

        Event::listen('mall.order.beforeCreate', function ($event) {
            $confirm = env('PRINTFUL_CONFIRM_ORDERS', 0);
            $confirmOrder = intval($confirm);
            $apiKey = env('PRINTFUL_API_KEY', '');
            $pf = new PrintfulApiClient($apiKey);
            $customer = $event->customer;
            // assemble the items array
            // create an empty array to store the items
            $order_items = [];
            foreach ($event->products as $product) {

                if(empty($product->product->printful_product_id)){
                    continue;
                }

                if(empty($product->variant->printful_variant_id)){
                    continue;
                }

                // append to the array
                $order_items[] = [
                    'variant_id'   => $product->variant->printful_variant_id,
                    'name'         => $product->product->name, // Display name
                    'retail_price' => $product->price['USD'], // Retail price for packing slip
                    'quantity'     => $product->product->quantity,
                    'files' => [
                        [
                            'url' => $product->variant->printful_variant_printfile,
                        ],
                    ],
                ];
            }

            if(empty($order_items))
                return;
            $pf->post('orders',
                [
                    'recipient' => [
                        'name'         => $customer->firstname . ' ' . $customer->lastname,
                        'address1'     => $customer->shipping_address->lines,
                        'city'         => $customer->shipping_address->city,
                        'state_code'   => $customer->shipping_address->state_code,
                        'country_code' => $customer->shipping_address->country_code,
                        'zip'          => $customer->shipping_address->zip,
                    ],
                    'items'    => $order_items,
                ],
                ['confirm' => $confirmOrder]
            );
        });
    }

}
