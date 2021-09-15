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

class Custom extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    const CARRIER_CODE = 'pargo_customshipping';

    /**
     * Carrier identifier
     *
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_code = 'pargo_customshipping';
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
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        Helper $helper,
        Curl $curl,
        Session $customerSession,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->curl = $curl;
        $this->customerSession = $customerSession;

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
        // Check if custom shipping method is available in frontend
        if (!$this->helper->isAvailable()) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->getCarrierCode());
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->getCarrierCode());
        $method->setMethodTitle($this->getConfigData('name'));
        $method->setPrice($this->getPrice($request));
        $method->setCost($this->getConfigData('cost'));

        $result->append($method);

        if ($this->getConfigData("doortodoor_enabled")) {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->rateMethodFactory->create();
            $method->setCarrier($this->getCarrierCode());
            $method->setCarrierTitle($this->getConfigData('doortodoor_name'));

            $method->setMethod($this->getCarrierCode() . "_doortodoor");
            $method->setMethodTitle($this->getConfigData('doortodoor_title'));

            $price = (float)$this->getDoorToDoorPrice($request);

            $method->setPrice($price);
            $method->setCost($price); //@todo discuss cost

            if ($price > 0.00) {
                $result->append($method);
            }
        }

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $shippingMethods = [];

        if ($this->getConfigData("active")) {
            $shippingMethods[] =  [$this->getCarrierCode() => __($this->getConfigData('name'))];
            if ($this->getConfigData("doortodoor_enabled")) {
                $shippingMethods[] =  [$this->getCarrierCode()."_doortodoor" => __($this->getConfigData('doortodoor_name'))];
            }
        }

        return $shippingMethods;
    }

    /**
     * Get Price
     *
     * @param RateRequest $request
     * @return false|string
     */
    protected function getPrice(RateRequest $request)
    {
        $price = $this->getConfigData('price');
        $priceMatrix = $this->helper->getPriceMatrix($request->getStoreId());

        if ($priceMatrix) {
            $subtotal = $request->getBaseSubtotalInclTax();

            foreach ($priceMatrix as $condition) {
                if ($condition['from'] <= $subtotal and $condition['to'] >= $subtotal) {

                    return $condition['price'];
                }
            }
        }

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

        if($this->customerSession->isLoggedIn()){

            $customerName = $this->customerSession->getCustomer()->getName();
            $customerName = explode(" ", $customerName, 1);
            $customerFirstName = $customerName[0];
            $customerLastName = $customerName[0];

            $customerEmail =  $this->customerSession->getCustomer()->getEmail();
            $customerPhone = $this->customerSession->getCustomer()->getDefaultShippingAddress()->getTelephone();

            $customerAddress = $this->customerSession->getCustomer()->getDefaultShippingAddress()->getData();
            $destStreet = $customerAddress['street'];
            $destCity = $customerAddress['city'];
            $destPostCode = $customerAddress['postcode'];
        }

        //work out the suburb from the address
        $streetParts = explode("\n", $destStreet);

        $destSuburb = "";
        if (count($streetParts) > 1) {
            $destSuburb = $streetParts[count($streetParts)-1];
        }

        $parcels = $this->getParcels($request);

        $data = [
            'data' => [
                'type' => 'W2D',
                'attributes' => [
                    'externalReference' => "Ref".rand(10000, 99000),
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
                        "suburb" => $destSuburb, /**@todo dicuss this**/
                        "postalCode" => $destPostCode,
                        "city" => $destCity,
                        "country" => "ZA"
                    ],
                    'totalParcels' => count($parcels),
                    'parcels' => $parcels
                ]
            ]
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
            $this->logger->error('Pargo: Failed to get quotation for shipping method door to door');
            return false;
        } else {
            $this->logger->info('Pargo: Quotation retrieved successfully for door to door');
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
                        "externalReference" => "quote-ref-" . $id."-".$iCount,
                        "cubicWeight" => $item->getCubicWeight() ? $item->getCubicWeight(): 1,
                        "deadWeight" => $item->getDeadWeight() ? $item->getDeadWeight(): 1,
                        "length" => $item->getLength() ? $item->getLength(): 1,
                        "weight" => $item->getWeight() ? $item->getWeight(): 1,
                        "height" => $item->getHeight() ? $item->getHeight(): 1
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
        $this->logger->info('Pargo: Authenticating API');

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

        if ($err) {
            $this->logger->error('Pargo: Failed to authenticate API');
            return false;
        } else {
            $this->logger->info('Pargo: API Authentication successful');
            $response = json_decode($response);

            return $response->access_token;
        }
    }

}
