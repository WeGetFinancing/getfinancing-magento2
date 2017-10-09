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
        $orderF = $orderFactory->create()->loadByIncrementId($orderId);

        #$orderF = $orderFactory->create()->load($orderId); // Get the order data (if exists)
        $isEmptyOrder = empty($orderF->getData())?true:false; // Check if order already exists

        if ($isEmptyOrder) { // The order doesn't exist (create it from the quote)
            $q->getPayment()->setMethod('getfinancing_gateway');
            $q->save();
            die ('is empty, create it');
            $quoteManagement = $this->_objectManager->create('\Magento\Quote\Api\CartManagementInterface');
            $orderF = $quoteManagement->submit($q); // Create the order  
        } 
        $newOrderStatus = $this->mapOrderStatus($orderStatus);
        $order = $this->updateOrderStatus ($orderF->getEntityId(), $newOrderStatus, $orderStatus);
        die('ok');
    }

    public function updateOrderStatus ($orderId, $orderStatus) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
        $order->setState($orderStatus)->setStatus($orderStatus);
        $order->save();
        return $order;
    }

    public function mapOrderStatus ($s) {
        switch (strtolower($s)) {
            case "preapproved":
                $s = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;
            case "refund":
                $s = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                break;
            case "approved":
                $s = \Magento\Sales\Model\Order::STATE_COMPLETE;
                break;
            default:
                $s = \Magento\Sales\Model\Order::STATE_HOLDED;
        }
       return $s;
    }
}
