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

class Xcom_Mapping_Helper_Mapper
{
    const XCOM_ATTRIBUTE_PREFIX   = 'xcom_';

    /**
     * get attributes in an attributeset that are eligible for attribute mapping
     * @param $attributeSetId
     * @return mixed
     */
    public function getAttributes($attributeSetId)
    {
        $result = array();
        $attrModel = Mage::getModel('xcom_mapping/attribute');
        $collection = $attrModel->getMagentoAttributesCollection($attributeSetId);
        foreach ( $collection as $attr) {
            if ( $attrModel->isUserDefinedAttribute($attr)) {
                $result[] = $attr;
            }
        }
        return $result;
    }

    /**
     * find the attributes in the product type
     * @param $attributeSetId
     * @param $productTypeId
     * @return array
     */
    public function getProductTypeAttributes($attributeSetId, $productTypeId)
    {
        $result = array();
        /** @var $collection Xcom_Mapping_Model_Resource_Attribute_Collection */
        $collection = Mage::getResourceModel('xcom_mapping/attribute_collection')
            ->addAttributeIdField()
            ->addFilter('mapping_product_type_id', $productTypeId);
        $enabledChannels = Mage::getModel('xcom_mapping/channel')->getEnabledChannels();
        $attrModel = Mage::getModel('xcom_mapping/attribute');
        foreach ( $collection as $attr) {
            if ( $attrModel->isAttributeForChannels($attr->getMappingAttributeId(), $enabledChannels)) {
                $result[] = $attr;
            }
        }
        return $result;
    }

    /**
     * match attribute by localized name, the locale is controlled by current store selection
     * @param $attributes
     * @param $mappingAttributes
     * @return array
     */
    protected function _matchAttributesByName($attributes, $mappingAttributes)
    {
        $matches = Array();

        foreach ( $attributes as $attribute) {
            $storeLabel = $attribute['store_label'];
            foreach ( $mappingAttributes as $mappingAttribute) {
                $attributeName = $mappingAttribute['name'];
                if ($this->_matchStringLoose($storeLabel, $attributeName) == 0
                    || $this->_matchAttributeNameLoose($storeLabel, $attributeName) == 0) {
                    $matches[$attribute['attribute_id']] = $mappingAttribute['mapping_attribute_id'];
                    break;
                }
            }
        }

        return $matches;
    }

    public function _reuseAttributeMapping($attributes, $mappingAttributes) {
        $matches = Array();
        $mappedMappingAttributes = array();

        $data = null;
        foreach( $attributes as $attribute) {
            $data = $this->existingAttributeMapping($attribute);
            foreach ( $data as $attr ) {
                $attrMapped = false;
                foreach( $mappingAttributes as $mappingAttribute ) {

                    if ( $attr['origin_attribute_id'] == $mappingAttribute['attribute_id'] ) {

                        // make sure mappingAttribute is only mapped once
                        if ( !array_key_exists ( $mappingAttribute['mapping_attribute_id'], $mappedMappingAttributes) ||
                            (array_key_exists ( $mappingAttribute['mapping_attribute_id'], $mappedMappingAttributes) &&
                             $mappedMappingAttributes[$mappingAttribute['mapping_attribute_id']] < $attr['relation_attribute_id']  ) ) {

                            $mappedMappingAttributes[$mappingAttribute['mapping_attribute_id']] =  $attr['relation_attribute_id'];

                        $matches[$mappingAttribute['mapping_attribute_id']] = $attribute['attribute_id'] ;

                        $attrMapped = true;
                        continue;
                        }
                    }

            }
                if ($attrMapped)
                    break;
            }
        }
       return  array_flip( $matches);


    }

    public function existingAttributeMapping($attribute)
    {
        $collection = Mage::getResourceModel('xcom_mapping/attribute_collection')->initAttributeRelations(null)
            ->addFieldToFilter('eat.attribute_id', $attribute['attribute_id'])->setOrder('relation_attribute_id', Varien_Data_Collection_Db::SORT_ORDER_DESC);;


        $data = $collection->getData();
        return $data;
    }


    /**
     * return predefined values for a product type attribute
     * @param $ptAttribute
     * @return mixed
     */



    public function getProductTypeAttributeValues($mappingAttributeId) {
        $values = array();
        $attrValueCollection = Mage::getResourceModel('xcom_mapping/attribute_value_collection')
            ->addFilter('mapping_attribute_id', $mappingAttributeId);
        foreach ($attrValueCollection->getItems() as $item) {
            //            'mapping_attribute_id'  => $attribute->getId(),
//            'channel_codes'         => $channelCodes,
//            'value_id'              => $data[$valueIdKey], // <-- string !!!
//            'name'                 => $data[$nameKey],
//            'locale_code'           => $this->getLocaleCode(),
            $attribute = Mage::getModel('xcom_mapping/attribute_value')->load($item->getMappingValueId());
            $data = $attribute->getData();
            $entry = array('mapping_value_id'=>$data['mapping_value_id'],'value_id'=> $data['value_id'],'name'=> $data['name']);
            $values[] = $entry;

        }
        return $values;
    }
    /**
     * @param $attributeId
     * @return mixed options for attribute {3=>'New', 4=>'Used'}
     */
    public function getMageAttributeOptions($attributeId) {
        $mappingHelper = Mage::helper('xcom_mapping');
        return $mappingHelper->getAttributeOptionsHash($attributeId);
    }

    protected function _findMatchingOption($options, $value)
    {
        foreach ( $options as $optionKey=>$optionValue) {
           if ( $this->_matchStringLoose($optionValue, $value) == 0) {
               return $optionKey;
           }
        }
        return false;
    }

    protected function _findMatchingValue($option, $values)
    {
        foreach ( $values as $value) {
            if ( $this->_matchStringLoose($option, $value['name']) == 0) {
                return $value['mapping_value_id'];
            }
        }
        return false;
    }

    protected function _stringMatchingValueMapping($mageValues, $productValues) {

        $matchArray = array();
        $i = 0;
        foreach ( $mageValues as $mageId => $mageValue ) {
            // find the matching value in magento attribute
            $option_key = $this->_findMatchingValue($mageValue, $productValues);
            if ( $option_key)
            {
                $matchArray[$mageId] = $option_key;
                $i++;
            }
        }
        return $matchArray;


    }

    public function _reuseValueMapping($mageValues, $attributeValues) {
        $matches = Array();

        $data = null;
        foreach( $mageValues as $key => $value) {
            $data = $this->existingValueMapping($key);

            foreach ($data as $attr) {
                  $attrMapped = false;
                foreach ($attributeValues as $attributeValue) {

                if ( $attr['origin_value_id'] ==  $attributeValue['value_id']) {
                $matches[$key] = $attributeValue['mapping_value_id'];
                    $attrMapped = true;
                    continue;
                }

        }
                if ($attrMapped)
                    break;
            }
        }
        return $matches;
    }

    public function getAttributeValueMapping($attributeSetId,$mappingProductTypeId, $attributeId) {
        $collection = Mage::getResourceModel('xcom_mapping/attribute_value_collection')->initValueRelations($attributeSetId, $attributeId)->addValueIdColumn()
             ->addFieldToFilter('ptr.mapping_product_type_id',$mappingProductTypeId);

        return $collection;
    }

    public function existingValueMapping($key)
    {
        $collection = Mage::getResourceModel('xcom_mapping/attribute_value_collection')->initMappingValueView()->addValueIdColumn()
            ->addFieldToFilter('mer.value_id', $key)->setOrder('relation_value_id', Varien_Data_Collection_Db::SORT_ORDER_DESC);


        $data = $collection->getData();
        return $data;
    }

    /**
     * @param $attributeId
     * @param $mappingAttributeId
     * @return {option_key=>$mappingValueId}
     */
    public function matchAttributeValueAsHash($attributeId, $mappingAttributeId)
    {
        $matchArray = array();
        // get the options for the magento attribute
        $options = $this->getMageAttributeOptions($attributeId);
        // predefined values for the productTaxonomy attribute
        $attrValueCollection = $this->getProductTypeAttributeValues($mappingAttributeId);

        $match1 = $this->_reuseValueMapping($options,$attrValueCollection);
        $match = $this->_stringMatchingValueMapping($options, $attrValueCollection);

        if(count($match1)> 0) {
            $match = $match1 + $match;
        }
        return $match;
    }
    /**
     * create an array that represent attribute value mapping
     * the array format is defined by Xcom_Mapping_Model_Relation::saveValuesRelation
     * @param $attribute - Mage_Catalog_Model_Resource_Eav_Attribute
     * @param $ptAttribute - Xcom_Mapping_Model
     * @return array
     */
    public function matchAttributeValue($attributeId, $mappingAttributeId) {
        $match = $this
            ->matchAttributeValueAsHash($attributeId, $mappingAttributeId);
        $matchArray = array();
        foreach ( $match as $option_key => $mappingValueId )  {
            $matchArray[] = array(
                'attribute_value' => $option_key,
                'target_attribute_value' => $mappingValueId,
            );
        }
        return $matchArray;
    }

    private function _transformString($str)
    {
        $pattern='/[-,_,\',\s,&]*/';
        return strtolower(preg_replace($pattern, '', $str));
    }
    /**
     * match two strings loosely, e.g., ignore case and  special characters, etc.
     * @param $str1
     * @param $str2
     */
    private function _matchStringLoose($str1, $str2)
    {
        return strcasecmp(
            $this->_transformString($str1),
            $this->_transformString($str2)
        );
    }

    /**
     * try to match generated attribute with original attribute
     * @param $attrLabel
     * @param $attrName
     * @return int
     */
    private function _matchattributeNameLoose($attrLabel, $attrName)
    {
        //the generated attribute label use '_' to separate product type
        //name and attribute name
        $pos = strrpos($attrLabel, '_');

        if ( $pos ) {
            $attrLabel = $this->_transformString(substr($attrLabel, $pos));
            //only the first 14 characters counts
            $attrName = $this->_transformString(substr($attrName, 0, 14));

            return strcasecmp(
                $attrLabel,
                $attrName
            );
        }
        return 1;
    }
    /**
     * remove attributes with duplicate labels so that we don't map
     * two magento attributes to the same XPT attribute
     * @param $attributes
     */
    protected function removeDuplicateAttributes($attributes)
    {
        $newAttributes = array();
        foreach ($attributes as $attribute) {
            $storeLabel = $this->_transformString($attribute['store_label']);
            if ( !array_key_exists($storeLabel, $newAttributes)) {
                $newAttributes[$storeLabel] = $attribute;
            } else {
                //override xcom_ attributes if exist
                if ( strpos($attribute['attribute_code'],
                    Xcom_Mapping_Helper_Mapper::XCOM_ATTRIBUTE_PREFIX) != 1 ) {
                    $newAttributes[$storeLabel] = $attribute;
                }
            }
        }
        return $newAttributes;
    }
    /**
     * generate attribute mapping between an attribute set and a product type
     * @param $productTypeId
     * @param $attributeSetId
     */
    public function generateAttributeMapping($attributeSetId, $productTypeId)
    {
        $mappedAttributes = array();
        if ( !isset($productTypeId) || !isset($attributeSetId))
        {
            return;
        }

        // use this to create all mappings
        $relation = Mage::getModel('xcom_mapping/relation');

        //check whether there is relationship between the attribute and product type
        //we will only automatically generate attribute mapping when the attribute set
        //is first mapped to a product type. This is necessary so that we don't override
        //merchant's selection

        $existingMappedProductType = Mage::getModel('xcom_mapping/product_type')
            ->getResource()->getMappingProductTypeId($attributeSetId);

        if ( $existingMappedProductType )
        {
            return;
        }

        //find all eligible attributes of the attribute set
        $attributes = $this->removeDuplicateAttributes(
            $this->getAttributes($attributeSetId));

        //find all unmapped attributes in the product type
        $mappingAttributes = $this->getProductTypeAttributes($attributeSetId, $productTypeId);

        //match attributes
        $match = $this->_reuseAttributeMapping($attributes, $mappingAttributes);

        $matches = $this->_matchAttributesByName($attributes, $mappingAttributes);


        if (count($match) > 0) {
            // add them together, make sure there is no duplicate mapping
            $mergedFlipped = array_flip($matches) + array_flip($match);
            $matches = array_flip($mergedFlipped);
        }
                //create mappings between the matched attributes
        if ( isset ($matches))
        {
            foreach ( $matches as $attributeId => $mappingAttributeId)
            {
                $relation->saveRelation($attributeSetId, $productTypeId, $attributeId,
                    $mappingAttributeId, array());

                $mappedAttributes[$attributeId] = $mappingAttributeId;
                // create mapping between attribute value
                $this->generateValueMapping($attributeSetId, $mappingAttributeId, $attributeId);
            }
        }

        return $mappedAttributes;

    }

    public function generateValueMapping($attributeSetId, $mappingAttributeId, $attributeId)
    {
        $relation = Mage::getModel('xcom_mapping/relation');

        $matchingValues = $this->matchAttributeValue($attributeId, $mappingAttributeId);
        if (!empty($matchingValues)) {
            $relation->saveValuesRelation($attributeSetId, $attributeId,
                $mappingAttributeId, $matchingValues);
        }
    }



    public function getDataModel($attributeSetId, $productTypeId) {
        $mappedMagentoAttr = Mage::getModel('xcom_mapping/attribute')->getMappedMagentoAttributes($attributeSetId, $productTypeId);
        $unmappedMagentoAttr = Mage::getModel('xcom_mapping/attribute')->getUnmappedMagentoAttributes($attributeSetId, $productTypeId);
        $mappedXcomAttr = Mage::getModel('xcom_mapping/attribute')->getMappedXcomAttributes($attributeSetId, $productTypeId, $mappedMagentoAttr);
        $unmappedXcomAttr = Mage::getModel('xcom_mapping/attribute')->getUnmappedXcomAttributes($attributeSetId, $productTypeId,$mappedMagentoAttr);

        return array(0=>$mappedXcomAttr, 1=>$unmappedXcomAttr,2=> $mappedMagentoAttr, 3=>$unmappedMagentoAttr);
    }

}