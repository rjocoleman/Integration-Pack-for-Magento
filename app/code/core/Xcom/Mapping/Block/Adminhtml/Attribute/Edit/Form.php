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


class Xcom_Mapping_Block_Adminhtml_Attribute_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Required attributes flag.
     *
     * @var bool
     */
    protected $_requiredAttrExists = false;
    protected $_autoMappingResult = array();

    public function __construct()
    {
        parent::__construct();
        $this->setId('edit_form');
        $this->_controller = 'adminhtml_mapping_attribute';
    }

    /**
     * @return Xcom_Mapping_Block_Adminhtml_Attribute_Form
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this
            ->setTemplate('xcom/mapping/widget/form/renderer/fieldset/element.phtml');
        $this
            ->setTemplate('xcom/mapping/widget/form/renderer/fieldset.phtml');
        return $this;
    }

    /**
     * @return mixed
     */
    public function getXcommerceAttributes()
    {
        $params = Mage::registry('current_params');
        $productTypeId = $this->_getProductTypeId();
        $xCommerceAttributes = Mage::getModel('xcom_mapping/attribute')->getXcommerceAttributes($productTypeId);

        return $xCommerceAttributes;
    }

    /**
     * @param $attribute
     * @return string
     */
    public function getLiId($attribute)
    {
        return json_encode($attribute);
    }


    public function getClearMappingUrl() {
        $clearUrl = $this->getUrl('*/mapping_attribute/clearAttributeMapping',array('isAjax' => true, '_current' => true) );
        return $clearUrl;
    }


    public function getSaveMappingUrl() {
        $url = $this->getUrl('*/mapping_attribute/saveAttributeMapping', array('isAjax' => true,'_current' => true));
        return $url;
    }

    public function getUpdateMappingUrl() {
        $url = $this->getUrl('*/mapping_attribute/updateAttributeMapping', array('isAjax' => true,'_current' => true));
        return $url;
    }

    public function createNewAttrUrl() {
        return $this->getUrl('*/mapping_attribute/createNewAttr', array('isAjax' => true,'_current' => true));
    }

    /**
     * Returns Mapped Magento attributes by attribute set
     *
     * @param int $attributeSetId
     * @return array
     */
    public function getMappedMagentoAttributes()
    {
        $params = Mage::registry('current_params');
        $attributeSetId = $params->getAttributeSetId();
        $mappingProductTypeId = $this->_getProductTypeId();
        $mappedMagentoAttributes = Mage::getModel('xcom_mapping/attribute')->getMappedMagentoAttributes($attributeSetId, $mappingProductTypeId);

        return $mappedMagentoAttributes;
    }

    /**
     * Returns UnMapped Magento attributes by attribute set
     *
     * @param int $attributeSetId
     * @return array
     */
    public function getUnMappedMagentoAttributes()
    {
        $params = Mage::registry('current_params');
        $attributeSetId = $params->getAttributeSetId();
        $mappingProductTypeId = $this->_getProductTypeId();
        $unMappedMagentoAttributes = Mage::getModel('xcom_mapping/attribute')->getUnMappedMagentoAttributes($attributeSetId, $mappingProductTypeId);

        return $unMappedMagentoAttributes;
    }

    public function getMappedXcomAttributes()
    {
        $mappedMagAttrs = $this->getMappedMagentoAttributes();
        $params = Mage::registry('current_params');
        $productTypeId = $this->_getProductTypeId();
        $attributeSetId = $params->getAttributeSetId();
        $ret = Mage::getModel('xcom_mapping/attribute')->getMappedXcomAttributes($attributeSetId,$productTypeId,$mappedMagAttrs);
        return $ret;
    }

    public function getUnmappedXcomAttributes()
    {
        $mappedMagAttrs = $this->getMappedMagentoAttributes();
        $params = Mage::registry('current_params');
        $productTypeId = $this->_getProductTypeId();
        $attributeSetId = $params->getAttributeSetId();
        $attr = Mage::getModel('xcom_mapping/attribute')->getUnmappedXcomAttributes($attributeSetId,$productTypeId,$mappedMagAttrs);

        return $attr;
    }

   /*
    * color red if it is a required attribute
    */

    public function _colorRedIfRequired($theItem, $isRequired){

        $myoptions    = array(
            'value' => $theItem->getId(),
            'label' => sprintf("%s %s", $theItem->getName(),  $isRequired),
            'style' => $isRequired ? 'color:red;' : ''
        );
        return $myoptions;
    }

    public function getAutoValueMappings()
    {
        $params = Mage::registry('current_params');
        $attributeSetId = $params->getAttributeSetId();
        $mappingProductTypeId = $this->_getProductTypeId();

        //for each pair of magento-xcommerce attribute, create an array of value mapping
        $helper = Mage::helper('xcom_mapping');
        $mappingHelper = Mage::helper('xcom_mapping/mapper');
        $magAttributes = $helper->getAttributes($attributeSetId);
        $mappingAttributes = $helper->getProductTypeAttributes($attributeSetId, $mappingProductTypeId);

        $valueMapping = array();
        foreach ( $magAttributes as $magAttribute ) {
            if (!in_array($magAttribute['frontend_input'], array('select', 'multiselect'))) {
                //auto value mapping only applies to select attributes
                continue;
            }
            $magAttributeId = $magAttribute['attribute_id'];
            $valueMapping[$magAttributeId] = array();
            foreach ( $mappingAttributes as $mappingAttribute) {
                $mappingAttributeId = $mappingAttribute['mapping_attribute_id'];
                $matchingValues = $mappingHelper->matchAttributeValueAsHash(
                    $magAttributeId,
                    $mappingAttributeId);
                if ( count($matchingValues)> 0 ) {
                    $valueMapping[$magAttributeId][$mappingAttributeId] = $matchingValues;
                }
            }
        }
        return json_encode($valueMapping);
    }

    public function getDataModel() {

        return Mage::helper('xcom_mapping/mapper')->getDataModel();
    }

    public function setAutoMappingResult($result) {
        $this->_autoMappingResult = $result;
    }
    public function getAutoMappingResult() {
        return $this->_autoMappingResult;
    }

    private function _getProductTypeId() {
        $ret = null;
        $params = Mage::registry('current_params');
        $attributeSetId = $params->getAttributeSetId();
        $mappingProductTypeId = $params->getMappingProductTypeId();
        if ($mappingProductTypeId == null) {
               $ret = Mage::getModel('xcom_mapping/product_type')
                ->getResource()->getMappingProductTypeId($attributeSetId);
        }
        else {
            $ret = $mappingProductTypeId;
        }
        return $ret;
    }
}
