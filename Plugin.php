<?php namespace Artistro08\PrintfulAPI;


use DB;
use October\Rain\Exception\ValidationException;
use Illuminate\Support\Facades\Event;
use OFFLINE\Mall\Models\Product as ProductModel;
use Offline\Mall\Controllers\Products as ProductsController;
use OFFLINE\Mall\Models\Variant as VariantModel;
use System\Classes\PluginBase;
use Printful\PrintfulApiClient;
use System\Models\File;
use Illuminate\Support\Facades\Artisan;
use Cache;


/**
 * PrintfulAPI Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['OFFLINE.Mall'];
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
            'icon'        => 'icon-refresh'
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
            $pfProducts = Cache::get('printful_products');

            if(!$pfProducts) {
                // no cache found, fetch new
                $pfProducts = $pf->get('/products');

                // cache for 1hr
                Cache::put('printful_products',$pfProducts,2592000);
            }

            foreach ($pfProducts as $pfProduct) {

                $printfulProducts[] = [
                    'name' => ltrim($pfProduct['brand'] . " " . $pfProduct['model']),
                    'id'   => $pfProduct['id'],
                ];
            }
            array_unshift($printfulProducts, ['name' => 'None', 'id' => '']);
            return $printfulProducts;
        }

        function getProductVariants()
        {

            // Get the current product. It's pretty hacky, but it works
            $url = url()->current();
            $currentProduct = preg_replace('/\D/', '', $url);
            $printfulProductID = ProductModel::where('id', $currentProduct)->get()[0]->printful_product_id;


            $apiKey = env('PRINTFUL_API_KEY', '');

            // create ApiClient
            $pf = new PrintfulApiClient($apiKey);

            // declare array
            $printfulVariants = [];

            // load variants only if printful product id is set
            if (!empty($printfulProductID)) {
                // attempt to load from cache
                $pfVariants = Cache::get('printful_variants');

                if(!Cache::has('printful_variants')) {
                    // no cache found, fetch new
                    $pfVariants = $pf->get('/products' . '/' . $printfulProductID);

                    // cache for 30 days
                    Cache::put('printful_variants',$pfVariants,2592000);
                }

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

                $pfVariantOptions = Cache::get('printful_variant_options');


                if(!Cache::has('printful_variant_options')) {

                    // no cache found, fetch new
                    $pfVariantOptions = $pf->get('/products' . '/' . $printfulProductID);


                    // cache for 30 days
                    Cache::put('printful_variant_options',$pfVariantOptions,2592000);

                }


                foreach ($pfVariantOptions['product']['files'] as $pfVariantOption) {

                    $printfulVariantOptions[] = [
                        'id'   => $pfVariantOption['type'],
                        'name' => $pfVariantOption['title'],
                    ];
                }
            }
            return $printfulVariantOptions;
        }

        // extend the products controller to include our js
        ProductsController::extend(function ($controller) {
            $controller->addJs('/plugins/artistro08/printfulapi/assets/js/app.js');
        });


        // extend the product model for multiple file uploads and jsonable properties
        // also clear the cache if the product variant is updated.
        ProductModel::extend(function($model) {
            $model->attachMany['print_files'] = File::class;
            $model->bindEvent('model.afterSave', function() use ($model) {
                if(!empty($model->getOriginal())) {
                    if($model->printful_product_id !== $model->getOriginal()['printful_product_id']) {
                        Cache::forget('printful_variants');
                        Cache::forget('printful_variant_options');
                        DB::table('offline_mall_product_variants')
                            ->where('product_id', $model->product_id)
                            ->update(['printful_variant_placements' => []]);
                    }
                }

                // Sync product after save if the the printful product ID is set. This command catches if the variants aren't sent.
                $saveAttribute = env('PRINTFUL_SYNC_ON_SAVE', 1);
                $syncOnSave = intval($saveAttribute);
                if(!empty($model->printful_product_id)){
                    if($syncOnSave == 1)
                        Artisan::queue('printful:sync');
                }
            });
        });

        // extend the variant model for jsonable properties
        VariantModel::extend(function($model) {
            $model->addJsonable('printful_variant_placements');
            $model->addJsonable('printful_variant_placement');
            $model->rules = [
                'printful_variant_placements.*.printful_variant_placement' => "required",
                'printful_variant_placements' => 'required_with:printful_variant_id'
            ];

            // Throw error if the print placement field isn't set.
            $model->bindEvent('model.afterValidate', function () use ($model) {
                foreach ($model->errors()->all() as $error) {
                    if(str_contains($error, 'printful_variant_placements.')){
                        throw new ValidationException([
                            'printful_variant_placements' => 'Please make sure each Variant Placement item has a Print Placement set and none are the same.'
                        ]);
                    } elseif (str_contains($error, 'The Variant Placements field is required when Printful Variant is present.')) {
                        throw new ValidationException([
                            'printful_variant_placements' => 'Please add a Variant Placement or set the Printful Variant field to none.'
                        ]);
                    }
                }
            });
        });

        ProductsController::extendFormFields(function($form, $model){

            if(!$model instanceof ProductModel)
                return;

            if(!$form->isNested) {
                $form->addTabFields([
                    'printful_product_id' => [
                        'label'       => 'Printful Product',
                        'tab'         => 'Printful',
                        'type'        => 'dropdown',
                        'options'     =>  array_pluck(getProductCatalog(),'name','id'),
                        'span'        => 'left',
                        'comment'     => 'Set the global product type. <br><br> WARNING, changing this will reset all variants',
                        'commentHtml' => true,

                    ],
                    'print_files' => [
                        'label'        => 'Print Files',
                        'tab'          => 'Printful',
                        'type'         => 'fileupload',
                        'fileTypes'    => 'png,jpg,pdf,ai,eps,tiff',
                        'commentAbove' => 'Set the global print files. JPG, PNG, PDF, and AI are supported',
                        'span'         => 'right'
                    ],
                ]);
            }
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

            if(!$form->isNested) {
                $form->addTabFields([
                    'printful_variant_id' => [
                        'label'   => 'Printful Variant',
                        'tab'     => 'Printful Variant',
                        'type'    => 'dropdown',
                        'options' => array_pluck(getProductVariants(),'name','id'),
                        'span'    => 'full',
                        'default' => '',
                    ],
                    'printful_variant_placements' => [
                        'label'        => 'Print Areas',
                        'tab'          => 'Printful Variant',
                        'type'         => 'repeater',
                        'cssClass'     => 'variantRepeater',
                        'style'        => 'default',
                        'minItems'     => 1,
                        'commentAbove' => 'The areas where you would like to print. Print placement areas cannot be the same.',
                        'maxItems'     => count(getProductVariantOptions()),
                        'form'         => [
                            'fields' => [
                                'printful_variant_placement' => [
                                    'label'   => 'Print Placement',
                                    'tab'     => 'Printful Variant',
                                    'type'    => 'dropdown',
                                    'trigger' => [
                                        'action'    => 'disable',
                                        'field'     => '^^printful_variant_id',
                                        'condition' => 'value[]'
                                    ],
                                    'options'  => array_pluck(getProductVariantOptions(),  'name', 'id'),
                                    'span'     => 'left',
                                    'cssClass' => 'variantPlacementOptions',
                                    'default'  => array_pluck(getProductVariantOptions(), 'id')[0],
                                    'required' => true,
                                ],
                                'printful_variant_printfile' => [
                                    'label'   => 'Print File',
                                    'tab'     => 'Printful Variant',
                                    'type'    => 'dropdown',
                                    'trigger' => [
                                        'action'    => 'disable',
                                        'field'     => '^^printful_variant_id',
                                        'condition' => 'value[]'
                                    ],
                                    'options' => array_pluck(getPrintFiles(),'name','url'),
                                    'span'    => 'right'
                                ],
                                'printful_variant_option_id' => [
                                    'label'       => 'Option ID',
                                    'commentHtml' => true,
                                    'comment'     => 'An optional ID parameter for advanced users. <a href="https://www.printful.com/docs/products" target="_blank">See Docs</a>',
                                    'tab'         => 'Printful Variant',
                                    'type'        => 'text',
                                    'trigger' => [
                                        'action'    => 'disable',
                                        'field'     => '^^printful_variant_id',
                                        'condition' => 'value[]'
                                    ],
                                    'span'        => 'left'
                                ],
                                'printful_variant_option_value' => [
                                    'label'     => 'Option Value',
                                    'tab'       => 'Printful Variant',
                                    'comment'   => 'An optional value for advanced users.',
                                    'type'      => 'text',
                                    'trigger' => [
                                        'action'    => 'disable',
                                        'field'     => '^^printful_variant_id',
                                        'condition' => 'value[]'
                                    ],
                                    'span'      => 'right'
                                ],
                            ]
                        ],
                    ],
                ]);
            }
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

        Event::listen('mall.order.afterCreate', function ($event, $order) {

            // Collect the information needed for the order
            $apiKey = env('PRINTFUL_API_KEY', '');
            $pf = new PrintfulApiClient($apiKey);
            $customer = $order->customer;
            $order_id = $event->id;
            // assemble the items array
            // create an empty array to store the items
            $order_items = [];
            foreach ($order->products as $product) {

                if(empty($product->product->printful_product_id)){
                    continue;
                }

                if(empty($product->variant->printful_variant_id)){
                    continue;
                }

                $placements = $product->variant->printful_variant_placements;


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

                // append to the array
                $order_items[] = [
                    'external_id'  => $product->variant->id,
                    'variant_id'   => $product->variant->printful_variant_id,
                    'name'         => $product->product->name, // Display name
                    'retail_price' => $product->price[env('PRINTFUL_CURRENCY_CODE', 'USD')], // Retail price for packing slip
                    'quantity'     => $product->product->quantity,
                    'files' => $filePlacements,
                ];
            }

            $recipient = [
                'name'         => $customer->firstname . ' ' . $customer->lastname,
                'address1'     => $customer->shipping_address->lines,
                'city'         => $customer->shipping_address->city,
                'state_code'   => $customer->shipping_address->state_code,
                'country_code' => $customer->shipping_address->country_code,
                'zip'          => $customer->shipping_address->zip,
            ];

            $orderFinal = [
                'external_id' => $order_id,
                'recipient'   => $recipient,
                'items'       => $order_items,
            ];

            if(empty($order_items))
                return;


            // Send order request to the job queue
            dispatch(function() use ($orderFinal, $pf) {
                $pf->post('orders', $orderFinal);
            });
        });

        Event::listen('mall.checkout.succeeded', function ($order) {

            $confirm = env('PRINTFUL_CONFIRM_ORDERS', 0);
            $confirmOrder = intval($confirm);
            $apiKey = env('PRINTFUL_API_KEY', '');
            $pf = new PrintfulApiClient($apiKey);
            $order_id = $order->order->id;

            // Send order confirm request to the job queue
            dispatch(function() use ($confirmOrder, $pf, $order_id) {
                if($confirmOrder == 1) {
                    $pf->post('orders/@' . $order_id . '/confirm' );
                }
            });
        });

        // Remove cache for variant options on page load.
        Event::listen('backend.page.beforeDisplay', function ($backendController, $action, $params) {
            if(class_basename($backendController) == 'Products') {
                if($action == 'update') {
                    Cache::forget('printful_variants');
                }
            }
        });
    }
}
