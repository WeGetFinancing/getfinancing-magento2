<?php
/**
 * Getfinancing_Getfinancing Receive Postbacks from GF and update orders
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
 */

namespace Getfinancing\Getfinancing\Controller\Getfinancing;

class Notification extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $_gfModel;
    protected $om; // Object Manager

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        array $data = []
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $this->om = \Magento\Framework\App\ObjectManager::getInstance();
        $gfModel = $this->om->get('\Getfinancing\Getfinancing\Model\Getfinancing');
        $this->_gfModel = $gfModel; // set GetFinancing Model
    }

    public function execute()
    {
        $rawData = file_get_contents("php://input"); // Get the input with data of the postback
        $post = json_decode($rawData);
        $orderStatus = $post->updates->status;
        $merchantTransactionId = $post->merchant_transaction_id;

        if ($orderStatus != 'rejected' || $this->_gfModel->getDeleteCancelledOrders() == 0) {
            $orderFactory = $this->om->get('\Magento\Sales\Model\OrderFactory');
            $quoteFactory = $this->om->create('\Magento\Quote\Model\QuoteFactory');
            $quoteId = $this->_gfModel->getOrderIdByMerchantTransactionId($merchantTransactionId);
            $q = $quoteFactory->create()->load($quoteId); // Get the quote to have the order data
            $orderId = $q->GetReservedOrderId();
            $orderF = $orderFactory->create()->loadByIncrementId($orderId);
            #$orderF = $orderFactory->create()->load($orderId); // Get the order data (if exists)
            $isEmptyOrder = empty($orderF->getData())?true:false; // Check if order already exists
            if ($isEmptyOrder) { // The order doesn't exist (create it from the quote)
                $q->getPayment()->setMethod('getfinancing_gateway');
                $q->save();
                $quoteManagement = $this->_objectManager->create('\Magento\Quote\Api\CartManagementInterface');
                $orderF = $quoteManagement->submit($q); // Create the order  
            }
            $newOrderStatus = $this->mapOrderStatus($orderStatus); // Only manage some statuses
            $order = $this->updateOrderStatus ($orderF->getEntityId(), $newOrderStatus, $orderStatus);
        }
        $this->getResponse()->setBody('ok');
    }

    public function updateOrderStatus ($orderId, $orderStatus) {
        $order = $this->_objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
        $order->setState($orderStatus)->setStatus($orderStatus);
        $order->save();
        return $order;
    }

    public function mapOrderStatus ($s) {
        switch (strtolower($s)) {
            case "preapproved":
                $s = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT;
                break;
            case "refund":
            case "rejected":
                $s = \Magento\Sales\Model\Order::STATE_CANCELED;
                break;
            case "approved":
                $s = \Magento\Sales\Model\Order::STATE_PROCESSING;
                break;
            default:
                $this->getResponse()->setBody('error, status doesnt exist');
        }
       return $s;
    }
}
