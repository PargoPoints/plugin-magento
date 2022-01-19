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

namespace Pargo\CustomShipping\Block;

use Magento\Csp\Helper\InlineUtil;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class PargoData extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var SecureHtmlRenderer
     */
    public $secureRenderer;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param SecureHtmlRenderer $secureRenderer
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context,
        SecureHtmlRenderer $secureRenderer, // \Magento\Csp\Helper\InlineUtil $secureRenderer, older magento 2.3.5
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->secureRenderer = $secureRenderer;

        parent::__construct($context, $data);
    }

    /**
     * Gets the content for the shipping token
     * @return mixed
     */
    public function getMapToken()
    {
        return $this->scopeConfig->getValue(
            'carriers/pargo_customshipping/mapToken',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Function to return the map url, chosing between live or staging dependant on the admin live dropdown
     * @return mixed
     */
    public function getMapUrl()
    {
        if ($this->isLive()){
            return $this->scopeConfig->getValue(
                'carriers/pargo_customshipping/live_map_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        } else {
            return $this->scopeConfig->getValue(
                'carriers/pargo_customshipping/staging_map_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
        }

    }

    /**
     * Function to determine if a live or staging site, based on dropdown in admin
     * @return mixed
     */
    public function isLive()
    {
        return $this->scopeConfig->getValue(
            'carriers/pargo_customshipping/live_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Function that builds the map iframe. Selecting the url, token and creating an exception policy for the xsite link.
     * @return string
     */
    public function getMapIFrame()
    {
        $returnString = $this->secureRenderer->renderTag('iframe', ['id' => 'iframe',
                                                                            'class' => 'resp-iframe',
                                                                            'allow' => 'geolocation',
                                                                            'src' => $this->getMapUrl() . '/?token=' . $this->getMapToken()]);

        return $returnString;
    }

    /**
     * Function that builds the map scripts. Selecting the urland creating an exception policy for the xsite link.
     * @return string
     */
    public function getMapScript()
    {
        $returnString = $this->secureRenderer->renderTag('script', ['src' => $this->getMapUrl() . 'assets/pargo-map.full.min.js?v=fe9a11fa']);
        $returnString .= $this->secureRenderer->renderTag('script', ['src' => $this->getMapUrl() . 'assets/app.js?v=fe9a11fa']);

        return $returnString;
    }

}
