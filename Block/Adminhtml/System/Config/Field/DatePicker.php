<?php
/**
 * Pargo CustomShipping
 *
 * @category    Pargo
 * @package     Pargo_CustomShipping
 * @copyright   Copyright (c) 2018 Pargo Points (https://pargo.co.za)
 * @license     http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace Pargo\CustomShipping\Block\Adminhtml\System\Config\Field;

/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */

class DatePicker extends \Magento\Config\Block\System\Config\Form\Field
{

    public $template = 'system/config/field/datepicker.phtml';

    /**
     * Return element html
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData([
            'html_id' => $element->getHtmlId()
        ]);

        return $element->getElementHtml() . $this->toHtml();
    }
}
