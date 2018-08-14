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

        if ($orderStatus != 'rejected' || $this->_gfModel->getDeleteCancelledOrders() == 0) {
            $orderFactory = $this->om->get('\Magento\Sales\Model\OrderFactory');
            $quoteFactory = $this->om->create('\Magento\Quote\Model\QuoteFactory');
            $quoteId = $this->_gfModel->getOrderIdByMerchantTransactionId($merchantTransactionId);
            $q = $quoteFactory->create()->load($quoteId); // Get the quote to have the order data
            $this->_orderId = $q->GetReservedOrderId();
            $orderF = $orderFactory->create()->loadByIncrementId($this->_orderId);
            #$orderF = $orderFactory->create()->load($this->_orderId); // Get the order data (if exists)
            $isEmptyOrder = empty($orderF->getData())?true:false; // Check if order already exists
            if ($isEmptyOrder) { // The order doesn't exist (create it from the quote)
                $q->getPayment()->setMethod('getfinancing_gateway');
                $q->save();
                $quoteManagement = $this->_objectManager->create('\Magento\Quote\Api\CartManagementInterface');
                $orderF = $quoteManagement->submit($q); // Create the order  
            }
            $newOrderStatus = $this->mapOrderStatus($orderStatus); // Only manage some statuses
            $order = $this->_gfModel->updateOrderStatus ($orderF->getEntityId(), $newOrderStatus, $orderStatus);
            /* TODO: check here if order exists, if process it, if invoice, if sendmail etc */
            // If new status is not false use it, if no new status, use processing
            $newStatus = $this->_processOrder();
            $s = ($newStatus)?$newStatus:\Magento\Sales\Model\Order::STATE_PROCESSING;

        }
        $this->getResponse()->setBody('ok'); // We don't wan to call Block, just return text
    }

    public function mapOrderStatus ($s) {
        error_log ("\n status: \n ".print_r($s, 1), 3, '/tmp/log');
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
                error_log ("\n unknown status \n" , 3, '/tmp/log');
                $this->getResponse()->setBody('error, status doesnt exist');
        }
       return $s;
    }

    private function _processOrder() { // This method was in \Block\Notification.php but that file is not in use any more
        $order = $this->om->create('\Magento\Sales\Model\Order')->load($this->_orderId);
        error_log ("\n orderiD: \n". $this->_orderId, 3, '/tmp/log');
        $payment = $order->getPayment();
        $sendMail = $this->_gfModel->getSendEmail();
        $createInvoice = $this->_gfModel->getCreateInvoice();
        $retStatus = false;
        if($order->getId() && !$order->canInvoice() && $createInvoice) {
            error_log ("\n caninvoice \n".$order->canInvoice(), 3, '/tmp/log');
            error_log ("\n create invoice \n".$createInvoice, 3, '/tmp/log');
            error_log ("\n return nothing \n", 3, '/tmp/log');
            // return $retStatus; 
        } else {
            error_log ("\n before state complete \n", 3, '/tmp/log');
            $retStatus = \Magento\Sales\Model\Order::STATE_COMPLETE;
            error_log ("\n after state complete \n", 3, '/tmp/log');
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $order->addRelatedObject($invoice);
            $order->save();
            error_log ("\n before save prepare invoice \n", 3, '/tmp/log');
        }
        if (!$order->getEmailSent() && $sendMail) {
            error_log ("\n Send email \n", 3, '/tmp/log');
            $this->orderSender->send($order);
            $order->addStatusHistoryComment(
                __('Client notified with order #%1.', $order->getId())
            )->setIsCustomerNotified(
                true
            )->save();
        }
        return $retStatus;
    }
}
