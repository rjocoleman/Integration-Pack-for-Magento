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
 * @package     Xcom_Initializer
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Xcom_Initializer_Model_Resource_Job extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('xcom_initializer/job', 'job_id');
    }

    /**
     * Update column status
     *
     * @param $status
     * @param $correlationId
     * @param array $filter
     */
    public function updateStatusByCorrelationId($status, $correlationId, $filter = array())
    {
        $where = array('correlation_id = ?' => $correlationId);
        $this->_getWriteAdapter()
            ->update($this->getMainTable(), array('status' => $status), array_merge($where, $filter));
    }

    /**
     * Insert new job and return affecter row id
     *
     * @param $topic
     * @param $messageParams
     * @param $status
     * @return string
     */
    public function addSavedJob($topic, $messageParams, $status)
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->insert($this->getMainTable(), array(
            'topic'          => $topic,
            'message_params' => $messageParams,
            'status'         => $status
        ));
        return $adapter->lastInsertId();
    }

    /**
     * Return jobId by correlation id
     *
     * @param $correlationId
     * @return array
     */
    public function getJobIdByCorrelationId($correlationId)
    {
        $adapter = $this->_getReadAdapter();
        $where = $adapter->quoteInto('correlation_id = ?', $correlationId);
        $select = $adapter->select()
            ->from($this->getMainTable(), 'job_id')
            ->where($where);

        return $adapter->fetchOne($select);
    }


    /**
     * Check that all message topics were sent and all of them are saved
     *
     * @param int $topicsAmount - amount of required topics
     * @return bool
     */
    public function isDataCollected($topicsAmount)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('count' => new Zend_Db_Expr('COUNT(job_id)')))
            ->where('status <> ?', Xcom_Initializer_Model_Job::STATUS_SAVED);

        if (!$this->_getReadAdapter()->fetchOne($select)) {
            $select = $this->_getReadAdapter()->select()
                ->from($this->getMainTable(), array())
                ->columns('COUNT(DISTINCT topic)');
            if ($this->_getReadAdapter()->fetchOne($select) == $topicsAmount) {
                return true;
            }
        }
        return false;
    }

    /**
     * Restore jobs, where updated_at is expired
     * We set STATUS_PENDING back for records which STATUS_SENT or STATUS_RECEIVED is expired
     *
     * @return Xcom_Initializer_Model_Resource_Job
     */
    public function reviveExpiredJobs()
    {
        $adapter = $this->_getWriteAdapter();
        $adapter->update($this->getMainTable(), array(
            'status' => Xcom_Initializer_Model_Job::STATUS_PENDING),
            '( DATE_ADD(updated_at, INTERVAL ' . Xcom_Initializer_Model_Job::SENT_EXPIRATION_MINUTES .' MINUTE) <= NOW() ' .
            ' AND status = '.Xcom_Initializer_Model_Job::STATUS_SENT .
            ') OR ('.
            ' DATE_ADD(updated_at, INTERVAL ' . Xcom_Initializer_Model_Job::RECEIVED_EXPIRATION_MINUTES .' MINUTE) <= NOW() ' .
            ' AND status = ' . Xcom_Initializer_Model_Job::STATUS_RECEIVED . ' )'
        );

        return $this;
    }

    /**
     * Verifies if all jobs for the provided topic were finished
     *
     * @param $topic
     * @return bool
     */
    public function isAllJobsFinished($topic)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('count' => new Zend_Db_Expr('COUNT(job_id)')))
            ->where('topic = ?', $topic);
        if (!$this->_getReadAdapter()->fetchOne($select)) {
            return false;
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('count' => new Zend_Db_Expr('COUNT(job_id)')))
            ->where('status <> ?', Xcom_Initializer_Model_Job::STATUS_SAVED)
            ->where('topic = ?', $topic);

        return !$this->_getReadAdapter()->fetchOne($select);
    }

    /**
     * Count jobs for status grid
     * @param $topic
     * @return array
     */
    public function getJobCounts($topic)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array(
                'total'     => new Zend_Db_Expr('COUNT(job_id)'),
                'wait_since'=> new Zend_Db_Expr('IF(MIN(status) <> ' .
                    Xcom_Initializer_Model_Job::STATUS_SAVED . ', MIN(updated_at), null)'),
                'sent'      => new Zend_Db_Expr('COUNT(IF(status = ' .
                    Xcom_Initializer_Model_Job::STATUS_SENT . ', job_id, null))'),
                'received'  => new Zend_Db_Expr('COUNT(IF(status = ' .
                    Xcom_Initializer_Model_Job::STATUS_RECEIVED . ', job_id, null))'),
                'saved'     => new Zend_Db_Expr('COUNT(IF(status = ' .
                    Xcom_Initializer_Model_Job::STATUS_SAVED . ', job_id, null))'),
                'inprocess' => new Zend_Db_Expr('COUNT(IF(status <> ' .
                    Xcom_Initializer_Model_Job::STATUS_SAVED . ', job_id, null))'),
            ))
            ->where('topic = ?', $topic);
        return $this->_getReadAdapter()->fetchRow($select);
    }
}
