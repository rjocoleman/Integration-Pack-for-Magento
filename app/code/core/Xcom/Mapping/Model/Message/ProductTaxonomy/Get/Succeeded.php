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

class Xcom_Mapping_Model_Message_ProductTaxonomy_Get_Succeeded
    extends Xcom_Xfabric_Model_Message_Response
{
    const CLASSES_LOADED_FLAG_PATH = 'xcom/mapping/product_classes/loaded';

    /**
     * init message
     */
    protected function _construct()
    {
        $this->_topic = 'productTaxonomy/getSucceeded';
        $this->_schemaRecordName = 'GetProductTaxonomySucceeded';
        parent::_construct();
    }

    /**
     * check using correlationId to see whether this is part of initialization
     * @return bool
     */
    protected function isInitializing()
    {
        $correlationId = $this->getCorrelationId();
        if ( !empty($correlationId)) {
            $jobCollection = Mage::getResourceModel('xcom_initializer/job_collection')
                ->addFieldToFilter('correlation_id', $correlationId);

            foreach ( $jobCollection as $job) {
                if ( $job->getStatus() < Xcom_Initializer_Model_Job::STATUS_SAVED ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getExistingProductTypes()
    {
        $productTypes = array();
        $productTypeData = Mage::getResourceModel('xcom_mapping/product_type')->getAllProductType();
        foreach ( $productTypeData as $productType ) {
            $productTypes[$productType['product_type_id']]
                = array('version' => $productType['version'],
                    'status' => $productType['status']);
        }
        return $productTypes;
    }

    protected function getUpdatedProductTypeIds(&$data)
    {
        $updatedProductTypeIds = array();
        $existingProductTypes = $this->getExistingProductTypes();
        foreach($data['productTaxonomy']['productClasses'] as $productClass) {
            if ( $productClass['productTypeIdVersions'] == null ) {
                continue;
            }
            foreach ( $productClass['productTypeIdVersions'] as  $entry) {
                $productTypeId = $entry['productTypeId'];
                $version = $entry['version'];
                if ( !array_key_exists($productTypeId, $existingProductTypes)) {
                    //create an empty product type as placeholder
                    $productType = Mage::getModel('xcom_mapping/product_type');
                    $productType->setProductTypeId($productTypeId);
                    $productType->setVersion('placeholder');
                    //need to set default locale in order to load the product type later
                    $productType->setLocaleCode('en_US');
                    $productType->setStatus(Xcom_Mapping_Model_Product_Type::PRODUCT_TYPE_STATUS_PENDING_UPDATE);
                    $productType->save();
                    $updatedProductTypeIds[] = $productTypeId;
                }
                elseif ( $existingProductTypes[$productTypeId]['version'] != $version
                    && $existingProductTypes[$productTypeId]['status']!=Xcom_Mapping_Model_Product_Type::PRODUCT_TYPE_STATUS_PENDING_UPDATE) {
                    //update the status of the product type
                    $productType = Mage::getModel('xcom_mapping/product_type')
                        ->load( $productTypeId, 'product_type_id');
                    $productType->setStatus(Xcom_Mapping_Model_Product_Type::PRODUCT_TYPE_STATUS_PENDING_UPDATE);
                    $productType->save();
                    $updatedProductTypeIds[] = $productTypeId;
                }
            }
        }
        return $updatedProductTypeIds;
    }

    public function sendProductTypeGetMessage($productTypeIds, $locale) {
        $message =  Mage::helper('xcom_xfabric')->getMessage('productTaxonomy/productType/get');
        if (!$message) {
            throw Mage::exception('Xcom_Xfabric',
                Mage::helper('xcom_xfabric')->
                    __("Message for topic productTaxonomy/productType/get should be created"));
        }

        $data = array();
        $data['country'] = $locale['country'];
        $data['language'] = $locale['language'];
        $data['product_type_ids'] = $productTypeIds;

        $message->process(new Varien_Object($data));

        // we set the flag to make requests slower since it adds a timeout to wait for responce
        $message->setIsWaitResponse(false);

        $correlationId = $message->getCorrelationId();
        try {
            Mage::helper('xcom_xfabric')->getTransport()
                ->setMessage($message)
                ->send();
        } catch (Exception $ex) {
            //do nothing to allow continuing of job processing
            Mage::log('caught exception: ' . $ex);
            return null;
        }
        return $correlationId;
    }

    protected function getProductTypeGetMessageParams($productTypeIds, $locale)
    {
        $data = array();
        $data['country'] = $locale['country'];
        $data['language'] = $locale['language'];
        $data['product_type_ids'] = $productTypeIds;

        $params = json_encode($data);

        return $params;
    }

    protected function initiateProductTypeUpdate(&$data, $localeCode, $initializing)
    {
        $updatedProductTypeIds = $this->getUpdatedProductTypeIds($data);
        if ( count($updatedProductTypeIds) == 0 ) {
            return;
        }
        // send out message to get product types, 100 at a time
        //for all locale
        $locales = array(
            array('country' => 'US',  'language'=> 'en'),
            array('country' => 'GB',  'language'=> 'en'),
            array('country' => 'DE',  'language'=> 'de'),
            array('country' => 'FR',  'language'=> 'fr'),
            array('country' => 'AU',  'language'=> 'en'),
        );
        $batches = array_chunk($updatedProductTypeIds, 100);
        foreach ( $batches as $batch ) {
            foreach ( $locales as $locale ) {
                if ( $initializing) {
                    // create a job record and allow initializer to send and process message
                    $params = $this->getProductTypeGetMessageParams($batch, $locale);
                    $jobModel = Mage::getModel('xcom_initializer/job');
                    $jobModel->setStatus(Xcom_Initializer_Model_Job::STATUS_PENDING)
                        ->setMessageParams($params)
                        ->setTopic('productTaxonomy/productType/get')
                        ->save();
                }
                else {
                    //send the message directly
                    $this->sendProductTypeGetMessage($batch, $locale);
                }
            }
        }
    }

    /**
     * Process message body and store result in database
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_Get_Succeeded
     */
    public function process()
    {
        parent::process();
        $data = $this->getBody();

        //check whether this is part of initialization process
        //if this is part of initialization process, create additional
        //initialization jobs if necessary (productTaxonomy/productType/get)
        $initializing = $this->isInitializing();

        $localeCode = 'en_US';
        if (!is_null($data['locale'])) {
            $localeCode = $data['locale']['language'] . '_' . $data['locale']['country'];
        }
        $this->setLocaleCode($localeCode);

        $this->_deleteProductClasses($data);

        foreach ($data['productTaxonomy']['productClasses'] as $productClass) {
            $this->saveProductClass($productClass);
        }

        $this->setLoadedFlagConfig();
        $this->initiateProductTypeUpdate($data, $localeCode, $initializing);
        return $this;
    }

    /**
     * Clean product classes from database which are not present in given $data array.
     *
     * @param array $data
     * @return Xcom_Mapping_Model_Message_ProductTaxonomy_Get_Succeeded
     */
    protected function _deleteProductClasses(array $data)
    {
        $productClassIds = $this->_collectProductClassIds($data);

        $oldIds = Mage::getSingleton('xcom_mapping/product_class')->getCollection()
            ->addFieldToFilter('product_class_id', array('nin' => $productClassIds))
            ->setLocaleCode($this->getLocaleCode())
            ->getAllIds();

        Mage::getSingleton('xcom_mapping/product_class')->deleteByIds($oldIds);
        return $this;
    }

    /**
     * Collect product class ids from response.
     *
     * @param array $data
     * @return array
     */
    protected function _collectProductClassIds(array $data)
    {
        $result = array();
        foreach ($data['productTaxonomy']['productClasses'] as $productClass) {
           $result[] = $productClass['id'];
            if ($productClass['subClasses'] !== null) {
                foreach($productClass['subClasses'] as $productSubClass) {
                    $result[] = $productSubClass['id'];
                }
            }
        }
        return $result;
    }

    public function setLoadedFlagConfig()
    {
        Mage::getConfig()->saveConfig(self::CLASSES_LOADED_FLAG_PATH, 1);
        return $this;
    }

    /**
     * Save product class data to storage
     *
     * @param array $productClass
     * @param $parent
     * @return mixed
     */
    public function saveProductClass(array $productClass, $parent = null)
    {
        $classEntity = Mage::getModel('xcom_mapping/product_class');

        $data = array(
            'product_class_id'    => $productClass['id'],
            'name'  => $productClass['name'],
            'parent_product_class_id' => $parent,
            'locale_code' => $this->getLocaleCode()
        );

        $classEntity->setData($data);
        $classEntity->save();

        if ($productClass['subClasses'] !== null) {
            foreach ($productClass['subClasses'] as $subClass) {
                $this->saveProductClass($subClass, $classEntity->getId());
            }
        }
        return $classEntity->getId();
    }
}
