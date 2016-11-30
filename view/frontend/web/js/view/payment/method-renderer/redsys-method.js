/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
            'Magento_Checkout/js/view/payment/default',
            'mage/url'
        ],
    function (
        Component,
        url
    ) {
            'use strict';

            return Component.extend(
                {
                    defaults: {
                        template: 'Codeko_Redsys/payment/form',
                    },
                    redirectAfterPlaceOrder: false,
                
                    getCode: function () {
                        return 'redsys';
                    },
                
                    getData: function () {
                        return {
                            'method': this.item.method,
                            'additional_data': {
                                'transaction_result': "1"
                            }
                        };
                    },
                
                    afterPlaceOrder: function () {
                        window.location.replace(url.build('redsys/'));
                    }
                }
            );
    }
);