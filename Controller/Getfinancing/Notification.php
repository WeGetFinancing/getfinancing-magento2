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
    protected $_orderId;
    protected $orderSender;

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
        $this->orderSender = $this->om->get('Magento\Sales\Model\Order\Email\Sender\OrderSender');
        //$client = new Raven_Client('https://ab22360f77e34a81a9f444f8b15a38ab@sentry.getfinancing.us/5');
    }

    public function execute()
    {
        $rawData = file_get_contents("php://input"); // Get the input with data of the postback
        $post = json_decode($rawData);
        $orderStatus = $post->updates->status;
        $merchantTransactionId = $post->merchant_transaction_id;

        error_log ("\n ------------- \n: ".print_r('got status', 1), 3, '/tmp/log');

        if ($this->_gfModel->getDeleteCancelledOrders() == 0 || $orderStatus != 'rejected') {
            $orderFactory = $this->om->get('\Magento\Sales\Model\OrderFactory');
            $quoteFactory = $this->om->create('\Magento\Quote\Model\QuoteFactory');
            $quoteId = $this->_gfModel->getOrderIdByMerchantTransactionId($merchantTransactionId);
            $q = $quoteFactory->create()->load($quoteId); // Get the quote to have the order data
            $this->_orderId = $q->GetReservedOrderId();
            $orderF = $orderFactory->create()->loadByIncrementId($this->_orderId);
            #$orderF = $orderFactory->create()->load($this->_orderId); // Get the order data (if exists)
            $isEmptyOrder = empty($orderF->getData())?true:false; // Check if order already exists

            error_log ("\n quoteId: ".$quoteId, 3, '/tmp/log');
            error_log ("\n this->_order_id: ".$this->_orderId, 3, '/tmp/log');
            error_log ("\n orderF: ".$orderF->getEntityId(), 3, '/tmp/log');
            error_log ("\n isEmptyOrder: ".($isEmptyOrder?' empty ':' full order'), 3, '/tmp/log');

            if ($isEmptyOrder) { // The order doesn't exist (create it from the quote)
                $q->getPayment()->setMethod('getfinancing_gateway');
                $q->save();
                $quoteManagement = $this->_objectManager->create('\Magento\Quote\Api\CartManagementInterface');
                $orderF = $quoteManagement->submit($q); // Create the order  
            }
            $newOrderStatus = $this->mapOrderStatus($orderStatus); // Only manage some statuses
            $order = $this->_gfModel->updateOrderStatus ($orderF->getEntityId(), $newOrderStatus);
            if ($newOrderStatus == \Magento\Sales\Model\Order::STATE_COMPLETE) {
                $newStatus = $this->_processOrder(); // Notify user by Email and create invoice, only if status is COMPLETED
            }            
            $this->getResponse()->setBody('ok'); // We don't wan to call Block, just return text
        }
        $this->getResponse()->setBody('cancel, unused status'); // We didn't applly anythinf if we reach this point, so say cancel
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
                $s = \Magento\Sales\Model\Order::STATE_COMPLETE;
                break;
            default: // If we don't know the status, use processing
                $s = \Magento\Sales\Model\Order::STATE_PROCESSING;
        }
       return $s;
    }

    private function _processOrder() { // This method was in \Block\Notification.php but that file is not in use any more
        $order = $this->om->create('\Magento\Sales\Model\Order')->load($this->_orderId);
        $payment = $order->getPayment();
        $sendMail = $this->_gfModel->getSendEmail();
        $createInvoice = $this->_gfModel->getCreateInvoice();
        //if($order->getId() && !$order->canInvoice() && $createInvoice) {  
        if ($createInvoice) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $order->addRelatedObject($invoice);
            $order->save();
        }

        if (!$order->getEmailSent() && $sendMail) {
            // Only send emails when send emails is set in backoffice configuration and email was not sent yet
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(
                __('Client notified with order #%1.', $order->getId())
            )->setIsCustomerNotified(
                true
            )->save();
        }

    }
}
