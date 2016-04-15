Installing the module
---------------------

- unzip the .zip file
- copy to the magento2 root folder

execute the following commands using the shell :


  - bin/magento module:enable Getfinancing_Getfinancing
  - bin/magento setup:upgrade
  - bin/magento setup:di:compile

  - (OPTIONAL) change ownership of the magento folder to the web user

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
