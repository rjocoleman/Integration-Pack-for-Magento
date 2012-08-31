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

class Xcom_Mapping_Adminhtml_Map_AttributeController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Constructor.
     * Set used module name for translations.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUsedModuleName("Xcom_Mapping");
        $this->_publicActions = array('value');
    }

    protected function _getValidator()
    {
        return Mage::getSingleton('xcom_mapping/validator');
    }
    /**
     * Index page for attribute mapping.
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_title($this->__('Manage Attribute Set Mapping'));
        $this->loadLayout();
        $this->_setActiveMenu('catalog/attributes/attribute_mapping');
        $this->renderLayout();
    }

    /**
     * Edit attribute set mapping page.
     *
     * @return void
     */
    public function editSetAction()
    {
        //TODO to be done
        $attributeSetId = (int) $this->getRequest()->getParam('attribute_set_id');
        if (empty($attributeSetId)) {
            $this->_redirect('*/*/index');
        }

        $productTypeId =   Mage::getModel('xcom_mapping/product_type')
            ->getResource()->getMappingProductTypeId($attributeSetId);

        if ($productTypeId) {
            $this->_title($this->__('Edit Attribute Set Mapping'));
            $this->_redirect('*/mapping_attribute',array(
              'attribute_set_id' => $attributeSetId

          ));
        } else {
            $this->_title($this->__('New Attribute Set Mapping'));
        }

        $this->loadLayout();
        $this->_setActiveMenu('catalog/attributes/attribute_mapping');
        $this->renderLayout();
    }









    /**
     * Save custom set mapping
     */
    public function saveSetAction()
    {
        $attributeSetId         = (int)$this->getRequest()->getParam('attribute_set_id');
        $mappingProductTypeId   = (int)$this->getRequest()->getParam('mapping_product_type_id');
        try {
            Mage::getModel('xcom_mapping/product_type')->deleteAttributeSetMappingRelation($attributeSetId);
            if ($mappingProductTypeId == Xcom_Mapping_Model_Relation::DIRECT_MAPPING) {
                Mage::getModel('xcom_mapping/relation')->saveRelation($attributeSetId, null, null, null, array());
            }
            else {
              //  Mage::getModel('xcom_mapping/product_type')->saveRelation($attributeSetId, $mappingProductTypeId);
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        $this->_redirect('*/mapping_attribute/index', array('_current'  => true));
    }


    public function clearSetAction()
    {
        $attributeSetId         = (int)$this->getRequest()->getParam('attribute_set_id');

        try {
            Mage::getModel('xcom_mapping/product_type')->deleteAttributeSetMappingRelation($attributeSetId);

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/map_attribute/index', array('_current'  => true));
    }



    /**
     * Clear taxonomy mapping
     */
    public function clearTaxonomyAction()
    {
        try {
            Mage::getResourceModel('xcom_mapping/relation')->deleteTaxonomy();
            Mage::dispatchEvent('taxonomy_data_cleared', array());
            $this->_getSession()->addSuccess($this->__('Taxonomy was cleared successfully'));
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirect('*/map_attribute/index');
    }
}
