/**
 * Pargo CustomShipping
 *
 * @category    Pargo
 * @package     Pargo_CustomShipping
 * @copyright   Copyright (c) 2018 Pargo Points (https://pargo.co.za)
 * @license     http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author     dev@pargo.co.za
 */
define([
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function (quote, $t) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                if (quote.shippingMethod()['method_code'] === 'pargo_customshipping') {
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
