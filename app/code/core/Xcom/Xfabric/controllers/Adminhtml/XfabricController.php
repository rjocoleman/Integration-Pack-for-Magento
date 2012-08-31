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
 * @package     Xcom_Xfabric
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Xcom_Xfabric_Adminhtml_XfabricController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Test connection to Xfabric with available authorization credentials
     */
    public function testconnectionAction()
    {
        try {
            /* @var $authorizationModel Xcom_Xfabric_Model_Authorization */
            $authorizationModel = Mage::getModel('xcom_xfabric/authorization');
            $destinationId = $authorizationModel->load()->getDestinationId();
            Mage::helper('xcom_xfabric')->send('message/ping',
                array('destination_id' => $destinationId));
            $this->_getSession()->addSuccess($this->__('Connection succeeded.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Connection failed. Reason: %s', $e->getMessage()));
        }

        $this->_redirectReferer();
    }
}
