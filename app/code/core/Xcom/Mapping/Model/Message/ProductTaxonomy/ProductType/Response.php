<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Xcom
 * @package     Xcom_Mapping
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
    extends Xcom_Xfabric_Model_Message_Response
{
    /**
     * @var string
     */
    protected $_defaultLocaleCode = 'en_US';
    protected $_localeCode;
    protected $_eventPrefix = '';

    /**
     * Message will be processed later to reduce the time on receiving
     * @var bool
     */
    protected $_isProcessLater = true;

    /**
     * Save product types.
     *
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
     */
    public function process()
    {
        //since this process is delayed event prefix has to be set here
        $this->_eventPrefix = 'response_message';
        Mage::log('started processing message: ' . $this->getTopic());
        parent::process();
        $data = $this->getBody();
        if (isset($data['productTypes'])) {
            $this->saveProductTypes($data['productTypes']);
        }
        Mage::log('finished processing message: ' . $this->getTopic());

        return $this;
    }

    /**
     * return an array of product types with product_type_id as index
     * @return array
     */
    public function getProductTypeMap()
    {
        $map = array();
        $productTypes = Mage::getResourceModel('xcom_mapping/product_type')
            ->getAllProductType();
        foreach ( $productTypes as $productType ) {
            $map[$productType['product_type_id']] = $productType;
        }
        return $map;
    }

    /**
     * Store Product Type data to the database.
     * Calls Attribute persist operation in case 'attributes' parameter has data.
     * If attribute is up to date, we assume that we are only updating locale
     * information of attributes attribute values
     *
     * @param array $data
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
     */
    public function saveProductTypes(array $data)
    {
        //$this->_deleteProductTypes($data);
        $productTypeMap = $this->getProductTypeMap();
        foreach ($data as $productTypeData) {
            $productTypeId = $productTypeData['id'];
            /** @var $productType Xcom_Mapping_Model_Product_Type */
            // set locale in resource, this is hacky
            //Mage::getResourceModel('xcom_mapping/product_type')
            //    ->setLocaleCode($this->getLocaleCode());
            $productType = Mage::getModel('xcom_mapping/product_type')
                ->setLocaleCode($this->getLocaleCode())
                ->load($productTypeId, 'product_type_id');
            if ( $productType->getLocaleCode() != $this->getLocaleCode()) {
                // unset locale id
                $productType->setLocaleId(null);
            }

            if (!empty($productTypeData['attributes'])) {
                //check whether the attribute for the product type is up to date
                $attributeUpdated = false;
                if ( !array_key_exists($productTypeId, $productTypeMap)
                    || $productTypeMap[$productTypeId]['version'] != $productTypeData['version']) {
                    $attributeUpdated = false;
                }
                else {
                    $attributeUpdated =
                        ($productTypeMap[$productTypeId]['status']==
                            Xcom_Mapping_Model_Product_Type::PRODUCT_TYPE_STATUS_UPDATED);
                }
                $productType->setStatus(Xcom_Mapping_Model_Product_Type::PRODUCT_TYPE_STATUS_UPDATED);
                $this->saveProductType($productType, $productTypeData);
                if ( !$attributeUpdated ) {
                    $this->saveAttributes($productType,
                        $productTypeData['attributes']);
                }
                else {
                    $this->saveAttributesLocale($productType,
                        $productTypeData['attributes']);
                }
            }
            else {
                $productType->setIsAttributeUpdated(false);
                $productType->save();
            }
        }
        return $this;
    }

    /**
     * Clean product types from database which are not present in given $data array.
     *
     * @param array $data
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
     */
    protected function _deleteProductTypes(array $data)
    {
        $productTypeIds = $this->_collectIds($data);

        $oldIds = Mage::getSingleton('xcom_mapping/product_type')->getCollection()
            ->addFieldToFilter('product_type_id', array('nin' => $productTypeIds))
            ->setLocaleCode($this->getLocaleCode())
            ->getAllIds();

        Mage::getSingleton('xcom_mapping/product_type')->deleteByIds($oldIds);
        return $this;
    }

    /**
     * Collect all ids from response.
     *
     * @param array $data
     * @param string $indexName
     * @return array
     */
    protected function _collectIds(array $data, $indexName = 'id')
    {
        $result = array();
        foreach ($data as $item) {
            $result[] = $item[$indexName];
        }
        return $result;
    }

    public function saveProductType(Mage_Core_Model_Abstract $productType, array $data)
    {
        $productTypeData = array(
            'product_type_id'   => $data['id'],
            'version'           => $data['version'],
            'name'              => $data['name'],
            'description'       => $data['description'],
            'product_class_ids' => $data['productClassIds'],
            'status'            => Xcom_Mapping_Model_Product_Type::PRODUCT_TYPE_STATUS_UPDATED,
            'locale_code'       => $this->getLocaleCode()
        );
        $productType->addData($productTypeData)
            ->save();

        return $this;
    }

    public function saveAttributes(Varien_Object $productType, array $attributes)
    {
        $this->_deleteAttributes($productType, $attributes);
        foreach ($attributes as $attributeData) {
            /** @var $attribute Xcom_Mapping_Model_Attribute */
            $attribute = Mage::getModel('xcom_mapping/attribute');

            $mappingAttributeId = Mage::getModel('xcom_mapping/attribute')->getCollection()
                ->addFieldToFilter('mapping_product_type_id', $productType->getId())
                ->addFieldToFilter('attribute_id', $attributeData['id'])
                ->getFirstItem()
                ->getId();

            if ($mappingAttributeId) {
                $attribute->load($mappingAttributeId);
            }

            $this->saveAttribute($productType, $attribute, $attributeData);
            //if ( $attribute->getIsRestricted() ) {
                $this->saveAttributeValues($attribute, $attributeData);
            //}
        }

        return $this;
    }

    /**
     * return attributes for a given product type in an array indexed by attribute_id
     * @param $mappingProductTypeId
     * @return array
     */
    public function loadAttributeByProductTypeId($mappingProductTypeId)
    {
        $map = array();
        $attributes = Mage::getResourceModel('xcom_mapping/attribute')
            ->getAttributesForProductType($mappingProductTypeId);
        foreach ( $attributes as $attribute ) {
            $map[$attribute['attribute_id']] = $attribute;
        }
        return $map;
    }

    public function saveAttributesLocale(Varien_Object $productType, array $attributes)
    {
        $attributeMap = $this->loadAttributeByProductTypeId($productType->getId());
        foreach ($attributes as $attributeData) {
            //get mapping_attribute_id from attribute_id
            if ( !array_key_exists($attributeData['id'], $attributeMap)) {
                Mage::log('Attribute with id ' . $attributeData['id'] . ' does not exist');
                continue;
            }
            $existingAttributeData = $attributeMap[$attributeData['id']];
            $attributeData['mapping_attribute_id'] = $existingAttributeData['mapping_attribute_id'];

            $this->_saveAttributeLocale($attributeData);
            //if ( $existingAttributeData['is_restricted'] ) {
                $this->_saveAttributeValuesLocale($attributeData);
            //}
        }

        return $this;
    }

    protected function _saveAttributeLocale($attributeLocale)
    {
        Mage::getResourceModel('xcom_mapping/attribute')->saveAttributeLocale(
            $attributeLocale['mapping_attribute_id'], $attributeLocale['name'],
            $attributeLocale['description'], $this->getLocaleCode()
        );
        return $this;
    }

    protected function _getAttributeValueData($attributeData)
    {
        $attributeValueData = array();
        if ($this->isStringValues($attributeData)) {
            $attributeValueData['values'] = $attributeData['recommendedValues'];
            $attributeValueData['nameKey'] = 'localizedValue';
            $attributeValueData['valueIdKey'] = 'valueId';
        } elseif ($this->isEnumerationValues($attributeData)) {
            $attributeValueData['values'] = $attributeData['enumerators'];
            $attributeValueData['nameKey'] = 'name';
            $attributeValueData['valueIdKey'] = 'id';
        } elseif ($this->isBooleanValues($attributeData)) {
            $attributeValueData['values'] = array(
                array('valueId' => -1, 'name' => 'True', 'channelId' => null),
                array('valueId' => -2, 'name' => 'False', 'channelId' => null),
            );
            $attributeValueData['nameKey'] = 'name';
            $attributeValueData['valueIdKey'] = 'id';
        }
        return $attributeValueData;
    }


    protected function _saveAttributeValuesLocale($attributeData)
    {
        //find attribute value ids
        $attrValueResource =
            Mage::getResourceModel('xcom_mapping/attribute_value');
        //load existing attribute value for the attribute
        //index the attribute value using value_id
        $attributeValues = $attrValueResource
            ->getByAttributeId($attributeData['mapping_attribute_id']);
        $attributeValueMap = array();
        foreach ( $attributeValues as $attributeValue ) {
            $attributeValueMap[$attributeValue['value_id']] = $attributeValue;
        }

        //delete existing value locale for current locale
        $mappingValueIds = $this->_collectIds($attributeValues, 'mapping_value_id');
        $attrValueResource->deleteByIdsAndLocale(
            $mappingValueIds, $this->getLocaleCode());

        //save value locale records
        $attributeValueData = $this->_getAttributeValueData($attributeData);
        foreach ( $attributeValueData['values'] as $attributeValue ) {
            $valueId = $attributeValue[$attributeValueData['valueIdKey']];
            $localizedName = $attributeValue[$attributeValueData['nameKey']];
            if ( !array_key_exists($valueId, $attributeValueMap)) {
                Mage::log('Attribute value id ' . $valueId . ' for attribute '
                    . $attributeData['mapping_attribute_id']
                    . 'does not exist, check taxonomy data' );
                continue;
            }
            $mappingValueId = $attributeValueMap[$valueId]['mapping_value_id'];
            $attrValueResource->saveAttributeValueLocale($mappingValueId, $localizedName, $this->getLocaleCode());
        }
    }

    protected function _deleteAttributes(Varien_Object $productType, array $attributes)
    {
        $attributeIds = $this->_collectIds($attributes);

        $oldIds = Mage::getSingleton('xcom_mapping/attribute')->getCollection()
            ->addFieldToFilter('attribute_id', array('nin' => $attributeIds))
            ->addFieldToFilter('mapping_product_type_id', $productType->getId())
            ->setLocaleCode($this->getLocaleCode())
            ->getAllIds();

        Mage::getSingleton('xcom_mapping/attribute')->deleteByIds($oldIds);
        return $this;
    }

    public function saveAttribute(Varien_Object $productType, Varien_Object $attribute, $data)
    {
        $channelDecoration = array();
        if (!is_null($data['channelAttributeDecorations'])) {
            foreach ($data['channelAttributeDecorations'] as $decoration) {
                $channelDecoration[] = array(
                    'channel_code'    => $decoration['channelId'],
                    'is_required'   => $decoration['required'],
                    'is_variation'  =>
                    !is_null ($decoration['supportsVariation']) ? $decoration['supportsVariation'] : false
                );
            }
        }

        $info = array(
            'attribute_id'         => $data['id'],
            'mapping_product_type_id' => (int)$productType->getId(),
            'name'                 => $data['name'],
            'channel_decoration'   => $channelDecoration,
            'description'          => $data['description'],
            'is_multiselect'       => isset($data['allowMultipleValues']) ? (bool)$data['allowMultipleValues'] : null,
            'default_value_ids'    => $data['defaultValue'],
            'locale_code'          => $this->getLocaleCode(),
            'is_restricted'        => $this->isStringValues($data) ? 0 : 1
        );

        if ($this->isStringValues($data)) {
            $info['attribute_type'] = Xcom_Mapping_Model_Attribute::ATTR_TYPE_STRING;
        } elseif ($this->isEnumerationValues($data)) {
            $info['attribute_type'] = Xcom_Mapping_Model_Attribute::ATTR_TYPE_ENUM;
        } elseif ($this->isBooleanValues($data)) {
            $info['attribute_type'] = Xcom_Mapping_Model_Attribute::ATTR_TYPE_BOOL;
        }

        $attribute->addData($info)
            ->save();

        return $this;
    }

    /**
     * @param Varien_Object $attribute
     * @param array $attributeData
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
     */
    public function saveAttributeValues(Varien_Object $attribute, array $attributeData)
    {
        $attributeValueData = $this->_getAttributeValueData($attributeData);
        $values = $attributeValueData['values'];
        $nameKey = $attributeValueData['nameKey'];
        $valueIdKey = $attributeValueData['valueIdKey'];
        if (isset($values) && isset($nameKey) && isset($valueIdKey)) {
            $this->_deleteAttributeValues($attribute, $values, $valueIdKey);
            foreach ($values as $attrValues) {
                $this->saveAttributeValueData($attribute, $attrValues, $valueIdKey, $nameKey);
            }
        }

        return $this;
    }

    /**
     * Delete all attribute values from database which aren't in the $values array.
     *
     * @param Varien_Object $attribute
     * @param array $values
     * @param string $valueIdKey
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
     */
    protected function _deleteAttributeValues(Varien_Object $attribute, array $values, $valueIdKey)
    {
        $attributeValueIds = $this->_collectIds($values, $valueIdKey);

        $oldIds = Mage::getSingleton('xcom_mapping/attribute_value')->getCollection()
            ->addFieldToFilter('value_id', array('nin' => $attributeValueIds))
            ->addFieldToFilter('mapping_attribute_id', $attribute->getId())
            ->setLocaleCode($this->getLocaleCode())
            ->getAllIds();

        Mage::getSingleton('xcom_mapping/attribute_value')->deleteByIds($oldIds);
        return $this;
    }

    /**
     * Save attribute values to the database.
     *
     * @param Varien_Object $attribute
     * @param array $data
     * @param string $valueIdKey
     * @param string $nameKey
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
     */
    public function saveAttributeValueData(Varien_Object $attribute, array $data, $valueIdKey = 'id', $nameKey = 'name')
    {
        $channelCodes = array();
        if (!is_null($data['channelValueDecorations'])) {
            foreach ($data['channelValueDecorations'] as $decoration) {
                $channelCodes[] = $decoration['channelId'];
            }
        }
        $attributeValueData = array(
            'mapping_attribute_id'  => $attribute->getId(),
            'channel_codes'         => $channelCodes,
            'value_id'              => $data[$valueIdKey], // <-- string !!!
            'name'                 => $data[$nameKey],
            'locale_code'           => $this->getLocaleCode(),
        );

        $attributeValue = Mage::getModel('xcom_mapping/attribute_value');

        $mappingAttributeValueId = $attributeValue->getCollection()
            ->addFieldToFilter('mapping_attribute_id', $attribute->getId())
            ->addFieldToFilter('value_id', $data[$valueIdKey])
            ->getFirstItem()
            ->getId();

        if ($mappingAttributeValueId) {
            $attributeValue->load($mappingAttributeValueId);
        }

        $attributeValue->addData($attributeValueData)
            ->save();

        return $this;
    }

    /**
     * Check is string type values via checking exist "recommendedValues" key
     *
     * @param array $data
     * @return bool
     */
    public function isStringValues(array &$data)
    {
        return isset($data['recommendedValues']);
    }

    /**
     * Check is enumeration type values via checking exist "enumerators" key
     *
     * @param array $data
     * @return bool
     */
    public function isEnumerationValues(array &$data)
    {
        return isset($data['enumerators']);
    }

    /**
     * Check is boolean type values via checking type bool "defaultValue"
     *
     * @param array $data
     * @return bool
     */
    public function isBooleanValues(array &$data)
    {
        return is_bool($data['defaultValue']);
    }

    /**
     * Returns locale code received from message.
     *
     * @return string
     */
    public function getLocaleCode()
    {
        if (null === $this->_localeCode) {
            $this->_prepareLocaleCode();
        }
        return $this->_localeCode;
    }

    /**
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_ProductType_Response
     */
    protected function _prepareLocaleCode()
    {
        $data = $this->getBody();
        if (!empty($data['locale']['country']) && !empty($data['locale']['language'])) {
            $this->_localeCode = $data['locale']['language'] . '_' . $data['locale']['country'];
        } else {
            $this->_localeCode = $this->_defaultLocaleCode;
        }
        return $this;
    }
}
