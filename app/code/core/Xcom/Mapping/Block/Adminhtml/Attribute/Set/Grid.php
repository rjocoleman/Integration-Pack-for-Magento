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
class Xcom_Mapping_Block_Adminhtml_Attribute_Set_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Prepare grid.
     *
     * @return void
     */

    private $param = null;


    protected function _construct()
    {
        parent::_construct();
        $this->setId('attributeSetGrid');
        $this->setDefaultSort('attribute_name');
        $this->setDefaultDir('asc');
        $this->setVarNameFilter('attribute_set_filter');
    }


    protected function _initParams()
    {
        $params = new Varien_Object($this->getRequest()->getParams());
        Mage::register('current_params', $params);
        return $params;
    }


    /**
     * @return Xcom_Mapping_Block_Adminhtml_Attribute_Set_Grid
     */
    protected function _prepareCollection()
    {
        /** @var $collection Xcom_Mapping_Model_Resource_Product_Type_Collection */
        $collection = Mage::getResourceModel('xcom_mapping/product_type_collection')
            ->initProductTypeRelations();

        $this->param = $this->_initParams();
        if ($this->getColumn('product_type_name') && ($this->param->getSort() == 'product_type_name') ) {
            $this->getColumn('product_type_name')->setFilterIndex($collection->getProductTypeNameExpr());
        }

        if ($this->param->getSort() ) {
           $this->getColumn('status')->setFilterIndex('status');
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return Xcom_Mapping_Block_Adminhtml_Attribute_Set_Grid
     */
    protected function _afterLoadCollection()
    {

        /** @var $validator Xcom_Mapping_Model_Validator */
        $validator = Mage::getSingleton('xcom_mapping/validator');
        foreach($this->getCollection() as $item) {
            $id = $item->getMappingProductTypeId();
            $attributeSetId = $item->getAttributeSetId();
            if (!$validator->validateIsRequiredAttributeHasMappedValue($id, null, $attributeSetId)) {
                $item->setProductTypeName($item->getProductTypeName() );

            }


                if (is_null($item->getAttributeSetId()) || is_null($item->getMappingProductTypeId()))  {
                    $width = 0;
                }
                else {
                    $width =  $this->_getStatusWidth($item->getAttributeSetId(),$item->getMappingProductTypeId());
                }

                $item->setStatus((int) $width);

        }

            // sort collection->items
            $productArray = array();
            $statusArray=array();
            $collection = $this->getCollection();
            $items= $this->getCollection()->getItems();
            $countLimit = count($items);
            $tmpItems = array() ;

                foreach ( array_keys($items) as $key) {

                    $data = $items[$key]->getStatus();
                    $productName = $items[$key]->getProductTypeName();
                    array_push($productArray,$productName);
                    array_push($statusArray, $data);
                }

            //Start sort in ascending order
            array_multisort($statusArray,  SORT_ASC, $productArray, $items);

            if (($this->param->getDir() == 'asc')) {
                array_multisort($statusArray,  SORT_DESC, $productArray, $items);
            }
            else {
                array_multisort($statusArray,  SORT_ASC, $productArray, $items);
            }

            foreach ( array_keys($collection->getItems()) as $key) {
                $collection->removeItemByKey($key);
            }
              //Testing bug
            foreach ($items as $item) {
                $collection->addItem($item);
            }

        return parent::_afterLoadCollection();
    }

    /**
     * Prepare grid columns.
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('attribute_set_name', array(
            'header'=> Mage::helper('index')->__('Magento Attribute Set'),
            'index' => 'attribute_set_name',
            'sortable'  => false,
        ));
        $this->addColumn('product_type_name', array(
            'header'=> $this->__('X.commerce Attribute Set'),
            'index' => 'product_type_name',
            'filter_index' =>'mec.name',
            'frame_callback' => array($this,'renderProductType')
        ));

        $this->addColumn('status', array(
            'header'    => $this->__('Required Attributes'),
            'title'    => 'test',
            'width'     => '200',
            'index'     => 'status',
            'filter'    => false,
            'frame_callback' => array($this, 'decorateStatus')
        ));

        $this->addColumn('action', array(
            'header'    => $this->__('Actions'),
            'width'     => '150',
            'index'     => 'action',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => array($this, 'actionColumn')
        ));

        $this->setColumnRenderers(array('action' => 'xcom_mapping/adminhtml_widget_grid_column_renderer_action'));


        return parent::_prepareColumns();
    }

    /**
     * Decorate status column values
     *
     * @return string
     */
    public function decorateStatus($value, $rows)
    {
        $data = $rows->getData();
        $width = "0%";
        $attributeSetId = $data['attribute_set_id'];
        $productTypeId = $data['mapping_product_type_id'];
        if (isset($productTypeId)) {

        $width = $this->_getStatusWidth($attributeSetId, $productTypeId);

        }

        if ($width == "0%") {
            return "<div class=\"mapping_progress_empty\" >
                   $width
                </div>";
        }
        else if ($width == "100%") {
            return "
                   <div class=\"mapping_progress_full\">
                   $width
                   </div>
                ";
        }
        else {

        return   "<div class=\"meter-wrap\">
    <div class=\"meter-value\" style=\"width: $width\">
        <div class=\"meter-text\">
                $width
        </div>
    </div>
</div>";



        }
    }


    /**
     * Decorate status column values
     *
     * @return string
     */
    public function actionColumn($value, $rows)
    {
      $data = $rows->getData();
        $attributeSetId = $data['attribute_set_id'];
        $productTypeId = $data['mapping_product_type_id'];
        if (isset($productTypeId)) {
      $managingUrl =  $this->getUrl('*/mapping_attribute',array(
              'attribute_set_id' => $attributeSetId

          )
          )   ;
        $clearUrl = $this->getUrl('*/map_attribute/clearSet',array(
                'mapping_product_type_id'   => $productTypeId,
                'attribute_set_id' => $attributeSetId

            )
        )   ;

        $width = $this->_getStatusWidth($attributeSetId, $productTypeId);
        $styleWidth = 'width: ' . $width;
        return "<div class=\"mapping-action\" align='left'>
                  <a href=\"$managingUrl\">Manage</a>
                  &nbsp &nbsp | &nbsp   &nbsp
                  <a href=\"$clearUrl\"  title=\"Click 'Clear' to clear mapping\" TARGET=\"_top\">Clear</a>

                </div>";
    }
        else {
            $mapNowUrl =  $this->getUrl('*/map_attribute/editSet',array(
                'attribute_set_id' => $attributeSetId));

            return "<div class=\"mapping-action\" align='left'>
                  <a href=\"$mapNowUrl\">Map Now</a>
                </div>";
        }
    }


    public function renderProductType($value, $rows) {
        $data = $rows->getData();
        if ($data['product_type_name'] == 'Not mapped') {
          return  "<div class=\"unmapped\">Unmapped</div>";
        }
        else {
            return $data['product_type_name'];
        }




    }
    /**
     * Calculates status width
     *
     * @return string
     */
    public function _getStatusWidth($attributeSetId, $productTypeId)
    {
        list($mappedAttributes, $attributes) = $this->getMappedAttribute($productTypeId, $attributeSetId);

        $requiredCount = 0;
        $mappedCount = 0;
        foreach ($attributes as $attribute) {
                 $attrData = $attribute->getData();
            if ($attrData['is_required'] == '1') {
                $requiredCount++;
                $mapped = false;
                foreach ($mappedAttributes as $mappedAttr ) {
                     if ($mappedAttr['mapping_attribute_id'] == $attrData['mapping_attribute_id'])  {
                         $mapped = true;
                         break;
                     }
                }

                if ($mapped)
                    $mappedCount++;
            }
    }

        if ($requiredCount == 0) {
        $width = '100%';
        }
        else {
            $width = round($mappedCount * 100 / $requiredCount);
            $width = $width."%";
        }
        return $width;
    }

    public function getMappedAttribute($productTypeId, $attributeSetId)
    {
        $collection = Mage::getResourceModel('xcom_mapping/attribute_collection')->initAttributeRelations(null)
            ->addFieldToFilter('ptr.mapping_product_type_id', $productTypeId)->addFieldToFilter('ptr.attribute_set_id', $attributeSetId)->
            setOrder('relation_attribute_id', Varien_Data_Collection_Db::SORT_ORDER_DESC);
        ;


        $mappedAttributes = $collection->getData();

        $attrCollection = Mage::getResourceModel('xcom_mapping/attribute_collection')
            ->addFilter('mapping_product_type_id', $productTypeId)->addIsAttributeRequiredColumn();

        $attributes = array();
        // filter out attributes whose channels are not enabled
        $enabledChannels = Mage::getModel('xcom_mapping/channel')->getEnabledChannels();
        $attrModel = Mage::getModel('xcom_mapping/attribute');
        foreach ( $attrCollection as $attribute ) {
            if ( $attrModel->isAttributeForChannels($attribute->getMappingAttributeId(), $enabledChannels)) {
                $attributes[] = $attribute;
            }
        }
        return array($mappedAttributes, $attributes);
    }

    /**
     * @return Xcom_Mapping_Block_Adminhtml_Attribute_Set_Grid
     */
    protected function _prepareGrid()
    {
        parent::_prepareGrid();
        $massaction = $this->getColumn('massaction');
        if ($massaction) {
            $massaction->setData('width', '30');
        }
        return $this;
    }


    public function getRowUrl($item)
    {
        $data = $item->getStatus();
        switch($data) {
            case 100:
                $string = "Mapping complete";
                break;
            case 0:
                if (is_null($item->getMappingProductTypeId())) {
                    $string = "Click 'Map Now' to start mapping" ;
                } else {
                    $string = "Click on 'Manage' to resume attribute mapping";
                }
                break;
            default:
                $string = "Click on 'Manage' to resume attribute mapping";
                break;
        }
        return htmlspecialchars($string);
    }

    public function getRowClickCallback() {
        return false;
    }

}
