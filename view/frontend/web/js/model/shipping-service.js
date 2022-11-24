define(
    [
        'jquery', // For jQuery Added
        'Magento_Checkout/js/model/quote', // For Quote Added
        'ko',
        'Magento_Checkout/js/model/checkout-data-resolver',
        'Magento_Checkout/js/model/shipping-rate-registry'

    ],
    function ($, quote, ko, checkoutDataResolver, rateRegistry) {
        "use strict";
        var shippingRates = ko.observableArray([]);
        var rateRegistry = rateRegistry;
        var quote = quote;



        $(document).ready(function() {
            console.log ('loaded', $('.input-text[name=postcode]').length);

            setTimeout(function() {
                console.log('running');
                jQuery(':input[name="postcode"], :input[name="city"], :input[name="street[1]"]').on('blur', function (event) {
                    requirejs([
                        'Magento_Checkout/js/model/quote',
                        'Magento_Checkout/js/model/shipping-rate-registry'
                    ], function (quote, rateRegistry) {
                        console.log('checking address');
                        //get address from quote observable
                        var address = quote.shippingAddress();

                        //changes the object so observable sees it as changed
                        address.trigger_reload = new Date().getTime();

                        //create rate registry cache
                        //the two calls are required
                        //because Magento caches things
                        //differently for new and existing
                        //customers (a FFS moment)
                        rateRegistry.set(address.getKey(), null);
                        rateRegistry.set(address.getCacheKey(), null);

                        //with rates cleared, the observable listeners should
                        //update everything when the rates are updated
                        quote.shippingAddress(address);
                    });
                });
            } , 3000);
        });

        return {
            isLoading: ko.observable(false),
            /**
             * Set shipping rates
             *
             * @param ratesData
             */
            setShippingRates: function (ratesData) {
                shippingRates(ratesData);
                shippingRates.valueHasMutated();
                checkoutDataResolver.resolveShippingRates(ratesData);
            },

            /**
             * Get shipping rates
             *
             * @returns {*}
             */
            getShippingRates: function () {
                return shippingRates;
            }
        };
    }
);
