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

class Weekdays implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    public $data = [];

    /**
     * Get weekdays
     *
     * @return array
     */
    public function getWeekdays()
    {
        return $this->data = [
            'mon' => __('Monday'),
            'tue' => __('Tuesday'),
            'wed' => __('Wednesday'),
            'thu' => __('Thursday'),
            'fri' => __('Friday'),
            'sat' => __('Saturday'),
            'sun' => __('Sunday')
        ];
    }

    /**
     * Return array of options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $data = [];

        foreach ($this->getWeekdays() as $key => $value) {
            $data[] = [
                'value' => $key,
                'label' => $value
            ];
        }

        return $data;
    }
}
