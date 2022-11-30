/**
 * Pargo CustomShipping
 *
 * @category    Pargo
 * @package     Pargo_CustomShipping
 * @copyright   Copyright (c) 2018 Pargo Points (https://pargo.co.za)
 * @license     http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author     dev@pargo.co.za
 */
require([
    "jquery",
    "domReady!",
    "ko",
    "uiComponent",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/checkout-data",
    "Magento_Customer/js/customer-data",
    "Magento_Checkout/js/checkout-data",
    "Magento_Checkout/js/action/select-shipping-address",
    "Magento_Checkout/js/action/create-shipping-address",
    "Magento_Checkout/js/action/create-billing-address",
], function (
    $,
    ko,
    Component,
    quote,
    domReady,
    checkout,
    customer,
    checkoutData,
    selectShippingAddressAction,
    createShippingAddress,
    createBillingAddress
) {
    (function (win) {
        "use strict";
        let listeners = [];
        let doc = win.document;
        let MutationObserver = win.MutationObserver || win.WebKitMutationObserver;
        let observer;

        function ready(selector, fn) {
            // Store the selector and callback to be monitored
            listeners.push({
                selector: selector,
                fn: fn,
            });
            if (!observer) {
                // Watch for changes in the document
                observer = new MutationObserver(check);
                observer.observe(doc.documentElement, {
                    childList: true,
                    subtree: true,
                });
            }
            // Check if the element is currently in the DOM
            check();
        }

        function check() {
            // Check the DOM for elements matching a stored selector
            for (
                let i = 0, len = listeners.length, listener, elements;
                i < len;
                i++
            ) {
                listener = listeners[i];
                // Query for elements matching the specified selector
                elements = doc.querySelectorAll(listener.selector);
                for (let j = 0, jLen = elements.length, element; j < jLen; j++) {
                    element = elements[j];
                    // Make sure the callback isn't invoked with the
                    // same element more than once
                    if (!element.ready) {
                        element.ready = true;
                        // Invoke the callback with the element
                        listener.fn.call(element, element);
                    }
                }
            }
        }

        // Expose `ready`
        win.ready = ready;
    })(this);

    ready(".opc-progress-bar", function () {
        $(".opc-progress-bar").on('click', function () {
            if (
                $("input[value='pargo_customshipping_pargo_customshipping']").prop(
                    "checked"
                ) &&
                localStorage.getItem("pargoPoint") !== null
            ) {
                //$('.form-shipping-address').hide();
                //$('.checkout-shipping-address').hide();
                $(".continue").attr("disabled", false);
            }
        });
    });

    ready(".action-edit", function () {
        $(".action-edit").on('click', function () {
            if (
                $("input[value='pargo_customshipping_pargo_customshipping']").prop(
                    "checked"
                ) &&
                localStorage.getItem("pargoPoint") !== null
            ) {
                //$('.form-shipping-address').hide();
                //$('.checkout-shipping-address').hide();
                $(".continue").attr("disabled", false);
            }
        });
    });

    ready(".checkout", function () {
        console.log("Pargo: checkout ready");

        $(".checkout").on('click', function () {
            console.log("Pargo: checkout clicked");

            if(localStorage.getItem("pargoPoint")) {
                console.log("Pargo: " + localStorage.getItem("pargoPoint"));

                let pargoPoint = JSON.parse(localStorage.getItem("pargoPoint"));
                /*
                        var pargoPointTelephone = pargoPoint.phoneNumber.toString();
                        if(pargoPointTelephone==="")
                          pargoPointTelephone = "[]";
                */
                let pargoPointTelephone = "";
                if(pargoPoint.phoneNumber !== "" && pargoPoint.phoneNumber) {
                    pargoPointTelephone = pargoPoint.phoneNumber.toString();
                }
                let pargoStreet1 = pargoPoint.suburb;
                let pargoStreet2 = "";
                if(pargoPoint.address2 !== "" && pargoPoint.address2)
                {
                    pargoStreet1 = pargoPoint.address2;
                    pargoStreet2 = pargoPoint.suburb;
                }

                const shippingAddress = {
                    firstname: "Pargo Shipment",
                    lastname: "- Collect",
                    extension_attributes: {
                        pickupPointCode: pargoPoint.pargoPointCode,
                    },
                    // pickupPointCode: pargoPoint.pargoPointCode,
                    company: pargoPoint.storeName,
                    street: {
                        0: pargoPoint.address1,
                        1: pargoStreet1,
                        2: pargoStreet2,
                    },
                    //suburb: pargoPoint.suburb,
                    city: pargoPoint.city,
                    postcode: pargoPoint.postalcode,
                    region: pargoPoint.province,
                    country_id: "ZA",
                    telephone: pargoPointTelephone,
                    latitude: pargoPoint.latitude,
                    longitude: pargoPoint.longitude,
                    photo: pargoPoint.photo,
                    photo_small: pargoPoint.photo_small,
                };

                checkout.setSelectedShippingRate(
                    "pargo_customshipping_pargo_customshipping"
                );
                checkout.setSelectedShippingAddress(shippingAddress);

            } else {
                console.log("Pargo: error - pargoPoint not found in browser localStorage.");
            }
        });
    });

    $("input[value='pargo_customshipping_pargo_customshipping']").prop(
        "checked",
        true
    );

    let pargoPointState = false;
    let loadPargoInformation = false;
    let isLoggedIn = false;

    if (window.checkoutConfig.customerData.firstname) {
        isLoggedIn = true;
    }

    //set pargo state
    if (localStorage.getItem("pargoPoint")) {
        pargoPointState = true;
    }

    /*
    // pargo can be loaded
    if (
      pargoPointState === true &&
      checkout.getSelectedShippingRate() ===
        "pargo_customshipping_pargo_customshipping"
    ) {
      loadPargoInformation = true;
      pargoPointState = true;
    }

    //pargo can be loaded just set the shipping rate
    if (
      pargoPointState === true &&
      checkout.getSelectedShippingRate() !==
        "pargo_customshipping_pargo_customshipping"
    ) {
      loadPargoInformation = true;
      pargoPointState = true;
      checkout.setSelectedShippingRate(
        "pargo_customshipping_pargo_customshipping"
      );
    }
    */
    // * changes start
    if(pargoPointState===true){
        loadPargoInformation = true;
        //checkout.setSelectedShippingRate("pargo_customshipping_pargo_customshipping");
    }
    // * changes end

    // iFrame close event listener
    if (window.addEventListener) {
        window.addEventListener("message", setPargoPointInformation, false);
    } else {
        window.attachEvent("onmessage", setPargoPointInformation);
    }

    function setPargoPointInformation(point) {

        console.log("Pargo: setPargoPointInformation");

        if (!point.data.pargoPointCode) {
            console.log("Pargo:", "pickup point code is not set.");
            return true;
        } else {
            console.log("Pargo:", "pickup point code = " + point.data.pargoPointCode);
        }

        localStorage.setItem("pargoPoint", JSON.stringify(point.data));

        // todo: review
        if (
            !$("#checkout")
                .find('input[name="billing-address-same-as-shipping"]')
                .is(":checked")
        ) {
            $(".checkout-shipping-address")
                .children()
                .find('input[name="billing-address-same-as-shipping"]')
                .trigger("click");
        }

        $(".close").trigger("click");
        $(".continue").attr("disabled", false);

        let nameSelector = "input[name=firstname]",
            lastnameSelector = "input[name=lastname]",
            telephoneSelector = "input[name=telephone]",
            name = $(nameSelector).val(),
            lastname = $(lastnameSelector).val(),
            telephone = $(telephoneSelector).val();

        if (name === "") name = "Pargo Shipment";
        if (lastname === "") lastname = "- Collect";
        if (telephone === "") telephone = "[]";

        let pargoStreet1 = point.data.suburb;
        let pargoStreet2 = "";
        if(point.data.address2 !== "" && point.data.address2)
        {
            pargoStreet1 = point.data.address2;
            pargoStreet2 = point.data.suburb;
        }

        // todo: remove find chained function
        $(".form-shipping-address select[name=country_id]")
            .val("ZA");
        $(".form-shipping-address " + nameSelector).val(name).trigger('change');
        $(".form-shipping-address " + lastnameSelector).val(lastname).trigger('change');
        $(".form-shipping-address input[name=company]").val(point.data.storeName + "-" + point.data.pargoPointCode).trigger('change');
        $(".form-shipping-address input[name=\"street[0]\"]").val(point.data.address1).trigger('change');
        $(".form-shipping-address input[name=\"street[1]\"]").val(pargoStreet1).trigger('change');
        $(".form-shipping-address input[name=\"street[2]\"]").val(pargoStreet2).trigger('change');
        $(".form-shipping-address input[name=city]").val(point.data.city).trigger('change');
        $(".form-shipping-address input[name=region]").val(point.data.province).trigger('change');
        $(".form-shipping-address input[name=postcode]").val(point.data.postalcode).trigger('change');
        $(".form-shipping-address" + telephoneSelector).val(telephone).trigger('change');

        pargoAlternativeDisplay();
        /* Custom updates */
        $("div#checkout-step-shipping_method").css({
            opacity: "0.5",
            "pointer-events": "none",
        });

        setTimeout(function () {
            $(".radio").each(function () {

                const value = $(this).val();

                if (value === "pargo_customshipping_pargo_customshipping") {

                    const pargoPoint = JSON.parse(localStorage.getItem("pargoPoint"));
                    /*
                            var pargoPointTelephone = pargoPoint.phoneNumber.toString();
                            if(pargoPointTelephone==="")
                                pargoPointTelephone = "[]";

                    */
                    let pargoPointTelephone = "";
                    if(pargoPoint.phoneNumber !== "" && pargoPoint.phoneNumber)
                    {
                        pargoPointTelephone = pargoPoint.phoneNumber.toString();
                    }

                    let pargoStreet1 = pargoPoint.suburb;
                    let pargoStreet2 = "";
                    if(pargoPoint.address2 !== "" && pargoPoint.address2)
                    {
                        pargoStreet1 = pargoPoint.address2;
                        pargoStreet2 = pargoPoint.suburb;
                    }

                    const shippingAddress = {
                        firstname: "Pargo Shipment",
                        lastname: "- Collect",
                        company: pargoPoint.storeName,
                        street: {
                            0: pargoPoint.address1,
                            1: pargoStreet1,
                            2: pargoStreet2,
                        },
                        suburb: pargoPoint.suburb,
                        city: pargoPoint.city,
                        postcode: pargoPoint.postalcode,
                        region: pargoPoint.province,
                        country_id: "ZA",
                        telephone: pargoPointTelephone,
                        latitude: pargoPoint.latitude,
                        longitude: pargoPoint.longitude,
                        photo: pargoPoint.photo,
                        photo_small: pargoPoint.photo_small,

                        save_in_address_book: 0,
                    };

                    let shipAddr = createShippingAddress(shippingAddress);
                    //if (shipAddr['extension_attributes'] === undefined) {
                    //  shipAddr['extension_attributes'] = {};
                    //}
                    //shipAddr['extension_attributes']['pickupPointCode'] = pargoPoint.pargoPointCode;

                    console.log("Pargo:", "Shipping Address");
                    console.log(shipAddr);
                    let billAddr = createBillingAddress();
                    shipAddr.canUseForBilling(false);
                    checkout.setSelectedShippingRate(
                        "pargo_customshipping_pargo_customshipping"
                    );
                    checkout.setBillingAddressFromData(billAddr);
                    checkout.setSelectedShippingAddress(shipAddr);
                    // checkout.setNewCustomerShippingAddress(addr);
                    // checkout.getSelectedShippingAddress();
                    // checkout.getNewCustomerShippingAddress();
                    this.checked = true;

                    $("#checkout").find(".billing-address-form").show();
                    $("#checkout input[value=\"pargo_customshipping_pargo_customshipping\"]")
                        .trigger("click");

                    $(this).trigger("click");
                }
            });
            $("div#checkout-step-shipping_method").css({
                opacity: "1",
                "pointer-events": "visible",
            });
        }, 7000);
    }

    ready(".pargo-btn", function () {
        if (
            (pargoPointState === true &&
                loadPargoInformation === true &&
                $(".radio:checked").val() ===
                "pargo_customshipping_pargo_customshipping") ||
            (checkout.getSelectedShippingRate() ===
                "pargo_customshipping_pargo_customshipping" &&
                localStorage.getItem("pargoPoint") !== null)
        ) {
            pargoAlternativeDisplay();
            if (isLoggedIn) {

                const pargoPoint = JSON.parse(localStorage.getItem("pargoPoint"));
                /*
                        var pargoPointTelephone = pargoPoint.phoneNumber.toString();
                        if(pargoPointTelephone==="")
                          pargoPointTelephone = "[]";
                */
                let pargoPointTelephone = "";
                if(pargoPoint.phoneNumber !== "" && pargoPoint.phoneNumber)
                {
                    pargoPointTelephone = pargoPoint.phoneNumber.toString();
                }
                let pargoStreet1 = pargoPoint.suburb;
                let pargoStreet2 = "";
                if(pargoPoint.address2 !== "" && pargoPoint.address2)
                {
                    pargoStreet1 = pargoPoint.address2;
                    pargoStreet2 = pargoPoint.suburb;
                }
                const shippingAddress = {
                    firstname: "Pargo Shipment",
                    lastname: "- Collect",
                    pickupPointCode: pargoPoint.pargoPointCode,
                    company: pargoPoint.storeName,
                    street: {
                        0: pargoPoint.address1,
                        1: pargoStreet1,
                        2: pargoStreet2,
                    },
                    suburb: pargoPoint.suburb,
                    city: pargoPoint.city,
                    postcode: pargoPoint.postalcode,
                    region: pargoPoint.province,
                    country_id: "ZA",
                    telephone: pargoPointTelephone,
                    latitude: pargoPoint.latitude,
                    longitude: pargoPoint.longitude,
                    photo: pargoPoint.photo,
                    photo_small: pargoPoint.photo_small,

                    save_in_address_book: 0,
                };

                // selectShippingAddressAction(shippingAddress);
                // checkoutData.setSelectedShippingAddress(shippingAddress);
                // checkout.setSelectedShippingRate('pargo_customshipping_pargo_customshipping');
                // checkout.setShippingAddressFromData(shippingAddress);
            }
        } else {
            pargoDefaultDisplay();
            if (
                $(".radio:checked").val() !==
                "pargo_customshipping_pargo_customshipping"
            ) {
                $(".form-shipping-address").show();
            }
        }

        $(".radio").on('change', function () {
            if (
                $(this).val() === "pargo_customshipping_pargo_customshipping" &&
                isLoggedIn
            ) {
                pargoDefaultDisplay();
                //$('.form-shipping-address').hide();
                //$('.checkout-shipping-address').hide();
            } else {
                localStorage.removeItem("pargoPoint");
                pargoDefaultDisplay();
                $(".form-shipping-address").show();
                $(".checkout-shipping-address").show();
                $(".pargo-btn").hide();
            }

            if ($(this).val() === "pargo_customshipping_pargo_customshipping") {
                pargoDefaultDisplay();
                //$('.form-shipping-address').hide();
            } else {
                localStorage.removeItem("pargoPoint");
                pargoDefaultDisplay();
                $(".form-shipping-address").show();
                $(".pargo-btn").hide();
            }
        });
    });

    function pargoDefaultDisplay() {
        const btnText = "Select a Pargo Point";
        const btnTextColor = "#000";
        const btnTextHoverColor = "#fff";
        const btnColor = "#fff200";
        const btnHoverColor = "#000";
        $(".pargo-store-info").remove();
        $(".pargo-btn").show();

        if (localStorage.getItem("pargoPoint") === null) {
            $(".form-shipping-address").hide();
            $("#pargo-point").hide();
        }

        $(".pargo-btn").text(btnText);
        $(this).css("background-color", btnHoverColor);
        $(this).css("color", btnTextColor);

        $(".pargo-btn")
            .on('mouseover', function () {
                $(this).css("background-color", btnHoverColor);
                $(this).css("color", btnTextHoverColor);
            })
            .on('mouseout', function () {
                $(this).css("background-color", btnColor);
                $(this).css("color", btnTextColor);
            });
    }

    function pargoAlternativeDisplay() {
        if (
            $(".radio:checked").val() ===
            "pargo_customshipping_pargo_customshipping" ||
            checkout.getSelectedShippingRate() ===
            "pargo_customshipping_pargo_customshipping"
        ) {
            pargoDefaultDisplay();
        }

        const btnText = "Change Pargo Point";
        const btnTextColor = "#000";
        const btnTextHoverColor = "#fff";
        const btnColor = "#fff200";
        const btnHoverColor = "#000";
        $(".form-shipping-address").hide();
        $(".pargo-btn").show();
        $(".pargo-btn").text(btnText);
        $(".pargo-store-info").remove();
        if (localStorage.getItem("pargoPoint")) {
            $("#pargo-point")
                .show()
                .append(
                    '<div class="pargo-store-info"><strong>Store name</strong><p>' +
                    JSON.parse(localStorage.getItem("pargoPoint")).storeName +
                    "</p><br><strong>Store location</strong><p>" +
                    JSON.parse(localStorage.getItem("pargoPoint")).address1 +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).city +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).postalcode +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).province +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).suburb +
                    "</p></div>"
                );
        }

        $(this).css("background-color", btnHoverColor);
        $(this).css("color", btnTextColor);

        $(".pargo-btn")
            .on('mouseover', function () {
                $(this).css("background-color", btnHoverColor);
                $(this).css("color", btnTextHoverColor);
            })
            .on('mouseout', function () {
                $(this).css("background-color", btnColor);
                $(this).css("color", btnTextColor);
            });
    }

    function renderPargo() {
        let btnText = "Change Pargo Point";
        const btnTextColor = "#000";
        const btnTextHoverColor = "#fff";
        const btnColor = "#fff200";
        const btnHoverColor = "#000";
        if (localStorage.getItem("pargoPoint")) {
            $(".form-shipping-address").hide();
            $(".pargo-btn").show();
            $(".pargo-store-info").remove();
            $("#pargo-point")
                .show()
                .append(
                    '<div class="pargo-store-info"><strong>Store name</strong><p>' +
                    JSON.parse(localStorage.getItem("pargoPoint")).storeName +
                    "</p><br><strong>Store location</strong><p>" +
                    JSON.parse(localStorage.getItem("pargoPoint")).address1 +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).city +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).postalcode +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).province +
                    ", " +
                    JSON.parse(localStorage.getItem("pargoPoint")).suburb +
                    "</p></div>"
                );

            $(".pargo-btn").text(btnText);
            $(this).css("background-color", btnHoverColor);
            $(this).css("color", btnTextColor);

            $(".pargo-btn")
                .on('mouseover', function () {
                    $(this).css("background-color", btnHoverColor);
                    $(this).css("color", btnTextHoverColor);
                })
                .on('mouseout', function () {
                    $(this).css("background-color", btnColor);
                    $(this).css("color", btnTextColor);
                });
        } else {
            btnText = "Select a Pargo Point";

            $(".form-shipping-address").hide();
            $(".pargo-store-info").remove();
            $(".pargo-btn").show();
            $("#pargo-point").hide();

            $(".pargo-btn").text(btnText);
            $(this).css("background-color", btnHoverColor);
            $(this).css("color", btnTextColor);

            $(".pargo-btn")
                .on('mouseover', function () {
                    $(this).css("background-color", btnHoverColor);
                    $(this).css("color", btnTextHoverColor);
                })
                .on('mouseout', function () {
                    $(this).css("background-color", btnColor);
                    $(this).css("color", btnTextColor);
                });
        }
    }
});
