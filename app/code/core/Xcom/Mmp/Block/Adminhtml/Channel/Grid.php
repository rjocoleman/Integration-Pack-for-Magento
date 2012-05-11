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
 * @package     Xcom_Mmp
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Xcom_Mmp_Block_Adminhtml_Channel_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Set grid params
     */
    public function __construct()
    {
        parent::__construct();
        $this->setMassactionBlockName('xcom_mmp/adminhtml_widget_grid_massaction');
        $this->setDefaultSort('title');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare collection.
     *
     * @return Xcom_Mmp_Block_Adminhtml_Channel_Grid
     */
    protected function _prepareCollection()
    {
        $channelType = Mage::registry('current_channeltype');

        $collection = Mage::getResourceModel('xcom_mmp/channel_collection')
            ->addChanneltypeCodeFilter($channelType->getCode())
            ->addWebsiteStoreInfo()
            ->addUserIdTextField()
            ->addPolicyNames();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Customize add filter to collection.
     *
     * @param $column
     * @return Mage_Adminhtml_Block_Widget_Grid|Xcom_Mmp_Block_Adminhtml_Channel_Grid
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {
            if (!in_array($column->getId(), array('user_id_text', 'policy_name', 'marketplace'))) {
                return parent::_addColumnFilterToCollection($column);
            }
            $field = ( $column->getFilterIndex() ) ? $column->getFilterIndex() : $column->getIndex();
            $cond = $column->getFilter()->getCondition();
            if ($field && isset($cond)) {
                $this->getCollection()->getSelect()->having("$field LIKE '{$cond['like']}'");
            }
        }
        return $this;
    }

    /**
     * Add columns to grid
     *
     * @return Xcom_Mmp_Block_Adminhtml_Channel_Grid
     */
    protected function _prepareColumns()
    {
        /** @var $systemStore Mage_Adminhtml_Model_System_Store */
        $systemStore = Mage::getModel('adminhtml/system_store');

        $this->addColumn('title', array(
            'header'        => $this->__('Name'),
            'align'         => 'left',
            'index'         => 'name',
            'filter_index'  => 'main_table.name',
        ));

        $this->addColumn('store', array(
            'header'        => $this->__('Store View'),
            'type'          => 'options',
            'index'         => 'store_id',
            'options' => $systemStore->getStoreOptionHash(),
            'option_groups' => $systemStore->getStoreValuesForForm(),
            'filter_index'  => 'main_table.store_id',
        ));

        $this->addColumn('marketplace', array(
            'header'    => $this->__('User ID'),
            'index'      => 'marketplace',
        ));

        $this->addColumn('policy_name', array(
            'header'    => $this->__('Policy'),
            'index'      => 'policy_name',
            'align'     => 'left',
        ));

        $this->addColumn('is_active', array(
            'header'    => $this->__('Channel Status'),
            'width'     => 90,
            'index'     => 'is_active',
            'type'      => 'options',
            'options'   => array(
                1  => $this->__('Enabled'),
                0  => $this->__('Disabled')),
            'filter_index' => 'main_table.is_active',
        ));

        $this->addColumn('action', array(
                'header'    => $this->__('Action'),
                'type'      => 'action',
                'width'     => '80px',
                'getter'    => 'getId',
                'filter'    => false,
                'sortable'  => false,
                'actions'   => array(array(
                    'caption' => $this->__('Edit'),
                    'url'     => array(
                        'base'   => '*/mmp_channel/edit',
                    ),
                    'field'   => 'id'
                )),
                'index'     => 'stores',
        ));

        return parent::_prepareColumns();
    }

    /**
     * Prepare grid mass-action block
     *
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('main_table.channel_id');
        $this->getMassactionBlock()->setFormFieldName('selected_channels');

        $this->getMassactionBlock()->addItem('enable', array(
             'label'=> $this->__('Enable'),
             'url'  => $this->getUrl('*/*/massEnable'),
             'confirm'  => $this->__('Are you sure you want to enable the channel(s)?')
        ));
        $this->getMassactionBlock()->addItem('disable', array(
             'label'=> $this->__('Disable'),
             'url'  => $this->getUrl('*/*/massDisable'),
             'confirm' => $this->__('Are you sure you want to disable the channel(s)?'),
             'validate_url' => $this->getUrl('*/*/massDisableValidation'),
        ));

        return parent::_prepareMassaction();
    }

}
