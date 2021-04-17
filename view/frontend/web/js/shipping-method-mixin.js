/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

define([
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function (quote, $t) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                if (quote.shippingMethod()['carrier_code'] === 'pargo_customshipping') {
                    if (localStorage.getItem('pargoPoint') === null) {
                        this.errorValidationMessage($t('Please choose a Pargo Point before continuing'));

                        return false;
                    }
                }

                return this._super();
            }
        });
    }
});