<?php
namespace Pargo\CustomShipping\Model\Adminhtml\System\Config\Source;

/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */


class Weekdays implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var Array
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
