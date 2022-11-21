<?php

namespace Getfinancing\Getfinancing\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();


            // Create getfinancing table
            $table = $installer->getConnection()
                ->newTable($installer->getTable('getfinancing'))
                ->addColumn(
                    'order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'order_id'
                )
                ->addColumn(
                    'merchant_transaction_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'merchant_transaction_id'
                )
                ->addColumn(
                    'order_data',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BLOB,
                    null,
                    ['nullable' => true, 'default' => ''],
                    'order_data'
                )
                ->setComment('GetFinancing Table')
                ->setOption('type', 'InnoDB')
                ->setOption('charset', 'utf8');
            $installer->getConnection()->createTable($table);


        $installer->endSetup();
    }
}
