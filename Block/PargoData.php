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
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class PargoData extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /** @var SecureHtmlRenderer */
    public $secureRenderer;


    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context,
        SecureHtmlRenderer $secureRenderer, // \Magento\Csp\Helper\InlineUtil $secureRenderer, older magento 2.5.3
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->secureRenderer = $secureRenderer;

        parent::__construct($context, $data);
    }

    public function getContent()
    {
        return $this->scopeConfig->getValue(
            'carriers/pargo_customshipping/mapToken',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
