# OctoberCMS Printful API Wrapper for OFFLINE/Mall

This plugin adds [Printful](https://www.printful.com/docs) Sync capabilities to the [OFFLINE\Mall](https://github.com/OFFLINE-GmbH/oc-mall-plugin) plugin.

It adds a new tab in the product backend called "Printful". There, you can pick your product and specify your print files. Once saved, each variant you have in your product will generate items to pick for each variant (Printful Variant, Print File, Option ID, and Option Value). customize these to your needs

To use this plugin, you'll need to specify the `PRINTFUL_API_KEY` Found in your store in the `.env` file of your application. You can also specify if you would like to confirm the orders as soon as they are made. You'll also need to specify the currency code as well. All examples below:

```dotenv
PRINTFUL_API_KEY=someapiKey
# Confirm orders immediately in printful (1 for yes, 0 for no)
PRINTFUL_CONFIRM_ORDERS=0
# Currency code, should be the one you set in the OFFLINE.mall backend.
PRINTFUL_CURRENCY_CODE=USD
```

To sync products to your store, run the command `php aritsan printfulapi:syncproducts` in your application's directory via command line. Alternatively, you can specify cron to run this command for you to keep your products up to date. The plugin automatically syncs the products on save as well.

## Other Notes
This is a one way sync to Printful. It only syncs variants if the variant is selected in the backend.

If you would like to contribute to the code, I'd love the help! Please submit a pull request, and I'll merge if the code is right.
