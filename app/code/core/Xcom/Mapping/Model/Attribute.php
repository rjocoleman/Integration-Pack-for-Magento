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
class Xcom_Mapping_Model_Attribute extends Mage_Core_Model_Abstract
{
    /**
     * Attribute type identifier for 'string' type
     */
    const ATTR_TYPE_STRING  = 'string';

    /**
     * Attribute type identifier for 'enumeration' type
     */
    const ATTR_TYPE_ENUM    = 'enumeration';

    /**
     * Attribute type identifier for 'boolean' type
     */
    const ATTR_TYPE_BOOL    = 'boolean';

    public function _construct()
    {
        $this->_init('xcom_mapping/attribute');
    }

    /**
     * Save relation
     *
     * @param int $relationProductTypeId
     * @param int $attributeId
     * @param int $mappingAttributeId
     * @return int
     */
    public function saveRelation($relationProductTypeId, $attributeId, $mappingAttributeId = null)
    {
        return $this->getResource()
            ->saveRelation((int)$relationProductTypeId, (int)$attributeId, $mappingAttributeId);
    }
    /**
     * Get Relation Attribute Id
     *
     * @param int $relationProductTypeId
     * @param int $attributeId
     * @param int $mappingAttributeId
     * @return int
     */
    public function getRelationAttributeId($relationProductTypeId, $attributeId, $mappingAttributeId = null)
    {
        return $this->getResource()
            ->getRelationAttributeId((int)$relationProductTypeId, (int)$attributeId, $mappingAttributeId);
    }

    /**
     * Delete attributes relation
     *
     * @param array $relationAttributeIds
     * @return Xcom_Mapping_Model_Attribute
     */
    public function deleteRelation($relationAttributeIds)
    {
        $this->getResource()->deleteRelation($relationAttributeIds);
        return $this;
    }

    /**
     * Get mapping array for attributes
     *
     * @param $attributeSetId
     * @param null $localeCode
     * @return mixed
     */
    public function getSelectAttributesMapping($attributeSetId, $localeCode = null)
    {
        return $this->getCollection()
            ->setLocaleCode($localeCode)
            ->initAttributeRelations($attributeSetId)
            ->addSelectOnlyFilter()
            ->getCollectionData();
    }

    /**
     * Retrieve text attributes
     *
     * @param $attributeSetId
     * @param null $localeCode
     * @return mixed
     */
    public function getTextAttributesMapping($attributeSetId, $localeCode = null)
    {
        return $this->getCollection()
            ->setLocaleCode($localeCode)
            ->initAttributeRelations($attributeSetId)
            ->addTextOnlyFilter()
            ->getCollectionData();
    }

    /**
     * @param array $ids
     * @return Xcom_Mapping_Model_Product_Class
     */
    public function deleteByIds(array $ids)
    {
        $this->_getResource()->deleteByIds($ids);
        return $this;
    }


    public function getMappedMagentoAttributes($attributeSetId, $mappingProductTypeId) {
        list($ret, $partialRet ) = $this->getMappedMagentoAllAttributes($attributeSetId, $mappingProductTypeId) ;
        return $ret;
    }

    /**
     * given attributeSetId, return all attribute mapping for the attributeSet
     * @param $attributeSetId
     * @return array of relation_attribute_id, attribute_id and mapping_attribute_id
     */
    public function getAttributeMapping($attributeSetId)
    {
        return $this->getResource()->getAttributeMapping($attributeSetId);

    }

    /**
     * check whether an attribute is mapped to "CustomAttribute" or
     * mapping_attribute_id = null
     * @param $attributeMappings
     * @param $attributeId
     * @return bool
     */
    public function isMappedToCustomAttribute($attributeMappings, $attributeId)
    {
        foreach ($attributeMappings as $mapping)  {
            if ( $mapping['attribute_id'] == $attributeId) {
                return ($mapping['mapping_attribute_id'] == null);
            }
        }
        return false;
    }
    /**
     * Returns Mapped Magento attributes by attribute set
     *
     * @param int $attributeSetId
     * @return array
     */
    public function getMappedMagentoAllAttributes($attributeSetId, $mappingProductTypeId)
    {

        // get all attributes
        $collection = $this->getMagentoAttributesCollection($attributeSetId);
        $allAttributes = array();
        foreach ($collection as $item) {
            if ($this->isUserDefinedAttribute($item)) {
                $allAttributes[$item->getAttributeId()] = array(
                    'value' => $item->getAttributeId(),
                    'label' => sprintf($item->getFrontendLabel()),
                    'attrvalues' => Mage::helper('xcom_mapping')->getAttributeOptionsHash($item->getAttributeId()),
                    'attrtype' => Mage::helper('xcom_mapping')->getAttributeType($item),
                );
            }
        }

           // get all unmapped attributes
        $relation = Mage::getModel('xcom_mapping/relation');
        $unmappedCollection = $this->getMagentoAttributesCollection($attributeSetId);
        $relation->addFilterOnlyMappedAttributes($unmappedCollection, $attributeSetId);
        $unMappedAttributes = array();

        foreach ($unmappedCollection as $item) {
            if($this->isUserDefinedAttribute($item)) {
            $unMappedAttributes[$item->getAttributeId()] = array(
                'value' => $item->getAttributeId(),
                'label' => sprintf($item->getFrontendLabel()),
                'attrvalues' => Mage::helper('xcom_mapping')->getAttributeOptionsHash($item->getAttributeId()),
                'attrtype' => Mage::helper('xcom_mapping')->getAttributeType($item),
        );

            }
        }

        // find all attributes that are mapped
        $unMappedIds = array();
        foreach ($unMappedAttributes as $item) {
            $unMappedIds[] = $item['value'];
        }
        $options = array();
        foreach ($allAttributes as $key => $item) {
            if (!in_array($item['value'], $unMappedIds)) {
                $options[$key] = array(
                    'value' => $item['value'],
                    'label' => $item['label'],
                    'attrvalues' => Mage::helper('xcom_mapping')->getAttributeOptionsHash($item['value']),
                    'attrtype' => $item['attrtype'],
            );
            }
        }

        // delete those mapped attribute whose values are not completely mapped
        // get attribute mappings so we can tell which attributes are mapped to custom attribute
        $attributeMappings = $this->getAttributeMapping($attributeSetId);
        $ret = array();
        $attrs = $this->getResource()->getAttributesForProductType($mappingProductTypeId);

        $partialRet = array();
        foreach ($options as $key => &$attribute) {
            // if the xcom's attribute is not restricted, consider it mapped
            $mappingAttributeId = null;
            foreach ($attributeMappings as $attrMapping) {
                  if ($attrMapping['attribute_id'] == $attribute['value'])
                      $mappingAttributeId = $attrMapping['mapping_attribute_id'];
            }
            $attr = $this->findAttribute($attrs,$mappingAttributeId)  ;

            if ( $attribute['attrtype'] != 'select' || $this->isMappedToCustomAttribute($attributeMappings, $attribute['value'])) {
                $ret[$key] = $attribute;
                continue;
            }
            $attributeId = $attribute['value'];
            $valueCollection = Mage::helper('xcom_mapping/Mapper')->getAttributeValueMapping($attributeSetId, $mappingProductTypeId,$attributeId);
            $valueData = $valueCollection->getData();
            $attrValues = $attribute['attrvalues'];
            if (count($attrValues) == 0 || $attrValues == null ) {
                $ret[$key] = $attribute;
                continue;
            }
            $mapping = array();
            $attrMapped = true;
            foreach ($attrValues as $valueId => $valueName)  {
                $valueMapped = false;
                foreach ($valueCollection as $value) {
                    if ($valueId == $value->getValueId()) {
                        $mapping[$valueId] = $value->getMappingValueId();
                         $valueMapped = true;
                        break;
                    }
                }
                if (!$valueMapped) {
                    $attrMapped = false;
                }
            }
            if ($attrMapped || $attr['is_restricted'] == 0) {
                $attribute['attrValueMapping']   = $mapping;
                $ret[$key] = $attribute;

            }
            else {
                $attribute['attrValueMapping'] = $mapping;
                $partialRet[$key] = $attribute;
            }
        }
        return array(0=>$ret, 1=>$partialRet);
    }


    private function findAttribute($attrs, $mappingAttributeId) {

        foreach ($attrs as $attr) {
            if ($attr['mapping_attribute_id'] == $mappingAttributeId)
                return $attr;

        }

        return null;


    }

    /**
     * Returns UnMapped Magento attributes by attribute set
     *
     * @param int $attributeSetId
     * @return array
     */
    public function getUnMappedMagentoAttributes($attributeSetId, $mappingProductTypeId)
    {
        $collection = $this->getMagentoAttributesCollection($attributeSetId);
        $allAttributes = array();

        foreach ($collection as $item) {
            if ($this->isUserDefinedAttribute($item)) {
            $allAttributes[$item->getAttributeId()] = array(
                'value' => $item->getAttributeId(),
                'label' => sprintf($item->getFrontendLabel()),
                'attrvalues' => Mage::helper('xcom_mapping')->getAttributeOptionsHash($item->getAttributeId()),
                'attrtype' => Mage::helper('xcom_mapping')->getAttributeType($item),
            );

            }
        }

        list($mappedAttributes, $partialMapped) = $this->getMappedMagentoAllAttributes($attributeSetId, $mappingProductTypeId);
        $ret = array();
        $mappedIds = array();
        foreach ($partialMapped as $key => $partial) {
            $ret[$key] = $partial;
            $mappedIds[] = $partial['value'];
        }

        foreach ($mappedAttributes as $item) {
            $mappedIds[] = $item['value'];
        }

        foreach ($allAttributes as $key => $item) {
            if (!in_array($item['value'], $mappedIds)) {

                $ret[$key] = array(
                    'value' => $item['value'],
                    'label' => $item['label'],
                    'attrvalues' => Mage::helper('xcom_mapping')->getAttributeOptionsHash($item['value']),
                    'attrtype' => $item['attrtype'],
                );
            }
        }
        return $ret;
    }

    public function getMappedXcomAttributes($attributeSetId,$productTypeId, $mappedMagAttrs) {
        list($mappedXcomAttr, $partialMappedXcomAttr) = $this->getMappedXcomAllAttributes($attributeSetId,$productTypeId,  $mappedMagAttrs);
        return $mappedXcomAttr;
    }
    /**
     * the function is to return completely mapped xcommerce attributes
     *
     * @param $mappedMagAttrs
     * @return array
     */
    public function getMappedXcomAllAttributes($attributeSetId,$productTypeId, $mappedMagAttrs)
    {

        $collection = Mage::getResourceModel('xcom_mapping/attribute_collection')->initAttributeRelations(null)
            ->addFieldToFilter('ptr.attribute_set_id', $attributeSetId)->addFieldToFilter('ptr.mapping_product_type_id', $productTypeId)
            ->setOrder('name', Varien_Data_Collection_Db::SORT_ORDER_ASC);;

        $attrModel = Mage::getModel('xcom_mapping/attribute');

        $option  = array();
        $counter = 0; // keep track number of custom attribute
        foreach ($collection as $item) {
            $data = $item->getData();
            if ( $item->getMappingAttributeId() == null ) {
                $option['-1' .  $counter++] = array(  'value' => '-1',
                    'label' => 'Custom Attribute',
                    'attrvalues' => array(),
                    'magento_name'=> $item->getAttributeName(),
                    'relation_attribute_id' => $data['relation_attribute_id'],
                    'isRestricted' => false,
                );
            }
            else {
                $option[$item->getMappingAttributeId()] = array(  'value' => $item->getMappingAttributeId(),
                    'label' => $item->getName(),
                    'attrvalues' => Mage::helper('xcom_mapping')->getProductTypeAttributeValues($item->getMappingAttributeId()),
                    'magento_name'=> $item->getAttributeName()     ,
                    'relation_attribute_id' => $data['relation_attribute_id'],
                    'isRestricted' => $data['is_restricted'],
                    'isRequired' => $attrModel->isRequired($item->getMappingAttributeId()),
                );
            }

        }
        $ret = array();
        if (count($mappedMagAttrs) == 0) {      // no mappedMag, so, any xmapping is partial
            return array(array(),$option);
        }

        $partialRet = array();
        foreach ($option as $key => $item) {
            $mapped = false     ;
            foreach ($mappedMagAttrs as $attr) {
                if ($attr['label'] == $item['magento_name']) {
                    $ret[$key] = $item;
                    $mapped = true;
                    break;
                }
            }
            if (!$mapped)  {
                $partialRet[$key] = $item;
            }

        }
        return array($ret, $partialRet);
    }

    protected function consolidateEbayChannel($channelCode)
    {
        if ( strstr($channelCode, 'ebay') ) {
            return 'ebay';
        }
        else {
            return $channelCode;
        }
    }
    public function isAttributeForChannels($mappingAttributeId, array $channels)
    {
        //consolidate all ebay channels into one
        $consolidatedChannels = array();
        foreach ( $channels as $channel ) {
                $consolidatedChannels[$this->consolidateEbayChannel($channel)]=true;

        }
        $channels = $this->getChannelInfo($mappingAttributeId);
        foreach ( $channels as $channelInfo ) {
            $channelCode = $channelInfo['channel_code'];
            if ( isset($consolidatedChannels[$this->consolidateEbayChannel($channelCode)])) {
                return true;
            }
        }
        return false;
    }
    /**
     * the function is to return unmapped and partially mapped xcommerce attributes
     *
     * @param $productTypeId
     * @param $attributeSetId
     * @param $mappedMagAttrs
     * @return array
     */
    public function getUnmappedXcomAttributes($attributeSetId,$productTypeId, $mappedMagAttrs)
    {
        $attrCollection = Mage::getResourceModel('xcom_mapping/attribute_collection')
            ->addFilter('mapping_product_type_id', $productTypeId)
            ->addIsRestrictedField()
            ->setOrder('name', Varien_Data_Collection_Db::SORT_ORDER_ASC);;
        $attrModel = Mage::getModel('xcom_mapping/attribute');

        list($data, $partial) = $this->getMappedXcomAllAttributes( $attributeSetId,$productTypeId,$mappedMagAttrs);
        $attr = array();
        $mappedIds = array();
        foreach ($partial as $attribute) {
            $attr[$attribute['value']] = array(  'value' => $attribute['value'],
                'label' => $attribute['label'],
                'attrvalues' => $attribute['attrvalues'],
                'magento_name' => $attribute['magento_name'],
                'relation_attribute_id' => $attribute['relation_attribute_id'],
                'isRestricted' => $attribute['isRestricted'],
                'isRequired' => $attrModel->isRequired($attribute['value']));
            $mappedIds[] = $attribute['value'];
        }

        foreach ($data as $attribute) {
            $mappedIds[] = $attribute['value'];
        }

        $enabledChannels = Mage::getModel('xcom_mapping/channel')->getEnabledChannels();
        foreach($attrCollection as $item) {
            if ( !$this->isAttributeForChannels($item->getMappingAttributeId(), $enabledChannels)) {
                continue;
            }
            $id = $item->getMappingAttributeId();
            if (!in_array($id, $mappedIds)) {
                $attr[$item->getMappingAttributeId()] = array(  'value' => $item->getMappingAttributeId(),
                    'label' => $item->getName(),
                    'attrvalues' => Mage::helper('xcom_mapping')->getProductTypeAttributeValues($item->getMappingAttributeId()),
                    'isRequired' => $attrModel->isRequired($id),
                    'isRestricted' => $item->getIsRestricted(),
                );
            }

        };

        //add custom attribute
        $attr['-1'] = array(  'value' => '-1',
            'label' => 'Custom Attribute',
            'attrvalues' => array(),
            'isRequired' => false,
            'isRestricted' => false,
        );
        return $attr;
    }


    /**
     * Returns Magento attributes collection
     *
     * @param $attributeSetId
     * @return mixed
     */
    public function getMagentoAttributesCollection($attributeSetId)
    {
        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->setAttributeSetFilter($attributeSetId)
            ->addStoreLabel(Mage_Core_Model_App::ADMIN_STORE_ID)
        //    ->addFilter('is_user_defined', 1)
            ->unshiftOrder('frontend_label', Varien_Data_Collection::SORT_ORDER_ASC);

        return $collection;
    }



    /**
     * @param $mappingAttributeId
     * @return true if the attribute is required by any channel, false otherwise
     */
    public function isRequired($mappingAttributeId)
    {
        return $this->getResource()->isRequired($mappingAttributeId);
    }

    public function getChannelInfo($mappingAttributeId)
    {
        return $this->getResource()->getChannelInfo($mappingAttributeId);
    }

    /**
     * @param $item
     *
     * return true if $item is used defined attribute or system attribute of weight
     */
    public function isUserDefinedAttribute($item) {
        $name = $item->getAttributeCode();
        if (($item->getIsUserDefined() == 1)|| (($item->getIsUserDefined() == 0) && ($item->getAttributeCode() == 'weight'))) {
            return true;
        }

        return false;

    }

}