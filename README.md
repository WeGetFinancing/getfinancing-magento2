General Instructions
-----------------------------
1. Create your merchant account to offer monthly payment options to your consumers directly on your ecommerce from here (http://www.getfinancing.com/signup) if you haven't done it yet.

Install using Composer (recommended)
----------------------------

1. Run these commands in your root Magento installation directory (this extension is registered on Packagist):

    ```
    composer require getfinancing/getfinancing:dev-master
    bin/magento module:enable --clear-static-content Getfinancing_Getfinancing
    bin/magento setup:upgrade
    bin/magento cache:flush
    ```

Install by copying files
----------------------------

1. Create an `app/code/Getfinancing/Getfinancing` directory in your Magento installation.
2. Download the latest "Source code" from this page: [https://github.com/GetFinancing/getfinancing-magento2/archive/master.zip](https://github.com/GetFinancing/getfinancing-magento2/archive/master.zip)
3. Extract the file and copy the contents of the file into the `app/code/Getfinancing/Getfinancing` directory.
4. Run following commands from your root Magento installation directory:

    ```
    bin/magento module:enable --clear-static-content Getfinancing_Getfinancing
    bin/magento setup:upgrade
    bin/magento cache:flush
    ```

Configure module
---------------------

1. Setup the module with the information found under the Integration section on your portal account https://partner.getfinancing.com/partner/portal/. Also remember to change the postback url on your account for both testing and production environments.
2. Once the module is working properly and the lightbox opens on the request, we suggest you to add some conversion tools to your store so your users know before the payment page that they can pay monthly for the purchases at your site. You can find these copy&paste tools under your account inside the Integration section.
3. Check our documentation (www.getfinancing.com/docs) or send us an email at (integrations@getfinancing.com) if you have any doubt or suggestions for this module. You can also send pull requests to our GitHub account (http://www.github.com/GetFinancing) if you feel you made an improvement to this module.

Activating the module
---------------------
 - Go to the admin backoffice
 - At the top, go to System > Configuration
 - On the left, go to Sales > Payment Methods
 - If Getfinancing does not show up:
   execute the terminal commands.
 - On the left, go to Stores > Configuration > Sales > Payment Methods
 - Under GetFinancing:
   - Set Enabled to YES
   - Fill in Merchant ID
   - Fill in username
   - Fill in password


Testing
-------

In the complete integration guide that you can download from our portal,
you can see various test personae that you can use for testing.

Switching to production
-----------------------

 - Go to the admin backoffice
 - At the top, go to  Stores > Configuration > Sales > Payment Methods
 - Under 'GetFinancing', fill in the production URL, and switch the Platform
   dropdown from staging to production.

Note that after this change, you should no longer use the test personae you
used for testing, and all requests go to our production platform.

Module notes
------------
 - when checking out with GetFinancing, the quote only gets converted to
   an order after the loan has been preapproved.  This allows for easy
   rollback to other payment methods in case the loan is not preapproved.
 - the order is set to STATE_PAYMENT_REVIEW in Magento 1.4.1 and higher,
   and STATE_HOLDED before that.
 - Configure the postback url in the GetFinancing portal as
   /getfinancing/getfinancing/notification
   prefixed with your domain

Compatibility
-------------
 - This module has been tested with magento2
