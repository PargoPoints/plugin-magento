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



use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\ProductMetadataInterface;
// Used for secure renderer for version 2.3.5 do not use outside of version check
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Helper\InlineUtil;
// Used for secure renderer for version 2.4.0 and higher do not use outside of version check
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRender\HtmlRenderer;
use Magento\Framework\Escaper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class PargoData extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var mixed
     */
    public $secureRenderer;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param ProductMetadataInterface $productMetadata
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context,
        ProductMetadataInterface $productMetadata,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->productMetadata = $productMetadata;
        // Choosing the correct CSP method as per Magento Version
        if(version_compare($this->productMetadata->getVersion(), "2.4.0", ">="))
        {
            $secureRenderer = new SecureHtmlRenderer(new HtmlRenderer(new Escaper()), new Random());
        } else {
            $secureRenderer = new InlineUtil(new DynamicCollector());
        }
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
     * Function to return the map url, choosing between live or staging dependent on the admin live dropdown
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
        $returnString = $this->secureRenderer->renderTag('script', ['src' => $this->getMapUrl() . '/assets/pargo-map.full.min.js?v=fe9a11fa']);
        $returnString .= $this->secureRenderer->renderTag('script', ['src' => $this->getMapUrl() . '/assets/app.js?v=fe9a11fa']);

        return $returnString;
    }

}
