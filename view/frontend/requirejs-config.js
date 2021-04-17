/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

var config = {
    map: {
        '*': {
            'Magento_Checkout/js/model/checkout-data-resolver': 'Pargo_CustomShipping/js/checkout-data-resolver',
            'Magento_Checkout/js/model/shipping-save-processor/default': 'Pargo_CustomShipping/js/shipping-save-processor/default'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'Pargo_CustomShipping/js/shipping-method-mixin': true
            }
        }
    }
};