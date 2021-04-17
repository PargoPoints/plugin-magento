<?php


namespace Pargo\CustomShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Pargo\CustomShipping\Helper\Config as Helper;

/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */


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
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Pargo\CustomShipping\Helper\Config $helper,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->helper = $helper;
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
        $request;
        // Check if custom shipping method is availabie in frontend
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

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->getCarrierCode() => __($this->getConfigData('name'))];
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
}
