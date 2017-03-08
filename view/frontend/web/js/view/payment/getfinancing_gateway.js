/**
 * Getfinancing_Getfinancing payment form

 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2016 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
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
