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
 * @package     Xcom_ChannelGroup
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Xcom_ChannelGroup_Adminhtml_Action extends Mage_Adminhtml_Controller_Action
{
    /**
     * Constructor.
     *
     * @return void
     */
    protected function _construct()
    {
        // Define module dependent translate
        $this->setUsedModuleName('Xcom_ChannelGroup');
    }

    /**
     * Initialize channel type from request parameters
     *
     * @param string $channelTypeCode
     * @return Varien_Object
     */
    protected function _initChannelType($channelTypeCode = 'type')
    {
        $code = $this->getRequest()->getParam($channelTypeCode);
        /** @var $config Xcom_ChannelGroup_Model_Config_Channeltype */
        $config = Mage::getModel('xcom_channelgroup/config_channeltype');
        if (!empty($code)) {
            $channelType = $config->getChanneltype($code);
        } else {
            $channelType = $config->getDefault();
        }
        Mage::register('current_channeltype', $channelType);
        return $channelType;
    }

    /**
     * Retrieve currently edited channeltype object.
     *
     * @return Varien_Object
     */
    public function getChannelType()
    {
        if (null === Mage::registry('current_channeltype')) {
            $this->_initChannelType();
        }
        return Mage::registry('current_channeltype');
    }
}