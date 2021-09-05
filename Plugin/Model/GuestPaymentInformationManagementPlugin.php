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
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestPaymentMethodManagementInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote;

class GuestPaymentInformationManagementPlugin
{

    /**
     * @var GuestPaymentMethodManagementInterface
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
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * PaymentInformationManagement constructor.
     * @param GuestPaymentMethodManagementInterface $paymentMethodManagement
     * @param ScopeConfigInterface $scopeConfig
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        GuestPaymentMethodManagementInterface $paymentMethodManagement,
        ScopeConfigInterface $scopeConfig,
        CartRepositoryInterface $cartRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->scopeConfig = $scopeConfig;
        $this->cartRepository = $cartRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
     * @param callable $proceed
     * @param integer $cartId
     * @param string $email
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\State\InvalidTransitionException
     */
    public function aroundSavePaymentInformation(
        \Magento\Checkout\Model\GuestPaymentInformationManagement $subject,
        callable $proceed,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var Quote $quote */
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

        if ($billingAddress) {
            $shippingAddress = $quote->getShippingAddress();

            if ($shippingAddress->getShippingMethod() !== 'pargo_customshipping_pargo_customshipping') {
                $proceed($cartId, $email, $paymentMethod, $billingAddress);
                return true;
            }

            $billingAddress->setEmail($email);
            $quote->removeAddress($quote->getBillingAddress()->getId());
            $quote->setBillingAddress($billingAddress);
            $quote->setDataChanges(true);
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setLimitCarrier('pargo_customshipping');
        } else {
            $quote->getBillingAddress()->setEmail($email);
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
