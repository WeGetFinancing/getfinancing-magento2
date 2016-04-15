/**
 * Getfinancing_Getfinancing payment form

 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2016 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
 */

define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'mage/url',
    ],
    function (Component, quote, url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Getfinancing_Getfinancing/payment/form'
            },

            getCode: function() {
                return 'getfinancing_gateway';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                };
            },
            afterPlaceOrder: function () {
                window.location.replace( url.build( 'getfinancing/getfinancing/redirect/' ) );
            },
        });
    }
);
