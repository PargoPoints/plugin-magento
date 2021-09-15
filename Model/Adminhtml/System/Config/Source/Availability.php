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

namespace Pargo\CustomShipping\Model\Adminhtml\System\Config\Source;

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
