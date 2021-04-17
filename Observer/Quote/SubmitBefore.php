<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

namespace Pargo\CustomShipping\Observer\Quote;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Pargo\CustomShipping\Model\Carrier\Custom as PargoCarrier;

class SubmitBefore implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return $this
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');

        if ((string)$order->getShippingMethod() !== $this->getPargoCarrierCode()) {
            return $this;
        }

        // Shouldn't happen
        if (!$order->getShippingAddress()) {
            return $this;
        }

        $company = $order->getShippingAddress()->getData('company');
        $pickUpPointData = explode('-', $company);

        if (
            !$company ||
            !isset($pickUpPointData[1]) ||
            !$pickUpPointData[1] ||
            strpos($pickUpPointData[1], 'pup') === false
        ) {
            throw new LocalizedException(
                __('Please choose a Pargo Point before continuing')
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPargoCarrierCode()
    {
        return (PargoCarrier::CARRIER_CODE . '_' . PargoCarrier::CARRIER_CODE);
    }
}