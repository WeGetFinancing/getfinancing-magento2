/**
 * Getfinancing_Getfinancing payment form
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
 */

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'getfinancing_gateway',
                component: 'Getfinancing_Getfinancing/js/view/payment/method-renderer/getfinancing_gateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
