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
        $rawData = file_get_contents("php://input"); // Get the input with data of the postback
        $post = json_decode($rawData);
        $orderStatus = $post->updates->status;
        $merchantTransactionId = $post->merchant_transaction_id;
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $orderFactory = $objectManager->get('\Magento\Sales\Model\OrderFactory');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');
        
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()
        ->get('Magento\Framework\App\ResourceConnection');
        $connection= $this->_resources->getConnection();
        $tablename = $this->_resources->getTableName('getfinancing');
        $sql = $connection->select()->from($tablename)
                          ->where('merchant_transaction_id = ?', $merchantTransactionId);
        $result = $connection->fetchAll($sql);
        $quoteId = (int)$result[0]['order_id']; // This is the quote ID, with it we get the order (if order exists)

        $q = $quoteFactory->create()->load($quoteId); // Get the quote to have the order data
        $orderId = $q->GetReservedOrderId();
        $orderF = $orderFactory->create()->loadByIncrementId($orderId); // Get the order data (if exists)
        
        if (empty($orderF->getData())) { // There is no order yet (just items in cart "quote")
            $orderF->setState($orderStatus)->setStatus($orderStatus); // Set the new status
            $q->getPayment()->setMethod('getfinancing_gateway');
            $q->save();
            $quoteManagement = $this->_objectManager->create('\Magento\Quote\Api\CartManagementInterface');
            $order = $quoteManagement->submit($q); // Create the order  
        } else {
            die ('The order already exists, will not change it');
        }
        die('ok');
    }
}
