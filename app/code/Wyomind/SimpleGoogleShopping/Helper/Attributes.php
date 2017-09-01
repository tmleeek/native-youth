<?php

/**
 * Copyright © 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\SimpleGoogleShopping\Helper;

/**
 * Attributes management
 */
class Attributes extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_attributes = [
        'Wyomind\SimpleGoogleShopping\Helper\AttributesDefault',
        'Wyomind\SimpleGoogleShopping\Helper\AttributesCategories',
        'Wyomind\SimpleGoogleShopping\Helper\AttributesImages',
        'Wyomind\SimpleGoogleShopping\Helper\AttributesInventory',
        'Wyomind\SimpleGoogleShopping\Helper\AttributesPrices',
        'Wyomind\SimpleGoogleShopping\Helper\AttributesStockInTheChannel',
        'Wyomind\SimpleGoogleShopping\Helper\AttributesUrl'
    ];
    protected $_listOfAttributes = [];
    protected $_messageManager = null;
    protected $_objectManager = null;
    private $_as = [];
    public $skipProduct = false;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Eav\Model\Entity\TypeFactory $attributeTypeFactory,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
    
        parent::__construct($context);

        $this->_messageManager = $messageManager;
        $this->_objectManager = $objectManager;

        $typeId = -1;
        $resTypeId = $attributeTypeFactory->create()->getCollection()->addFieldToFilter('entity_type_code', ['eq' => 'catalog_product']);
        foreach ($resTypeId as $re) {
            $typeId = $re['entity_type_id'];
        }
        $attributesList = $attributeFactory->create()->getCollection()->addFieldToFilter('entity_type_id', ['eq' => $typeId]);
        $this->_listOfAttributes = [];
        foreach ($attributesList as $key => $attr) {
            array_push($this->_listOfAttributes, $attr['attribute_code']);
        }
    }

    public function executeAttribute(
        $model,
        $attributeCall,
        $product
    ) {
    
        // check if statements if needed
        if (is_array($attributeCall["parameters"])) {
            if (isset($attributeCall["parameters"]["if"])) {
                $ifResult = true;
                foreach ($attributeCall["parameters"]["if"] as $if) {
                    if (isset($if['alias'])) {
                        $prop = $this->_as[$if['alias']];
                    } elseif (isset($if['object'])) {
                        $item = $model->checkReference($if['object'], $product);
                        $prop = $item->getData($if['property']);
                    } else {
                        $prop = "";
                    }
                    switch ($if['condition']) {
                        case '==':
                            $ifResult &= $prop == $if['value'];
                            break;
                        case '!=':
                            $ifResult &= $prop != $if['value'];
                            break;
                        case '>':
                            $ifResult &= (float) $prop > (float) $if['value'];
                            break;
                        case '<':
                            $ifResult &= (float) $prop < (float) $if['value'];
                            break;
                        case '>=':
                            $ifResult &= (float) $prop >= (float) $if['value'];
                            break;
                        case '<=':
                            $ifResult &= (float) $prop <= (float) $if['value'];
                            break;
                    }
                }
                if (!$ifResult) {
                    return "";
                }
            }
        }


        // retrieve the main value
        $value = $this->proceed($attributeCall, $model, $attributeCall["parameters"], $product);


        if (isset($attributeCall["parameters"]["as"])) {
            $this->_as[$attributeCall["parameters"]["as"]] = $value;
        }
        
        $prefix = (!isset($attributeCall["parameters"]['prefix'])) ? '' : $attributeCall["parameters"]['prefix'];
        $suffix = (!isset($attributeCall["parameters"]['suffix'])) ? '' : $attributeCall["parameters"]['suffix'];

        // apply php
        if (is_array($attributeCall["parameters"])) {
            if (isset($attributeCall["parameters"]["output"])) {
                if ($attributeCall["parameters"]["output"] == "null") {
                    return "";
                }
                if (!is_array($value)) {
                    $toExecute = str_replace('$self', "stripslashes(\"" . addslashes($value) . "\")", $attributeCall["parameters"]["output"]);
                } else {
                    $toExecute = str_replace('$self', '$value', $attributeCall["parameters"]["output"]);
                }
                $value = $this->execPhp("return " . $toExecute . ";", $product);
            }
        }
        if (is_array($value)) {
            $value = implode(",", $value);
        }

        $value = ($value != "") ? ($prefix . $value . $suffix) : $value;
        
        return $value;
    }

    public function execPhp(
        $script,
        $product = null
    ) {
    
        foreach ($this->_as as $key => $value) {
            $$key = $value;
        }
        return eval($script);
    }
    
    public function skip($skip = true)
    {
        $this->skipProduct = $skip;
    }
    
    public function getSkip()
    {
        return $this->skipProduct;
    }

    /**
     * Execute inline php scripts
     * @param type $output
     * @param type $product
     * @return type
     * @throws \Exception
     */
    public function executePhpScripts(
        $preview,
        $output,
        $product
    ) {
    
        
        if ($output == null) {
            return;
        }

        $matches = [];
        preg_match_all("/(?<script><\?php(?<php>.*)\?>)/sU", $output, $matches);

        $i = 0;
        foreach (array_values($matches["php"]) as $phpCode) {
            $val = null;

            $displayErrors = ini_get("display_errors");
            ini_set("display_errors", 0);

            if (($val = $this->execPhp($phpCode, $product)) === false) {
                if ($preview) {
                    ini_set("display_errors", $displayErrors);
                    throw new \Exception("Syntax error in " . $phpCode . " : " . error_get_last()["message"]);
                } else {
                    ini_set("display_errors", $displayErrors);
                    $this->_messageManager->addError("Syntax error in <i>" . $phpCode . "</i><br>." . error_get_last()["message"]);
                    throw new \Exception();
                }
            }
            ini_set("display_errors", $displayErrors);

            if (is_array($val)) {
                $val = implode(",", $val);
            }
            $output = str_replace($matches["script"][$i], $val, $output);
            $i++;
        }
        return $output;
    }

    public function isProductAttribute($attribute)
    {
        return in_array($attribute, $this->_listOfAttributes);
    }

    public function proceed(
        $attributeCall,
        $model,
        $options,
        $product
    ) {
    

        $reference = $attributeCall['object'];
        $ignore = ['status', 'price', 'special_price', 'tier_price', 'visibility'];
        if ($this->isProductAttribute($attributeCall['property']) && !in_array($attributeCall['property'], $ignore)) {
            return $this->productAttribute($model, $attributeCall['property'], $product, $reference);
        } else {
            $exploded = explode('_', $attributeCall['property']);
            $method = "";
            foreach ($exploded as $x) {
                $method .= ucfirst(strtolower($x));
            }
            $method = lcfirst($method);
            foreach ($this->_attributes as $library) {
                if (method_exists($library, $method)) {
                    return $this->_objectManager->get($library)->$method($model, $options, $product, $reference);
                }
            }
        }
        return false;
    }

    /**
     * All other attributes processing
     * @param \Wyomind\Simplegoogleshopping\Model\Feeds $model
     * @param \Magento\Catalog\Model\Product            $product
     * @param string                                    $reference
     * @return string the attribute value
     */
    public function productAttribute(
        $model,
        $attribute,
        $product,
        $reference
    ) {
    
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return "";
        }
        $exploded = explode('_', $attribute);
        $method = "";
        foreach ($exploded as $x) {
            $method .= ucfirst(strtolower($x));
        }
        $methodName = "get" . str_replace(' ', '', ucfirst(trim($method)));
        if (in_array($attribute, $model->listOfAttributes)) {
            if (in_array($model->listOfAttributesType[$attribute], ['select', 'multiselect'])) {
                $val = $item->$methodName();
                $vals = explode(',', $val);
                /* multiselect */
                if (count($vals) > 1) {
                    $value = [];
                    foreach ($vals as $v) {
                        if (isset($model->attributesLabelsList[$v][$model->params['store_id']])) {
                            $value[] = $model->attributesLabelsList[$v][$model->params['store_id']];
                        } else {
                            if (isset($model->attributesLabelsList[$v][0])) {
                                $value[] = $model->attributesLabelsList[$v][0];
                            }
                        }
                    }
                } else { /* select */
                    if (isset($model->attributesLabelsList[$vals[0]][$model->params['store_id']])) {
                        $value = $model->attributesLabelsList[$vals[0]][$model->params['store_id']];
                    } else {
                        if (isset($model->attributesLabelsList[$vals[0]][0])) {
                            $value = $model->attributesLabelsList[$vals[0]][0];
                        }
                    }
                }
            } else {
                $value = $item->$methodName();
            }
        }
        /* Recuperer une valeur de taux de change */
        if (isset($model->listOfCurrencies[$attribute])) {
            $value = $model->listOfCurrencies[$attribute];
        }

        if (!isset($value)) {
            $value = "";
        }

        /* enlever les caractères invalides */
        $valueCleaned = preg_replace(
            '/' .
                '[\x00-\x1F\x7F]' .
                '|[\x00-\x7F][\x80-\xBF]+' .
                '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
                '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
                '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|' .
                '(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})' .
                '/S',
            ' ',
            $value
        );
        $value = str_replace('&#153;', '', $valueCleaned);

        return $value;
    }

    /**
     * Compare two arrays
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function cmpArray(
        $a,
        $b
    ) {
    
        if (strlen(implode('', $a)) == strlen(implode('', $b))) {
            return 0;
        }
        return (strlen(implode('', $a)) < strlen(implode('', $b))) ? -1 : 1;
    }

    /**
     * Compare two strings
     * @param string $a
     * @param string $b
     * @return int
     */
    public static function cmp(
        $a,
        $b
    ) {
    
        if (strlen($a) == strlen($b)) {
            return 0;
        }
        return (strlen($a) < strlen($b)) ? 1 : -1;
    }
}
