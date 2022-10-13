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
    var listeners = [],
      doc = win.document,
      MutationObserver = win.MutationObserver || win.WebKitMutationObserver,
      observer;

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
        var i = 0, len = listeners.length, listener, elements;
        i < len;
        i++
      ) {
        listener = listeners[i];
        // Query for elements matching the specified selector
        elements = doc.querySelectorAll(listener.selector);
        for (var j = 0, jLen = elements.length, element; j < jLen; j++) {
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
    $(".opc-progress-bar").click(function () {
      if (
        $("input[value='pargo_customshipping_pargo_customshipping']").prop(
          "checked"
        ) &&
        localStorage.getItem("pargoPoint") != null
      ) {
        //$('.form-shipping-address').hide();
        //$('.checkout-shipping-address').hide();
        $(".continue").attr("disabled", false);
      }
    });
  });

  ready(".action-edit", function () {
    $(".action-edit").click(function () {
      if (
        $("input[value='pargo_customshipping_pargo_customshipping']").prop(
          "checked"
        ) &&
        localStorage.getItem("pargoPoint") != null
      ) {
        //$('.form-shipping-address').hide();
        //$('.checkout-shipping-address').hide();
        $(".continue").attr("disabled", false);
      }
    });
  });

  ready(".checkout", function () {
    console.log("Pargo: checkout ready");

    $(".checkout").click(function () {
      console.log("Pargo: checkout clicked");

      if(localStorage.getItem("pargoPoint")) {
        console.log("Pargo: " + localStorage.getItem("pargoPoint"));

        var pargoPoint = JSON.parse(localStorage.getItem("pargoPoint"));
/*
        var pargoPointTelephone = pargoPoint.phoneNumber.toString();
        if(pargoPointTelephone==="")
          pargoPointTelephone = "[]";
*/
          if(pargoPoint.phoneNumber === "" || pargoPoint.phoneNumber == null)
          {
              pargoPointTelephone = "";
          } else {
              var pargoPointTelephone = pargoPoint.phoneNumber.toString();
          }

          if(pargoPoint.address2 == "" || pargoPoint.address2 == null)
          {
              var pargoStreet1 = pargoPoint.suburb;
              var pargoStreet2 = "";
          } else {
              var pargoStreet1 = pargoPoint.address2;
              var pargoStreet2 = pargoPoint.suburb;
          }

        var shippingAddress = {
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

      }else{
        console.log("Pargo: error - pargoPoint not found in browser localStorage.");
      }
    });
  });

  $("input[value='pargo_customshipping_pargo_customshipping']").prop(
    "checked",
    true
  );

  var pargoPointState = false;
  var loadPargoInformation = false;
  var isLoggedIn = false;

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
      console.log("Pargo: pickup point code is not set.");
      return true;
    }else{
      console.log("Pargo: pickup point code = " + point.data.pargoPointCode);
    }

    localStorage.setItem("pargoPoint", JSON.stringify(point.data));

    // todo: review
    if (
      !jQuery("#checkout")
        .find('input[name="billing-address-same-as-shipping"]')
        .is(":checked")
    ) {
      jQuery(".checkout-shipping-address")
        .children()
        .find('input[name="billing-address-same-as-shipping"]')
        .trigger("click");
    }

    $(".close").trigger("click");
    $(".continue").attr("disabled", false);

    var nameSelector = "input[name=firstname]",
      lastnameSelector = "input[name=lastname]",
      telephoneSelector = "input[name=telephone]",
      name = $(nameSelector).val(),
      lastname = $(lastnameSelector).val(),
      telephone = $(telephoneSelector).val();

    if (name === "")
      name = "Pargo Shipment";
    if (lastname === "")
      lastname = "- Collect";
    if (telephone === "")
      telephone = "[]";

      if(point.data.address2 == "" || point.data.address2  == null)
      {
          var pargoStreet1 = point.data.suburb;
          var pargoStreet2 = "";
      } else {
          var pargoStreet1 = point.data.address2;
          var pargoStreet2 = point.data.suburb;
      }

    $(".form-shipping-address")
      .find("select[name=country_id]")
      .val("ZA")
      .change();
    $(".form-shipping-address").find(nameSelector).val(name).change();
    $(".form-shipping-address").find(lastnameSelector).val(lastname).change();
    $(".form-shipping-address")
      .find("input[name=company]")
      .val(point.data.storeName + "-" + point.data.pargoPointCode)
      .change();
    $(".form-shipping-address")
      .find('input[name="street[0]"]')
      .val(point.data.address1)
      .change();
    $(".form-shipping-address")
      .find('input[name="street[1]"]')
      .val(pargoStreet1)
      .change();
    $(".form-shipping-address")
      .find('input[name="street[2]"]')
      .val(pargoStreet2)
      .change();
    $(".form-shipping-address")
      .find("input[name=city]")
      .val(point.data.city)
      .change();
    $(".form-shipping-address")
      .find("input[name=region]")
      .val(point.data.province)
      .change();
    $(".form-shipping-address")
      .find("input[name=postcode]")
      .val(point.data.postalcode)
      .change();
    $(".form-shipping-address").find(telephoneSelector).val(telephone).change();

    pargoAlternativeDisplay();
    /* Custom updates */
    jQuery("div#checkout-step-shipping_method").css({
      opacity: "0.5",
      "pointer-events": "none",
    });

    setTimeout(function () {
      jQuery(".radio").each(function () {

        var value = jQuery(this).val();

        if (value == "pargo_customshipping_pargo_customshipping") {

        var pargoPoint = JSON.parse(localStorage.getItem("pargoPoint"));
/*
        var pargoPointTelephone = pargoPoint.phoneNumber.toString();
        if(pargoPointTelephone==="")
            pargoPointTelephone = "[]";

*/

        if(pargoPoint.phoneNumber === "" || pargoPoint.phoneNumber == null)
        {
          pargoPointTelephone = "";
        } else {
            var pargoPointTelephone = pargoPoint.phoneNumber.toString();
        }

        if(pargoPoint.address2 == "" || pargoPoint.address2 == null)
        {
            var pargoStreet1 = pargoPoint.suburb;
            var pargoStreet2 = "";
        } else {
            var pargoStreet1 = pargoPoint.address2;
            var pargoStreet2 = pargoPoint.suburb;
        }

          var shippingAddress = {
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

          console.log("Pargo: Shipping Address");
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

          jQuery("#checkout").find(".billing-address-form").show();
          jQuery("#checkout")
            .find('input[value="pargo_customshipping_pargo_customshipping"]')
            .trigger("click");

          jQuery(this).trigger("click");
        }
      });
      jQuery("div#checkout-step-shipping_method").css({
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

        var pargoPoint = JSON.parse(localStorage.getItem("pargoPoint"));
/*
        var pargoPointTelephone = pargoPoint.phoneNumber.toString();
        if(pargoPointTelephone==="")
          pargoPointTelephone = "[]";
*/

          if(pargoPoint.phoneNumber === "" || pargoPoint.phoneNumber == null)
          {
              pargoPointTelephone = "";
          } else {
              var pargoPointTelephone = pargoPoint.phoneNumber.toString();
          }

          if(pargoPoint.address2 == "" || pargoPoint.address2 == null)
          {
              var pargoStreet1 = pargoPoint.suburb;
              var pargoStreet2 = "";
          } else {
              var pargoStreet1 = pargoPoint.address2;
              var pargoStreet2 = pargoPoint.suburb;
          }
        var shippingAddress = {
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

    $(".radio").change(function () {
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
    var btnText = "Select a Pargo Point";
    var btnTextColor = "#000";
    var btnTextHoverColor = "#fff";
    var btnColor = "#fff200";
    var btnHoverColor = "#000";
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
      .mouseover(function () {
        $(this).css("background-color", btnHoverColor);
        $(this).css("color", btnTextHoverColor);
      })
      .mouseout(function () {
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

    var btnText = "Change Pargo Point";
    var btnTextColor = "#000";
    var btnTextHoverColor = "#fff";
    var btnColor = "#fff200";
    var btnHoverColor = "#000";
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
      .mouseover(function () {
        $(this).css("background-color", btnHoverColor);
        $(this).css("color", btnTextHoverColor);
      })
      .mouseout(function () {
        $(this).css("background-color", btnColor);
        $(this).css("color", btnTextColor);
      });
  }

  function renderPargo() {
    if (localStorage.getItem("pargoPoint")) {
      var btnText = "Change Pargo Point";
      var btnTextColor = "#000";
      var btnTextHoverColor = "#fff";
      var btnColor = "#fff200";
      var btnHoverColor = "#000";
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
        .mouseover(function () {
          $(this).css("background-color", btnHoverColor);
          $(this).css("color", btnTextHoverColor);
        })
        .mouseout(function () {
          $(this).css("background-color", btnColor);
          $(this).css("color", btnTextColor);
        });
    } else {
      var btnText = "Select a Pargo Point";
      var btnTextColor = "#000";
      var btnTextHoverColor = "#fff";
      var btnColor = "#fff200";
      var btnHoverColor = "#000";

      $(".form-shipping-address").hide();
      $(".pargo-store-info").remove();
      $(".pargo-btn").show();
      $("#pargo-point").hide();

      $(".pargo-btn").text(btnText);
      $(this).css("background-color", btnHoverColor);
      $(this).css("color", btnTextColor);

      $(".pargo-btn")
        .mouseover(function () {
          $(this).css("background-color", btnHoverColor);
          $(this).css("color", btnTextHoverColor);
        })
        .mouseout(function () {
          $(this).css("background-color", btnColor);
          $(this).css("color", btnTextColor);
        });
    }
  }
});
