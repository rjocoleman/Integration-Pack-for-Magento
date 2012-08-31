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

$this->startSetup();

/**
 * @var $this Mage_Core_Model_Resource_Setup
 * @var $table Varien_Db_Ddl_Table
 */

/**
 * Create table xcom_mapping_channel
 */
$this->run("
DROP TABLE IF EXISTS `{$this->getTable('xcom_mapping/channel')}`;
");
$table = $this->getConnection()
    ->newTable($this->getTable('xcom_mapping/channel'))
    ->addColumn('channel_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Channel Id')
    ->addColumn('channel_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 128, array(
        'nullable'  => false,
    ),'Channel Code')
    ->addColumn('is_enabled', Varien_Db_Ddl_Table::TYPE_TINYINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
    ), 'Is Enabled')
    ->addIndex('UNQ_ATTRIBUTE_CHANNEL_ID',
        array('channel_id'), array('type' => 'unique'))
    ->setOption('collate', null)
    ->setOption('comment', 'Xcom Mapping Channel');

$this->getConnection()->createTable($table);

$this->getConnection()->modifyColumn($table->getName(),
    'channel_id', 'int(10) unsigned NOT NULL auto_increment');

$this->getConnection()
    ->addColumn($this->getTable('xcom_mapping/product_type'), 'status', Varien_Db_Ddl_Table::TYPE_SMALLINT);

//make sure that the following indices are unique
$this->getConnection()->addKey($this->getTable('xcom_mapping/product_class'), 'UNQ_TARGET_PRODUCT_CLASS_ID',
    array('product_class_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/product_class_locale'), 'UNQ_PRODUCT_CLASS_ID_CHANNEL_CODE',
    array('mapping_product_class_id', 'locale_code'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/product_class_type'), 'UNQ_PRODUCT_CLASS_ID_TYPE_ID',
    array('mapping_product_class_id', 'mapping_product_type_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/product_type'), 'UNQ_TARGET_PRODUCT_TYPE_ID',
    array('product_type_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute'), 'UNQ_PRODUCT_TYPE_ID_ATTRIBUTE_ID',
    array('mapping_product_type_id', 'attribute_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute_value'), 'UNQ_MAPPING_ATTRIBUTE_ID_VALUE_ID',
    array('mapping_attribute_id', 'value_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/product_type_locale'), 'UNQ_PRODUCT_TYPE_ID_CHANNEL_CODE',
    array('mapping_product_type_id', 'locale_code'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute_locale'), 'UNQ_MAPPING_ATTRIBUTE_ID_CHANNEL_CODE',
    array('mapping_attribute_id', 'locale_code'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute_value_locale'), 'UNQ_MAPPING_VALUE_ID_CHANNEL_CODE',
    array('mapping_value_id', 'locale_code'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/product_type_relation'), 'UNQ_PRODUCT_TYPE_RELATION_ATTRIBUTE_SET_ID',
    array('attribute_set_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute_relation'), 'UNQ_ATTRIBUTE_RELATION_RELATION_ATTRIBUTE_ID',
    array('relation_product_type_id', 'attribute_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute_value_relation'), 'UNQ_ATTRIBUTE_RELATION_RELATION_VALUE_ID',
    array('relation_attribute_id', 'value_id'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute_value_relation'), 'UNQ_ATTRIBUTE_RELATION_RELATION_HASH_VALUE',
    array('relation_attribute_id', 'hash_value'), 'unique');
$this->getConnection()->addKey($this->getTable('xcom_mapping/attribute_channel'), 'UNQ_MAPPING_ATTRIBUTE_ID_CHANNEL_CODE',
    array('mapping_attribute_id', 'channel_code'), 'unique');

$this->endSetup();
