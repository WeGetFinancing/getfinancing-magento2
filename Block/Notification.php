<?php
/**
 * Getfinancing_Getfinancing payment form
* @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
 */

namespace Getfinancing\Getfinancing\Block;

use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use \Magento\Sales\Model\Order\Invoice;

class Notification extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    protected $_order;
    protected $_payment;

    /**
     * @var OrderSender
     */
    protected $orderSender;

    protected $_gfModel;
//    protected $_customerRepository;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Item $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        \Getfinancing\Getfinancing\Model\Getfinancing $getfinancing,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderSender $orderSender,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_gfModel = $getfinancing;
        $this->_orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }
    

    /**
     * Prepares FORM Data to send to gateway
     *
     * @return void
     */
    
    protected function prepareBlockData()
    { // THIS FILE IS NEVER CALLED, we finish in controller with setBody method to show text
        
        $json = file_get_contents('php://input'); // Receive the postback data
        $temp = json_decode($json,true);

        $merchant_transaction_id = $temp['merchant_transaction_id'];
        $orderId = $this->_gfModel->getOrderIdByMerchantTransactionId($merchant_transaction_id);

        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

        $this->_order = $order;
        $payment = $order->getPayment();
        $this->_payment = $payment;
        

        if ($order->getId()) {
            switch ($temp['updates']['status']) {
                case 'approved':
                    $payment->setLastTransId($orderId);
                    $order->save();
                    $this->_processOrder();
                    break;
                case 'rejected':
                    //we only set order as cancelled if it is not completed yet.
                    if ($order->getStatus() != order::STATE_COMPLETE ){
                      $payment->setLastTransId($orderId);
                      $order->setState(order::STATE_CANCELED);
                      $order->setStatus(order::STATE_CANCELED);
                      $order->save();
                    }
                    break;
            }
        }
    }

    private function _processOrder() {
        $order = $this->_order;
        $payment = $this->_payment;

        $sendMail = $this->_gfModel->getSendEmail();
        $createInvoice = $this->_gfModel->getCreateInvoice();



        if($order->getId() && !$order->canInvoice() && $createInvoice) {
            return;
        } else {
            $order->setState(order::STATE_COMPLETE);
            $order->setStatus(order::STATE_COMPLETE);
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $order->addRelatedObject($invoice);
            $order->save();
        }
         if (!$order->getEmailSent() && $sendMail) {
            $this->orderSender->send($this->_order);
            $this->_order->addStatusHistoryComment(
                __('Client notified with order #%1.', $order->getId())
            )->setIsCustomerNotified(
                true
            )->save();
         }
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
            && $this->isVisible($order);
    }
}
