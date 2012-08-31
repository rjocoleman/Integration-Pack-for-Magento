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

/**
 * @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup
 */
$installer      = $this;

$channels = array(
    Array(
        'channel_code' => "ebay_US",
        'is_enabled' => true,
    ),
    Array(
        'channel_code' => "ebay_AU",
        'is_enabled' => true,
    ),
    Array(
        'channel_code' => "ebay_DE",
        'is_enabled' => true,
    ),
    Array(
        'channel_code' => "ebay_FR",
        'is_enabled' => true,
    ),
    Array(
        'channel_code' => "ebay_UK",
        'is_enabled' => true,
    ),
    Array(
        'channel_code' => "google_U",
        'is_enabled' => false,
    ),
    Array(
        'channel_code' => "GS1",
        'is_enabled' => false,
    ),
);

foreach ($channels as $channel) {
    $channelModel = Mage::getModel('xcom_mapping/channel');
    $channelModel->setChannelCode($channel['channel_code']);
    $channelModel->setIsEnabled($channel['is_enabled']);
    $channelModel->save();
}
