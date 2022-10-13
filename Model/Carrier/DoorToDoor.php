<?php
/**
 * Pargo CustomShipping
 *
 * @category    Pargo
 * @package     Pargo_CustomShipping
 * @copyright   Copyright (c) 2018 Pargo Points (https://pargo.co.za)
 * @license     http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author     dev@pargo.co.za
 */

namespace Pargo\CustomShipping\Model\Carrier;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Pargo\CustomShipping\Helper\Config as Helper;
use Pargo\CustomShipping\Logger\Logger;
use Magento\Checkout\Model\Cart;

class DoorToDoor extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const CARRIER_CODE = 'pargo_customshipping_doortodoor';

    /**
     * Carrier identifier
     *
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_code = 'pargo_customshipping_doortodoor';
    // @codingStandardsIgnoreEnd

    /**
     * This carrier has fixed rates calculation
     *
     * @var bool
     */
    public $isFixed = true;

    /**
     * @var ResultFactory
     */
    public $rateResultFactory;

    /**
     * @var MethodFactory
     */
    public $rateMethodFactory;

    /**
     * @var \Pargo\CustomShipping\Helper\Config
     */
    public $helper;

    /**
     * @var Curl
     */
    public $curl;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param Helper $helper
     * @param Curl $curl
     * @param Session $customerSession
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface     $scopeConfig,
        ErrorFactory             $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        ResultFactory            $rateResultFactory,
        MethodFactory            $rateMethodFactory,
        Helper                   $helper,
        Curl                     $curl,
        Session                  $customerSession,
        Logger                   $pargoLogger,
        Cart                     $cart,
        array                    $data = []
    )
    {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->logger = $pargoLogger;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->customerSession = $customerSession;
        $this->cart = $cart;

        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Collect and get rates for backend and frontend
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param RateRequest $request
     * @return DataObject|bool|null
     * @api
     */
    public function collectRates(RateRequest $request)
    {

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */

        // Setting up the Home Delivery method
        if ($this->getConfigData("doortodoor_enabled")) {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->rateMethodFactory->create();
            $method->setCarrier($this->getCarrierCode());
            $method->setCarrierTitle($this->getConfigData('doortodoor_title'));

            $method->setMethod($this->getCarrierCode());// . "_doortodoor");
            $method->setMethodTitle($this->getConfigData('doortodoor_name'));

            if ($this->getConfigData("doortodoor_enable_free_shipping" == 1)) {
                $freeThreshold = (float)$this->getConfigData("doortodoor_free-shipping_threshold");
                $cartSubtotal = $this->cart->getQuote()->getSubtotal();
                $this->logger->info('steve ' . $cartSubtotal);

                if ($cartSubtotal < $freeThreshold) {
                    $price = 0.00;
                    $method->setPrice($price);
                    $method->setCost($price);
                    $method->setMethodTitle($this->getConfigData('doortodoor_name') . ". You have qualified for Free Shipping");

                } else {

                    if ($this->getConfigData("live_rates_enabled")) {
                        $price = (float)$this->getDoorToDoorPrice($request);

                        $method->setPrice($price);
                        $method->setCost($price); //@todo discuss cost

                        if ($price == 0.00) {
                            //Fall back if no price is retrieved from the API
                            $method->setPrice($this->getDoorToDoorFlatPrice($request));
                            $method->setMethodTitle($this->getConfigData('doortodoor_name') . ". Suburb, City & Postal Code required for an accurate estimate");
                        }
                    } else {
                        $method->setPrice($this->getDoorToDoorFlatPrice($request));
                        $method->setMethodTitle($this->getConfigData('doortodoor_name') . ". Flat rates in use.");
                    }

                    if ($method->getPrice() == 0.00) {
                        $method->setMethodTitle("Please configure your door to door shipping method correctly");
                    }
                }
            }
            $result->append($method);
        }

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        $shippingMethods = [];

//        if ($this->getConfigData("active")) {
//           $shippingMethods[] .=  [$this->getCarrierCode() => __($this->getConfigData('name'))];

//        }
        if ($this->getConfigData("doortodoor_enabled")) {
            $shippingMethods[] .= [$this->getCarrierCode() => __($this->getConfigData('doortodoor_name'))];
        }
        return $shippingMethods;
    }

    /**
     * Get Door to Door Flat Rate Price
     *
     * @param RateRequest $request
     * @return false|string
     */
    protected function getDoorToDoorFlatPrice(RateRequest $request)
    {
        $price = $this->getConfigData('doortodoor_price');
        return $price;
    }


    protected function getDoorToDoorPrice(RateRequest $request)
    {
        $destStreet = $request->getDestStreet();
        $destRegionCode = $request->getDestRegionCode();
        $destCity = $request->getDestCity();
        $destPostCode = $request->getDestPostcode();
        $customerFirstName = "Guest";
        $customerLastName = "Checkout";
        $customerPhone = "11111111111";
        $customerEmail = "dev@pargo.co.za";

        if ($this->customerSession->isLoggedIn()) {
            try {
                $customerName = $this->customerSession->getCustomer()->getName();
                $customerName = explode(" ", $customerName, 1);
                $customerFirstName = $customerName[0];
                $customerLastName = $customerName[0];

                if ($this->customerSession->getCustomer()) {
                    $customerEmail = $this->customerSession->getCustomer()->getEmail();

                    if ($this->customerSession->getCustomer()->getDefaultShippingAddress()) {
                        $customerAddress = $this->customerSession->getCustomer()->getDefaultShippingAddress()->getData();

                        $destStreet = $customerAddress['street'];
                        $destCity = $customerAddress['city'];
                        $destPostCode = $customerAddress['postcode'];
                    }
                }
                $this->logger->info('Pargo: We tried to connect');

            } catch (\Exception $exception) {
                $this->logger->error('Pargo: Logged in customer details not complete');
            }
        }

        //work out the suburb from the address
        $streetParts = explode("\n", $destStreet);

        $destSuburb = "";
        if (count($streetParts) > 1) {
            $destSuburb = $streetParts[count($streetParts) - 1];
        }

        $parcels = $this->getParcels($request);
        // Enforcing the return of a single parcel for now in line with order sent.
        $parcels = array($parcels[0]);
        $data = [
            'data' => [
                'type' => 'W2D',
                'attributes' => [
                    'externalReference' => "Ref" . rand(10000, 99000),
                    'consignee' => [
                        'firstName' => $customerFirstName,
                        'lastName' => $customerLastName,
                        'email' => $customerEmail,
                        'phoneNumbers' => [
                            $customerPhone
                        ],
                        "address1" => $destStreet,
                        "address2" => "",
                        "province" => $destRegionCode,
                        "suburb" => $destSuburb,
                        "postalCode" => $destPostCode,
                        "city" => $destCity,
                        "country" => "ZA"
                    ],
                    'totalParcels' => count($parcels),
                    'parcels' => $parcels
                ]
            ],
            'source' => 'magento'
        ];

        $url = $this->helper->getUrl();
        $token = $this->authenticate();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . '/orders/quotation',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->logger->error('Pargo Door to Door: Failed to get quotation for shipping method door to door');
            return false;
        } else {
            $this->logger->info('Pargo Door to Door: Quotation retrieved successfully for door to door');
            $response = json_decode($response);
            if (isset($response->data)) {
                return $response->data->attributes->quotation->price;
            } else {
                return 0.00;
            }
        }
    }

    /***
     * Method to populate the parcels
     * @param RateRequest $request
     * @return array
     */
    protected function getParcels(RateRequest $request)
    {
        $items = $request->getAllItems();
        $parcels = [];

        foreach ($items as $id => $item) {
            $iCount = 0;

            while ($iCount < $item->getQty()) {
                $parcels [] =
                    (object)[
                        "externalReference" => "quote-ref-" . $id . "-" . $iCount,
                        "cubicWeight" => $item->getCubicWeight() ? $item->getCubicWeight() : 1,
                        "deadWeight" => $item->getDeadWeight() ? $item->getDeadWeight() : 1,
                        "length" => $item->getLength() ? $item->getLength() : 1,
                        "weight" => $item->getWeight() ? $item->getWeight() : 1,
                        "height" => $item->getHeight() ? $item->getHeight() : 1
                    ];

                $iCount++;
            }
        }

        return $parcels;
    }

    /**
     * @return bool
     */
    private function authenticate()
    {
        $this->logger->info('Pargo Door to Door: Authenticating API');

        $url = $this->helper->getUrl();
        $username = $this->helper->getUsername();
        $password = $this->helper->getPassword();

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . '/auth',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode(array('username' => $username, 'password' => $password)),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        $this->logger->info('Pargo Door to Door: deets' . " " . $url . " " . $username . " " . $password);

        if ($err) {
            $this->logger->error('Pargo Door to Door: Failed to authenticate API' . $err);
            $this->logger->error(print_r($err, true));
            return false;
        } else {
            $response = json_decode($response);

            if (!empty($response->access_token)) {
                $this->logger->info('Pargo Door to Door: API Authentication successful');
                return $response->access_token;
            } else {
                $this->logger->error('Pargo Door to Door: Failed to authenticate API');

                return false;
            }
        }
    }

}
