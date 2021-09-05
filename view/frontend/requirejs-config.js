/**
 * Pargo CustomShipping
 *
 * @category    Pargo
 * @package     Pargo_CustomShipping
 * @copyright   Copyright (c) 2018 Pargo Points (https://pargo.co.za)
 * @license     http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author     dev@pargo.co.za
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
