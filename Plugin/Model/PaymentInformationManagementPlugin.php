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

namespace Pargo\CustomShipping\Plugin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Store\Model\ScopeInterface;

class PaymentInformationManagementPlugin
{

    /**
     * @var PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * PaymentInformationManagement constructor.
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param ScopeConfigInterface $scopeConfig
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        PaymentMethodManagementInterface $paymentMethodManagement,
        ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $cartRepository
    ) {
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->scopeConfig = $scopeConfig;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param callable $proceed
     * @param integer $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function aroundSavePaymentInformation(
        \Magento\Checkout\Model\PaymentInformationManagement $subject,
        callable $proceed,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        if ($billingAddress) {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($cartId);
            $shippingAddress = $quote->getShippingAddress();

            if ($shippingAddress->getShippingMethod() !== 'pargo_customshipping_pargo_customshipping') {
                $proceed($cartId, $paymentMethod, $billingAddress);
                return true;
            }

            $quote->removeAddress($quote->getBillingAddress()->getId());
            $quote->setBillingAddress($billingAddress);
            $quote->setDataChanges(true);
            $shippingAddress->setLimitCarrier('pargo_customshipping');
        }
        $this->paymentMethodManagement->set($cartId, $paymentMethod);

        return true;
    }

    /**
     * @param string $shippingCarrier
     * @return bool
     */
    protected function isExistingCarrier(string $shippingCarrier): bool
    {
        $carrierConfig = $this->scopeConfig->getValue('carriers/' . $shippingCarrier, ScopeInterface::SCOPE_STORE);
        return !empty($carrierConfig);
    }
}
