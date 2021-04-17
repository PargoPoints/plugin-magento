<?php
/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */
namespace Pargo\CustomShipping\Helper;

use Magento\Customer\Model\Session;
use Magento\Persistent\Model\SessionFactory;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timezone;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var Session
     */
    public $session;

    /**
     * @param \Magento\Customer\Model\Session $session
     * @var String
     */
    public $tab = 'carriers';

    /**
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Customer\Model\Session $session
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        SessionFactory $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {

        $this->timezone = $timezone;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get module configuration values from core_config_data
     *
     * @param $setting
     * @return mixed
     */
    public function getConfig($setting)
    {
        return $this->scopeConfig->getValue(
            $this->tab . '/pargo_customshipping/' . $setting,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get different values from core_config_data and decide if custom shipping method is available.
     *
     * @return boolean
     */
    public function isAvailable()
    {
        $date_current = strtotime($this->timezone->formatDate());
        $date_start = strtotime($this->getConfig('date_start'));
        $date_end = strtotime($this->getConfig('date_end'));
        $frequency = $this->getConfig('frequency');
        $day = strtolower(date('D', $date_current));

        /**
         * Check if shipping method is actually enabled
         */
        if (!$this->getConfig('active')) {
            return false;
        }

        /**
         * Check if shipping method should be available for logged in users only
         */
        if ($this->getConfig('customer') && !$this->isCustomerLoggedIn()) {
            return false;
        }

        /**
         * Check if shipping method should be visible in backend, frontend or both
         */
        if ($this->getConfig('availability') == 'backend' && !$this->isAdmin()
            || $this->getConfig('availability') == 'frontend'
            && $this->isAdmin()) {
            return false;
        }

        /**
         * Check if scheduler is enabled
         */
        if ($this->getConfig('scheduler_enabled')) {

            /**
             * Check if shipping method should be visible at current name of day
             */
            if (strpos($frequency, $day) === false) {
                return false;
            }

            /**
             * Check if start date is in range
             */
            if ($date_start && $date_start > $date_current) {
                return false;
            }

            /**
             * Check if start end is in range
             */
            if ($date_end && $date_end < $date_current) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if current user logged in as admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return 'adminhtml' === $this->session->getAreaCode();
    }

    /**
     * Check if current user logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {

        return $this->session->isLoggedIn();
    }

    /**
     * Retrieve API Url
     *
     * @return string
     */
    public function getUrl($storeId = 0)
    {
        return $this->scopeConfig->getValue(
            'carriers/pargo_customshipping/url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve API Username
     *
     * @return string
     */
    public function getUsername($storeId = 0)
    {
        return $this->scopeConfig->getValue(
            'carriers/pargo_customshipping/username',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve API Password
     *
     * @return string
     */
    public function getPassword($storeId = 0)
    {
        return $this->scopeConfig->getValue(
            'carriers/pargo_customshipping/password',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retriece Price Matrix
     *
     * @param int $storeId
     * @return bool|mixed
     */
    public function getPriceMatrix($storeId = 0)
    {
        $priceMatrix = $this->scopeConfig->getValue(
            'carriers/pargo_customshipping/price_matrix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($priceMatrix) {
            return json_decode($priceMatrix, true);
        }

        return false;
    }
}
