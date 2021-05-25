# OctoberCMS Printful API Wrapper for OFFLINE/Mall

This plugin adds [Printful](https://www.printful.com/docs) Sync capabilities to the [OFFLINE\Mall](https://github.com/OFFLINE-GmbH/oc-mall-plugin) plugin.

It adds a new tab in the product backend called "Printful". There, you can pick your product and specify your print files. Once saved, each variant you have in your product will generate items to pick for each variant (Printful Variant, Print File, Option ID, and Option Value). Customize these to your needs


## Getting started
To use this plugin, you'll need the following:

 - OctoberCMS 2.0.*
 - you will need to set up your queue works in your application. You can learn how to do that via [OctoberCMS Documentation](https://octobercms.com/docs/setup/installation#queue-setup).
 - specify the `PRINTFUL_API_KEY` Found in your store in the `.env` file of your application.
 - specify if you would like to confirm the orders as soon as they are made under `PRINTFUL_CONFIRM_ORDERS` (boolean 1 = true, 0 = false)
 - specify your currency code under `PRINTFUL_CURRENCY_CODE`
 - Specify if you want to sync your products on save `PRINTFUL_SYNC_ON_SAVE` (boolean 1 = true, 0 = false)

 Env examples below:

```dotenv
PRINTFUL_API_KEY=someapiKey
# Confirm orders immediately in Printful (1 for yes, 0 for no. Defaults to 0)
PRINTFUL_CONFIRM_ORDERS=0
# Currency code, should be the one you set in the OFFLINE.mall backend.
PRINTFUL_CURRENCY_CODE=USD
# Sync Products on save to Printful. (1 for yes, 0 for no. Defaults to 1)
PRINTFUL_SYNC_ON_SAVE=1
```

To sync products to your store, run the command `php aritsan printful:sync` in your application's directory via command line. The products sync on save in the background if the queue workers are setup

### Sync on save
This option will sync ALL products in your system on save. Only useful if you have already run the command `php artisan printful:sync`. If you would like to change this, Please submit a pull request!

### Other Notes
This is a one way sync to Printful. It only syncs variants if the variant is selected in the backend and creates orders on the fly. If you would like to contribute to the code, I'd love the help! Please submit a pull request.


### License
MIT License

Copyright (c) 2021 Devin Green

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.