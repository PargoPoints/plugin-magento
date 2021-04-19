<?php

namespace Pargo\CustomShipping\Observer\Quote;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Pargo\CustomShipping\Model\Carrier\Custom as PargoCarrier;

class SubmitBefore implements ObserverInterface
{
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $this->logger->info('Pargo: Validate Pickup Point');

        $order = $observer->getEvent()->getData('order');

        if ((string)$order->getShippingMethod() !== $this->getPargoCarrierCode()) {
            $this->logger->error('Pargo: Shipping method is not Pargo Pickup Points, ignore.');
            return $this;
        }

        if (!$order->getShippingAddress()) {
            $this->logger->error('Pargo: getShippingAddress returned null');
            return $this;
        }

        // test custom order address field
        $extAttributes = $order->getShippingAddress()->getExtensionAttributes();
        $pickupPointCode = $extAttributes->getPickupPointCode(); 
        $this->logger->info('Pargo: Pickup Point Code: ' . $pickupPointCode);

        $company = $order->getShippingAddress()->getData('company');
        // todo: pickupPointCode check
        $this->logger->info('Pargo: Pickup Point company: ' . $company);
        if (
            !$company
        ) {
            throw new LocalizedException(
                __('Please choose a Pargo Point before continuing')
            );
        }

        $this->logger->info('Pargo: Pickup Point validated');
        return $this;
    }

    public function getPargoCarrierCode()
    {
        $carrier_code = PargoCarrier::CARRIER_CODE . '_' . PargoCarrier::CARRIER_CODE;
        $this->logger->info($carrier_code);
        return ($carrier_code);
    }
}