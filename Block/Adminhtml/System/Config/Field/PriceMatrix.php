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

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * @category   Pargo
 * @package    Pargo_CustomShipping
 * @author     imtiyaaz.salie@pargo.co.za
 * @website    https://pargo.co.za
 */

class PriceMatrix extends AbstractFieldArray
{

    /**
     * Grid columns
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Prepare to render
     *
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'from',
            [
                'label' => __('From'),
            ]
        );
        $this->addColumn(
            'to',
            [
                'label' => __('To'),
            ]
        );
        $this->addColumn(
            'price',
            [
                'label' => __('Price'),
            ]
        );

        $this->addButtonLabel = __('Add');
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param  string $columnName
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        $this->_columns[$columnName]['class'] = 'required-entry';
        $this->_columns[$columnName]['style'] = 'width:75px';

        return parent::renderCellTemplate($columnName);
    }
}
