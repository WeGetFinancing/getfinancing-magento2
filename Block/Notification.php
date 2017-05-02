<?php
/**
 * Getfinancing_Getfinancing payment form

 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2016 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
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
     */
    protected $_checkoutSession;

    /**
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
    protected $_customerRepository;

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

        /*$orderId = '000000006';
        $data['id'] = 'XXXXXXXXXXXXXX';
        $temp = json_decode('{"event":"charge.created","api_version":"1.0","account_id":"tk_1234567890","signature":"25cf7709d9d4e54d985e97603b93f213f747614e","data": { "livemode": true, "paid": true, "amount": 1000, "currency": "EUR", "refunded": true, "captured": true, "authorization_code": "12345678", "error_code": null, "error_message": null, "order_id": "000000006", "description": "Descripción del cargo", "custom_fields": null, "amount_in_eur": 1000, "exchange_rate_eur": 1, "source": "web", "ip": "0.0.0.0", "locale": "es", "fee": null, "interchange_fee": null, "settlement": null, "created_at": "2014-06-24T15:30:00.000Z", "id": "cha_1234567890abcdefg" }}',true);
        */

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
