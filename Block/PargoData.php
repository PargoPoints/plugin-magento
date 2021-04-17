<?php

namespace Pargo\CustomShipping\Block;

class PargoData extends \Magento\Framework\View\Element\Template
{
    public $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;

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
