<?php

namespace Pargo\CustomShipping\Model\Adminhtml\System\Config\Source;

/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */


class Availability implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return array of options
     *
     * @return array
     */
    public function toOptionArray()
    {

        return [
            ['value' => 'both', 'label' => __('Backend / Frontend')],
            ['value' => 'backend', 'label' => __('Backend only')],
            ['value' => 'frontend', 'label' => __('Frontend only')]
        ];
    }
}
