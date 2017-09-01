<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_CustomerAttributes
 */

namespace Amasty\CustomerAttributes\Block\Adminhtml\Customer\Attribute\Edit\Tab\Options;

use Magento\Store\Model\ResourceModel\Store\Collection;

class Options extends \Magento\Eav\Block\Adminhtml\Attribute\Edit\Options\Options
{
    /**
     * @var \Amasty\CustomerAttributes\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var string
     */
    protected $_template = 'attribute/options.phtml';

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        array $data = [],
        \Amasty\CustomerAttributes\Helper\Image $imageHelper
    ) {
        parent::__construct($context, $registry, $attrOptionCollectionFactory, $universalFactory, $data);
        $this->_imageHelper = $imageHelper;
    }

    public function getUseImages() {
        return ('multiselectimg' == $this->getAttributeObject()->getFrontendInput()
            || 'selectimg' == $this->getAttributeObject()->getFrontendInput()
        );
    }

    /**
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
     * @param array|\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection $optionCollection
     * @return array
     */
    protected function _prepareOptionValues(
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute,
        $optionCollection
    ) {
        $type = $attribute->getFrontendInput();
        if ($type === 'select' || $type === 'selectgroup') {
            $defaultValues = explode(',', $attribute->getDefaultValue());
            $inputType = 'radio';
        }
        else if($type === 'multiselect' || $type === 'multiselectimg') {
            $defaultValues = explode(',', $attribute->getDefaultValue());
            $inputType = 'checkbox';
        }
        else{
            $defaultValues = [];
            $inputType = '';
        }

        $values = [];
        $isSystemAttribute = is_array($optionCollection);
        foreach ($optionCollection as $option) {
            $bunch = $isSystemAttribute ? $this->_prepareSystemAttributeOptionValues(
                $option,
                $inputType,
                $defaultValues
            ) : $this->_prepareUserDefinedAttributeOptionValues(
                $option,
                $inputType,
                $defaultValues
            );
            foreach ($bunch as $value) {
                /*Amasty code for adding icon*/
                $value['icon'] = $this->_imageHelper->getIconUrl($value['id']);
                $values[] = new \Magento\Framework\DataObject($value);
            }
        }

        return $values;
    }
}
