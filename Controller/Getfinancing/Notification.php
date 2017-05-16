<?php
/**
 * Getfinancing_Getfinancing payment form
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2016 Yameveo (http://www.yameveo.com)
 * @author       Yameveo <yameveo@yameveo.com>
 */


namespace Getfinancing\Getfinancing\Controller\Getfinancing;

class Notification extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        array $data = []
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $rawData = file_get_contents("php://input");
        $post = json_decode($rawData);
        $orderStatus = $post->updates->status;
        $merchantTransactionId = $post->merchant_transaction_id;
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderFactory = $objectManager->get('\Magento\Sales\Model\OrderFactory');

        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()
        ->get('Magento\Framework\App\ResourceConnection');
        $connection= $this->_resources->getConnection();
        $tablename = $this->_resources->getTableName('getfinancing');
        $sql = $connection->select()->from($tablename)
                          ->where('merchant_transaction_id = ?', $merchantTransactionId);
        $result = $connection->fetchAll($sql);
        $orderId = (int)$result[0]['order_id'];
        
        $orderF = $orderFactory->create()->loadByIncrementId($orderId);
        $orderF->setState($orderStatus)->setStatus($orderStatus);

        return $this->resultPageFactory->create();
    }
}
