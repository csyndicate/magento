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
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Eav\Model\Resource\Entity\Attribute;

/**
 * Entity attribute option resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Option extends \Magento\Framework\Model\Resource\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('eav_attribute_option', 'option_id');
    }

    /**
     * Add Join with option value for collection select
     *
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @param \Zend_Db_Expr $valueExpr
     * @return $this
     */
    public function addOptionValueToCollection($collection, $attribute, $valueExpr)
    {
        $adapter = $this->_getReadAdapter();
        $attributeCode = $attribute->getAttributeCode();
        $optionTable1 = $attributeCode . '_option_value_t1';
        $optionTable2 = $attributeCode . '_option_value_t2';
        $tableJoinCond1 = "{$optionTable1}.option_id={$valueExpr} AND {$optionTable1}.store_id=0";
        $tableJoinCond2 = $adapter->quoteInto(
            "{$optionTable2}.option_id={$valueExpr} AND {$optionTable2}.store_id=?",
            $collection->getStoreId()
        );
        $valueExpr = $adapter->getCheckSql(
            "{$optionTable2}.value_id IS NULL",
            "{$optionTable1}.value",
            "{$optionTable2}.value"
        );

        $collection->getSelect()->joinLeft(
            array($optionTable1 => $this->getTable('eav_attribute_option_value')),
            $tableJoinCond1,
            array()
        )->joinLeft(
            array($optionTable2 => $this->getTable('eav_attribute_option_value')),
            $tableJoinCond2,
            array($attributeCode => $valueExpr)
        );

        return $this;
    }

    /**
     * Retrieve Select for update Flat data
     *
     * @param \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute
     * @param int $store
     * @param bool $hasValueField flag which require option value
     * @return \Magento\Framework\DB\Select
     */
    public function getFlatUpdateSelect(
        \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute,
        $store,
        $hasValueField = true
    ) {
        $adapter = $this->_getReadAdapter();
        $attributeTable = $attribute->getBackend()->getTable();
        $attributeCode = $attribute->getAttributeCode();

        $joinConditionTemplate = "%s.entity_id = %s.entity_id" .
            " AND %s.entity_type_id = " .
            $attribute->getEntityTypeId() .
            " AND %s.attribute_id = " .
            $attribute->getId() .
            " AND %s.store_id = %d";
        $joinCondition = sprintf(
            $joinConditionTemplate,
            'e',
            't1',
            't1',
            't1',
            't1',
            \Magento\Store\Model\Store::DEFAULT_STORE_ID
        );
        if ($attribute->getFlatAddChildData()) {
            $joinCondition .= ' AND e.child_id = t1.entity_id';
        }

        $valueExpr = $adapter->getCheckSql('t2.value_id > 0', 't2.value', 't1.value');
        /** @var $select \Magento\Framework\DB\Select */
        $select = $adapter->select()->joinLeft(
            array('t1' => $attributeTable),
            $joinCondition,
            array()
        )->joinLeft(
            array('t2' => $attributeTable),
            sprintf($joinConditionTemplate, 't1', 't2', 't2', 't2', 't2', $store),
            array($attributeCode => $valueExpr)
        );

        if ($attribute->getFrontend()->getInputType() != 'multiselect' && $hasValueField) {
            $valueIdExpr = $adapter->getCheckSql('to2.value_id > 0', 'to2.value', 'to1.value');
            $select->joinLeft(
                array('to1' => $this->getTable('eav_attribute_option_value')),
                "to1.option_id = {$valueExpr} AND to1.store_id = 0",
                array()
            )->joinLeft(
                array('to2' => $this->getTable('eav_attribute_option_value')),
                $adapter->quoteInto("to2.option_id = {$valueExpr} AND to2.store_id = ?", $store),
                array($attributeCode . '_value' => $valueIdExpr)
            );
        }

        if ($attribute->getFlatAddChildData()) {
            $select->where('e.is_child = 0');
        }

        return $select;
    }
}
