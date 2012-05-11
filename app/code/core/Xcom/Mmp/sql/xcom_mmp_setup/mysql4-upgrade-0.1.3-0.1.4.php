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

/** @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

//remove all authorization records because of adding new field with unique key
$this->run("
TRUNCATE {$this->getTable('xcom_mmp/account')};
");
$this->getConnection()
    ->addColumn($this->getTable('xcom_mmp/account'), 'xaccount_id', "VARCHAR(255) NOT NULL after `auth_id`");
$this->getConnection()->addKey($this->getTable('xcom_mmp/account'),
    'UNQ_XCOM_XACCOUNTID', array('xaccount_id'), 'unique');

$this->endSetup();