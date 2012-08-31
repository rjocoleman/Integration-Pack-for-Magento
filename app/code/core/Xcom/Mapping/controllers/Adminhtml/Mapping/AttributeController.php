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
 require_once 'Mage/Adminhtml/Controller/Action.php';

class Xcom_Mapping_Adminhtml_Mapping_AttributeController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Store parameters to registry
     *
     * @return Varien_Object
     */
    protected function _initParams()
    {
        $params = new Varien_Object($this->getRequest()->getParams());
        Mage::register('current_params', $params);
        return $params;
    }

    /**
     * validate request parameters
     *
     * @return bool
     */
    protected function _validateRequestParams($attributeSetId, $productTypeId)
    {

        $isValidParams = true;
        if ($productTypeId && $attributeSetId) {
            $mappingProductTypeId = Mage::getModel('xcom_mapping/product_type')
                ->load((int)$productTypeId)
                ->getId();
            $attributeSetId = Mage::getModel('eav/entity_attribute_set')
                ->load((int)$attributeSetId)
                ->getId();
            if ($attributeSetId == null || ($mappingProductTypeId == null
                && $productTypeId != Xcom_Mapping_Model_Relation::DIRECT_MAPPING)) {
                $isValidParams = false;
            }
        } else {
            $isValidParams = false;
        }
        if (!$isValidParams) {
            $this->_getSession()->addError($this->__('Invalid Product Type or Attribute Set!'));
            $this->_redirect('*/map_attribute/index');
            return false;
        }
        return $isValidParams;
    }

    /**
     * Validate attribute mapping
     */
    protected function _validateMapping($attributeSetId, $productTypeId)
    {
        $params     = Mage::registry('current_params');
        /** @var $validator Xcom_Mapping_Model_Validator */
        $validator  = Mage::getSingleton('xcom_mapping/validator');
        $isRequiredAttributeHasMappedValue = $validator->validateIsRequiredAttributeHasMappedValue(
            $productTypeId,
            null, $attributeSetId);
//        if (!$isRequiredAttributeHasMappedValue) {
//            $this->_getSession()->addError($this->__('You have pending mandatory Attributes to be mapped'));
//        }
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_initParams();
        //generate attribute mapping automatically
        //TODO: check flag before generate automatic mapping
        $params     = Mage::registry('current_params');
        $attributeSetId = $params->getAttributeSetId();
        $productTypeId = $params->getMappingProductTypeId();

        if ($productTypeId == null) {
            $productTypeId =   Mage::getModel('xcom_mapping/product_type')
                ->getResource()->getMappingProductTypeId($attributeSetId);
        }

        if($productTypeId == null) {  // mapping does not exists
            $this->_redirect('*/map_attribute/index');
        }

        // checking if the auto_map checkbox is clicked
        $auto_map = $params["auto_map"];
        $autoMappingResult = array();
        if ($auto_map == 'on')
        {
            $autoMappingResult = Mage::helper('xcom_mapping/mapper')->generateAttributeMapping(
                $attributeSetId, $productTypeId);
        }

        $existingMappedProductTypeId = Mage::getModel('xcom_mapping/product_type')
            ->getResource()->getMappingProductTypeId($attributeSetId);
        if ( $existingMappedProductTypeId ) {
            //check whether it's the same
            if ( $existingMappedProductTypeId != $productTypeId) {
                $this->_getSession()->addError('The attribute set has already been mapped.');
                $this->_redirect('*/map_attribute/index');
            }
        }
        else {
            //establish relation between the product type and attribute set if none exists
            Mage::getModel('xcom_mapping/product_type')
                ->saveRelation($attributeSetId, $productTypeId);
        }

        $this->loadLayout();

        $layout = $this->getLayout();
        $form = $layout->getBlock('attribute_form');
        $form->setAutoMappingResult($autoMappingResult);

        $this->_validateRequestParams($attributeSetId, $productTypeId);
        $this->_title($this->__('Manage Attributes Mapping'));
        $this->_setActiveMenu('catalog/attributes/attribute_mapping');

        $this->renderLayout();
    }

    /**
     * Save attribute relation
     */
    public function saveAction()
    {
        $params = $this->_initParams();
        $auto_map = $params["auto_map"];
        try {
            Mage::getModel('xcom_mapping/relation')->saveRelation(
                $params->getAttributeSetId(),
                $params->getMappingProductTypeId(),
                $params->getAttributeId(),
                $params->getMappingAttributeId(),
                $auto_map,
                array()
            );

            // checking if auto_map has been set
            if ($auto_map == 'on')
            {
                Mage::helper('xcom_mapping/mapper')->generateValueMapping(
                    $params->getAttributeSetId(), $params->getMappingProductTypeId(),  $params->getAttributeId());
            }

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirectToIndex($params);
    }

    public function deleteAction()
    {
        $params     = $this->_initParams();
        try {
            Mage::getModel('xcom_mapping/attribute')->deleteRelation($params->getRelationAttributeIds());
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirectToIndex($params);
    }

    public function addAttributeAction()
    {
        $content = $this->getLayout()->createBlock('adminhtml/template', '', array(
            'attributeSetId' => $this->getRequest()->getParam('attributeSetId'),
            'mappingProductTypeId' => $this->getRequest()->getParam('mappingProductTypeId'),
            'mappingAttributeId' => $this->getRequest()->getParam('mappingAttributeId'),
        ));
        $content->setTemplate('xcom/mapping/attribute/addAttribute.phtml');
        $this->getResponse()->setBody($content->toHtml());
    }

    /**
     * @param Varien_Object $params
     * @return void
     */
    protected function _redirectToIndex(Varien_Object $params)
    {
        $this->_redirect('*/*/index', array(
            'attribute_set_id'          => $params->getAttributeSetId(),
            'mapping_product_type_id'   => $params->getMappingProductTypeId()
        ));
    }

    public function clearAttributeMappingAction()
    {

        $relationId = (int) $this->getRequest()->getParam('relationAttributeId');
        try {
            Mage::getModel('xcom_mapping/attribute')->deleteRelation($relationId);

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        $this->reloadDataModelAction();

    }



    public function updateAttributeMappingAction()
    {
        $this->_initParams();
        $params = Mage::registry('current_params');

        $attributeSetId = $this->getRequest()->getParam('attribute_set_id');

        $productTypeId =   Mage::getModel('xcom_mapping/product_type')
            ->getResource()->getMappingProductTypeId($attributeSetId);

        $values = $this->getRequest()->getParam('values');
        $values = json_decode($values, true);
        //work around incompatibility problem with JSON.stringify in front end
        if ( !is_array($values)) {
            $values = json_decode($values, true);
        }

        $attributeId = $this->getRequest()->getParam('attributeId');
        $attributeMappingId = $this->getRequest()->getParam('mappingAttributeId');


        try {
            Mage::getModel('xcom_mapping/relation')->saveRelation(
                $attributeSetId,
                $productTypeId,
                $attributeId,
                $attributeMappingId,
                array()
            );
        }
         catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        Mage::getModel('xcom_mapping/relation')->saveValuesRelation(
            $attributeSetId,
            $attributeId,
            $attributeMappingId,
            $values
        );

        $this->reloadDataModelAction();
    }

    /**
     *
     */
    public function saveAttributeMappingAction()
    {
        $this->_initParams();
        $params = Mage::registry('current_params');

        $attributeSetId = $this->getRequest()->getParam('attribute_set_id');

        $productTypeId =   Mage::getModel('xcom_mapping/product_type')
            ->getResource()->getMappingProductTypeId($attributeSetId);



        $attributeId = $this->getRequest()->getParam('attributeId');

        // we need delete the old attribute mapping if there exists one
        $collection =  Mage::getResourceModel('xcom_mapping/attribute');
        $mappingRelation = $collection->getMappingRelationAttributeId($attributeSetId, $productTypeId, $attributeId) ;
        $collection->deleteRelation($mappingRelation);

        $values = $this->getRequest()->getParam('values');
        $values = json_decode($values, true);
        //work around incompatibility problem with JSON.stringify in front end
        if ( !is_array($values)) {
            $values = json_decode($values, true);
        }

        $attributeMappingId = $this->getRequest()->getParam('attributeMappingId');

        try {
            Mage::getModel('xcom_mapping/relation')->saveRelation(
                $attributeSetId,
                $productTypeId,
                $attributeId,
                $attributeMappingId,
                array()
            );

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        Mage::getModel('xcom_mapping/relation')->saveValuesRelation(
            $attributeSetId,
            $attributeId,
            $attributeMappingId,
            $values
        );
        $this->reloadDataModelAction();
    }

    public function reloadDataModelAction() {
        $attributeSetId = $this->getRequest()->getParam('attribute_set_id');

        $productTypeId =   Mage::getModel('xcom_mapping/product_type')
            ->getResource()->getMappingProductTypeId($attributeSetId);

        $dataModel =  Mage::helper('xcom_mapping/mapper')->getDataModel($attributeSetId, $productTypeId) ;
        $html = json_encode($dataModel, JSON_FORCE_OBJECT);
        $this->getResponse()->setBody($html);
    }


    protected function createAttributeOptions($mappingAttributeId, $defaultValue)
    {
        $valueCollection = Mage::getResourceModel('xcom_mapping/attribute_value_collection')
            ->addFilter('mapping_attribute_id', $mappingAttributeId);

        $values = array();
        foreach ($valueCollection as $attributeValue) {
            $valueData = $attributeValue->getData();
            $values[] = $valueData['name'];
        }

        if ($values == null || count($values) == 0) {
            return null;
        }
        $options = array();
        $value = array();
        $order = array();
        $delete = array();
        $i = 0;
        foreach ($values as $v ) {
            $i = $i + 1;
            unset($option);
            $option[] = $v;
            $option[] = "";
            $index = 'option_' . $i;
            $value[$index] = $option;
            $order[$index] = "";
            $delete[$index] = "";
        }

        $options["value"] = $value;
        $options["order"] = $order;
        $options["delete"] = $delete;

        return $options;
    }

    protected function createAttributeGroup($attributeSetId, $groupName)
    {
        $groupModel = Mage::getModel('eav/entity_attribute_group');

        $groupModel->setAttributeGroupName($groupName)
            ->setAttributeSetId($attributeSetId);

        if( !$groupModel->itemExists() ) {
            try {
                $groupModel->save();
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('An error occurred while saving this group.'));
            }
        }

        $attributeGroupId = Mage::getSingleton('catalog/config')
            ->getAttributeGroupId($attributeSetId, $groupName);
        return $attributeGroupId;
    }

    /**
     * construct attribute name from product type id and attribute id
     * make sure that the name is no more than 30
     * @param $productTypeId
     * @param $attributeId
     * @return string
     */
    public function createAttributeName($productTypeId, $attributeId)
    {
        $attrName = substr($productTypeId, 0, 15) . "_" . substr($attributeId, 0, 14);
        return $attrName;
    }

    public function createNewAttrAction()
    {
        $this->_initParams();
        $params = Mage::registry('current_params');

        $attributeXId = $this->getRequest()->getParam('attributeId');
        $mappingAttribute = Mage::getModel('xcom_mapping/attribute')
            ->load($attributeXId);
        $attributeName = $this->getRequest()->getParam('attributeName');
        $Xvalue = $this->getRequest()->getParam('attributeValue');
        $applyAll = $this->getRequest()->getParam('applyAll');
        $attributeSetId = $params->getAttributeSetId();
        $mappingProductTypeId = Mage::getModel('xcom_mapping/product_type')
            ->getResource()->getMappingProductTypeId($attributeSetId);

        $mappingProductType = Mage::getModel('xcom_mapping/product_type')
            ->load($mappingProductTypeId);
        $attributeName = $this->createAttributeName(
            $mappingProductType->getProductTypeId(),
            $mappingAttribute->getAttributeId());

        $this->_entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

        $setModel = Mage::getModel('eav/entity_attribute_set')
            ->setEntityTypeId(4)->load((int)$attributeSetId);
        $setData = $setModel->getData();
        //TODO: verify this is the right group to put the attribute in
        $groupName = 'Xcommerce-' . $mappingProductType->getProductTypeId();
        $attributeGroupId = $this->createAttributeGroup($attributeSetId, $groupName);

        $isRestricted = $mappingAttribute->getIsRestricted();
        $options = array();
        if ( $isRestricted ) {
            $options = $this->createAttributeOptions($attributeXId, $Xvalue);
        }
        $isRequired = Mage::getModel('xcom_mapping/attribute')->isRequired($attributeXId);


        // Create new attribute with value of $Xvalue and put it in $attributeSet
        $attributeId = $this->createAttribute($mappingProductTypeId, $attributeName, $options,
            $params->getAttributeSetId(), $attributeGroupId, $isRequired, $Xvalue);

        if ( !isset($attributeId) ) {
            //something is wrong, return
            //TODO: return proper error
            $this->reloadDataModelAction();
            return;
        }

        // Create mapping between the new attribute and $attributeXId
        try {
            Mage::getModel('xcom_mapping/relation')->saveRelation(
                $params->getAttributeSetId(),
                $mappingProductTypeId,
                $attributeId,
                $attributeXId,
                array()
            );
            $mappingHelper = Mage::helper('xcom_mapping/mapper');
            $mappingHelper->generateValueMapping(
                $attributeSetId,
                $attributeXId,
                $attributeId);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        // if apply_all is true, make this attribute applied to all items in the Attribute Set
        if ( $applyAll=='true' && isSet($Xvalue)) {
            $products = Mage::getModel('catalog/product')
                ->getCollection()
                ->addFieldToFilter('attribute_set_id', $attributeSetId);

            $attribute = Mage::getModel('catalog/resource_eav_attribute')
                ->loadByCode($this->_entityTypeId, $attributeName);

            foreach ( $products as $product ) {
                $productData = array();
                $productData[$attribute->getAttributeCode()] = $attribute->getDefaultValue();
                $product->addData($productData);
                $product->save();
            }
        }


        $this->reloadDataModelAction();
    }

    private function getDefaultOption($options, $defaultValue)
    {
        $default = array();

       // check new option
        foreach ( $options['value'] as $optionName => $optionValue ) {
            if ( $optionValue[0] == $defaultValue) {
                $default[] = $optionName;
                return $default;
            }
        }
    }

    protected function mergeExistingOptions($existingOptions, $options)
    {
        $newOptions = array();
        $newOptions['value'] = array();
        $newOptions['order'] = array();
        $newOptions['delete'] = array();
        foreach ( $options['value'] as $optionName => $optionLabels) {
            $optionLabel = $optionLabels[0];
            $found = false;
            foreach ( $existingOptions as $existingOption) {
                if ( $optionLabel == $existingOption['label']) {
                    $newOptions['value'][$existingOption['value']] = $optionLabels;
                    $newOptions['order'][$existingOption['value']] = "";
                    $newOptions['delete'][$existingOption['value']] = "";
                    $found = true;
                    break;
                }
            }
            if ( !$found ) {
                $newOptions['value'][$optionName] = $optionLabels;
                $newOptions['order'][$optionName] = "";
                $newOptions['delete'][$optionName] = "";
            }
        }
        return $newOptions;
    }

    protected function createAttribute($productType, $magAttrName, $options, $setId, $groupId, $isRequired, $defaultValue) {

        $this->_entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

        $magAttrModel = Mage::getModel('catalog/resource_eav_attribute')->loadByCode($this->_entityTypeId, $magAttrName);

        $data = $this->prepareData($magAttrName, $isRequired);

        if ($options != null) {
            $attrSourceModel = null;
            try {
                $attrSourceModel = $magAttrModel->getSource();
            }
            catch ( Exception $e ) {
                $attrSourceModel = null;
            }

            $existingOptions = array();
            if ( isset($attrSourceModel) ) {
                $existingOptions = $attrSourceModel->getAllOptions();
            }
            $mergedOptions = $this->mergeExistingOptions($existingOptions, $options);
            $data['frontend_input'] = "select";
            $data['option'] = $mergedOptions;
            $data['default'] = $this->getDefaultOption($mergedOptions, $defaultValue);
        }
        else {
            $data['default_value'] = $defaultValue;
        }

        $magAttrModel->addData($data);
        $magAttrModel->setEntityTypeId($this->_entityTypeId);
        $magAttrModel->setIsUserDefined(1);


        try {
            $magAttrModel->setAttributeSetId($setId);
            $magAttrModel->setAttributeGroupId($groupId);
            $magAttrModel->save();

            return $magAttrModel->getId();
        } catch (Exception $e) {
            Mage::log($e);
            return;
        }
    }

    private function prepareData($attributeName, $isRequired) {
        $date = array();
        $data['attribute_code'] = $attributeName;
        $data['is_global'] = "0";
        $data['frontend_input'] = "text";
        $data['default_value_text'] = "";
        $data['default_value_yesno'] ="0";
        $data['default_value_date'] ="";
        $data['default_value_textarea'] = "";
        $data['is_unique'] = "0";
        if ($isRequired) {
            $data['is_required'] ="1";
        }
        else {
            $data['is_required'] ="0";
        }
        $data['frontend_class'] = "";
        $data['is_configurable'] ="0";
        $data['is_searchable'] ="0";
        $data['is_visible_in_advanced_search']="0";
        $data['is_comparable'] ="0";
        $data['is_used_for_promo_rules'] = "0";
        $data['is_html_allowed_on_front'] = "1";
        $data['is_visible_on_front'] ="0";
        $data['used_in_product_listing'] = "0";
        $data['used_for_sort_by'] ="0";
        $label = array();
        $label[] = $attributeName;
        $label[] = "";
        $data['frontend_label'] = $label;
        $data['source_model'] =null;
        $data['backend_model'] = null;
        $data['is_filterable'] = 0;
        $data['is_filterable_in_search'] = 0;
        $data['backend_type'] = "varchar";
        $data['default_value'] ="";
        $data['apply_to'] = array();
        $data['entity_type_id'] = 4;
        $data['is_user_defined'] = 1;

        return $data;
    }
}
