<?php

namespace Pargo\CustomShipping\Model\Adminhtml\Shipping\Config\Source;

/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */


class Allmethods implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Framework\App\Config
     */
    public $shippingConfig;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    public $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config $scopeConfig,
        \Magento\Shipping\Model\Config $shippingConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Return array of options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $carriers = $this->shippingConfig->getActiveCarriers();
        $methods = [];

        foreach ($carriers as $carrierCode => $carrierModel) {
            if (!$carrierMethods = $carrierModel->getAllowedMethods()) {
                continue;
            }

            $title = $this->scopeConfig->getValue('carriers/' . $carrierCode . '/title');

            foreach ($carrierMethods as $methodCode => $method) {
                $code = $carrierCode . '_' . $methodCode;

                if ($code == 'pargo_customshipping_pargo_customshipping') {
                    continue;
                }

                $methods[] = [
                    'label' => $title,
                    'value' => $code
                ];
            }
        }

        return $methods;
    }
}
