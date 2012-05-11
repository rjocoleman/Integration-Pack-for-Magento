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
 * @package     Xcom_Ebay
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Xcom_Ebay_Block_Adminhtml_Product_Edit_Tab_Listingerror extends Mage_Adminhtml_Block_Widget_Container
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     *  Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('xcom/ebay/product/tab/listingerror.phtml');
    }

    /**
     * Prepare Layout
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->setChild('xcom_ebay_product_tab_listingerror_form',
            $this->getLayout()->createBlock('xcom_ebay/adminhtml_product_edit_tab_listingerror_form'));
        $this->setChild('xcom_ebay_product_tab_listingerror_grid',
            $this->getLayout()->createBlock('xcom_ebay/adminhtml_product_edit_tab_listingerror_grid'));
        return parent::_prepareLayout();
    }

    /**
     * ######################## TAB settings #################################
     */
    public function getTabLabel()
    {
        return $this->__('Channel Listing Log');
    }

    public function getTabTitle()
    {
        return $this->__('Channel Listing Log');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}