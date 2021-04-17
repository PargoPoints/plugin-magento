<?php


namespace Pargo\CustomShipping\Model\Plugin\Shipping\Rate\Result;

/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */


class GetFilteredRates
{

    /**
     * Remove flagged shipping method
     *
     * @param \Magento\Shipping\Model\Rate\Result $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllRates($subject, $result)
    {
        $subject;
        foreach ($result as $key => $rate) {
            if ($rate->getIsDisabled()) {
                unset($result[$key]);
            }
        }

        return $result;
    }
}
