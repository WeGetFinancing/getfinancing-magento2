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

    protected $_pagantis;
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
        \Getfinancing\Getfinancing\Model\Getfinancing $pagantis,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderSender $orderSender,

        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_pagantis = $pagantis;
        $this->_orderFactory = $orderFactory;
        $this->_logger = $context->getLogger();
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
    {
        $json = file_get_contents('php://input');
        $temp = json_decode($json,true);
        $debug = $this->_pagantis->getDebug();

        if($debug){
            $this->_logger->debug('GETFINANCING - Starting order update');
        }

        $merchant_transaction_id = $temp['merchant_transaction_id'];

        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()
        ->get('Magento\Framework\App\ResourceConnection');
        $connection= $this->_resources->getConnection();

        $tablename = $this->_resources->getTableName('getfinancing');
        $sql = $connection->select()->from($tablename, 'order_id')->where('merchant_transaction_id=?', $merchant_transaction_id);
        $order = $connection->fetchRow($sql);
        $orderId = $order['order_id'];
        if($debug){
            $this->_logger->debug('GETFINANCING - Order located is ORDER #'.$orderId);
        }
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);

        $this->_order = $order;
        $payment = $order->getPayment();
        $this->_payment = $payment;
        

        if ($order->getId()) {
          $this->_logger->debug('GETFINANCING - getID');
            switch ($temp['updates']['status']) {
                case 'approved':
                    if($debug){
                        $this->_logger->debug('GETFINANCING - approved');
                    }
                    $payment->setLastTransId($orderId);
                    $order->save();
                    $this->_processOrder();
                    break;
                case 'rejected':
                    if($debug){
                        $this->_logger->debug('PAGANTIS - charge.failed');
                    }
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
        $debug = $this->_pagantis->getDebug();

        if($debug){
            $this->_logger->debug('GETFINANCING - Processing order');
        }

        $order = $this->_order;
        $payment = $this->_payment;

        $sendMail = $this->_pagantis->getSendEmail();
        $createInvoice = $this->_pagantis->getCreateInvoice();



        if($order->getId() && !$order->canInvoice() && $createInvoice) {
            return;
        } else {
            if($debug){
                $this->_logger->debug('GETFINANCING - creating invoice');
            }
            $order->setState(order::STATE_COMPLETE);
            $order->setStatus(order::STATE_COMPLETE);
            $invoice = $order->prepareInvoice();
            $invoice->register();
            $order->addRelatedObject($invoice);
            $order->save();
        }
         if (!$order->getEmailSent() && $sendMail) {
             if($debug){
                 $this->_logger->debug('GETFINANCING - sending email to customer');
             }
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
