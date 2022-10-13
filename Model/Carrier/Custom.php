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

        // Setting up the Pargo Pickup method
        $method->setMethod($this->getCarrierCode());
        $method->setMethodTitle($this->getConfigData('name'));

        if ($this->getConfigData("enable_free_shipping" == 1)) {
            $freeThreshold = (float)$this->getConfigData("free-shipping_threshold");
            $cartSubtotal = $this->cart->getQuote()->getSubtotal();

            if ($cartSubtotal < $freeThreshold) {
                $price = 0.00;
                $method->setPrice($price);
                $method->setCost($price);
                $method->setMethodTitle($this->getConfigData('name') . ". You have qualified for Free Shipping");

            } else {

                $method->setPrice($this->getPrice($request));
                $method->setCost($this->getConfigData('cost'));

                if ($method->getPrice() == 0.00) {
                    $method->setCarrierTitle("Please configure your Pargo shipping method flat rate price");
                }
            }
        }
        $result->append($method);

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

        if ($this->getConfigData("active")) {
            $shippingMethods[] = [$this->getCarrierCode() => __($this->getConfigData('name'))];

        }

        return $shippingMethods;
    }

    /**
     * Get Flat Rate Price
     *
     * @param RateRequest $request
     * @return false|string
     */
    protected function getPrice(RateRequest $request)
    {
        $price = $this->getConfigData('price');
        return $price;
    }

}
