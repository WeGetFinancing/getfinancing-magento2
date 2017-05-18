<?php
/**
 * Getfinancing_Getfinancing payment form

 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2016 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
 */

namespace Getfinancing\Getfinancing\Block;

use Magento\Sales\Model\Order;

class Redirect extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    protected $_pagantis;
//    protected $_orderFactory;
    protected $quoteManagement;
//    protected $_paymentHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Getfinancing\Getfinancing\Model\Getfinancing $pagantis,
        \Magento\Payment\Helper\Data $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->quoteManagement = $quoteManagement;
//        $this->_orderFactory = $orderFactory;
        $this->_pagantis = $pagantis;
//        $this->_paymentHelper = $paymentHelper;
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
        $debug = $this->_pagantis->getDebug();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
        $orderObj = $objectManager->get('\Magento\Sales\Model\Order\Config');
        $quote = $cart->getQuote(); 
        $transactionId = $quote->getId();

        // retrieve quote items collection
        $itemsCollection = $quote->getItemsCollection();      
        // get array of all items what can be display directly
        $itemsVisible = $quote->getAllVisibleItems();

        $allCartItems = $quote->getAllItems();
        $cartItems = []; $items = [];
        
        foreach($allCartItems as $item) { 
            // Get all products for send to Get Financing
            $cartItems[] = ['sku'=>$item->getSku(),'display_name'=>$item->getName(),
                            'quantity'=>(real)$item->getQty(),
                            'unit_price'=>(real)$item->getPrice(),
                            'unit_tax'=>(real)$item->getTaxAmount()];
            // Get all products to save
            $items[] = ['description'=>$item->getName(),
                        'quantity'=>$item->getQty(),
                        'amount'=>$item->getRowTotalInclTax()];
        }


        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        /// Paymente Method
        $quote->getPayment()->setMethod('getfinancing_gateway');

        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $order = $this->_checkoutSession->getLastRealOrder();
        if(!$customerSession->isLoggedIn()) {
            $customerEmail = $order->getCustomerEmail(); 
            $shippingEmail  = $customerEmail;
        } else {
            //$order = $this->quoteManagement->submit($quote);
            $customerEmail = $quote->getCustomerEmail();
            $shippingEmail = $shippingAddress->getEmail();
        }

         $urlOk = $this->_storeManager->getStore()->getBaseUrl() . $this->_pagantis->getUrl('ok');
        $urlKo = $this->_storeManager->getStore()->getBaseUrl() . $this->_pagantis->getUrl('ko');
        $urlCallback = $this->_storeManager->getStore()->getBaseUrl() . $this->_pagantis->getUrl('notification');

        $postUrl = $this->_pagantis->getUrl('pagantis');
        
        $address = $billingAddress->getStreet()[0];
        $saddress = $shippingAddress->getStreet()[0]; 

        $currency = $this->_pagantis->getCurrency();

        $total = $shippingAddress->getSubtotal();

        $form['order_id'] = $transactionId;
        $form['amount'] = $shippingAddress->getSubtotal();
        $form['currency'] = $currency;

        $form['ok_url'] = $urlOk;
        $form['nok_url'] = $urlKo;

        $form['full_name'] = $shippingAddress->getFirstName().' '.$shippingAddress->getLastName();
        $form['email'] = $customerEmail;

        $form['dni'] = $quote->getCustomerTaxvat();

        $form['address']['street']   = $saddress;
        $form['address']['city']     = $shippingAddress->getCity();
        $form['address']['province'] = $shippingAddress->getRegion();
        $form['address']['zipcode']  = $shippingAddress->getPostcode();

        $form['callback_url'] = $urlCallback;

        $form['phone'] = $shippingAddress->getTelephone();
        $form['dob'] = $quote->getCustomerDob();

        $form['post_url'] = $postUrl;
        $merchant_loan_id = md5(time() . $this->_pagantis->getMerchantId().$shippingAddress->getFirstName() . $total);
 
        $gf_data = array(
       //     'product_info'     => $transactionId,
            'first_name'       => $shippingAddress->getFirstName(),
            'last_name'        => $shippingAddress->getLastName(),
            'amount'           => $total,
            'shipping_address' => array(
                'street1'  => $shippingAddress->getStreet()[0],
                'city'     => $shippingAddress->getCity(),
                'state'    => $shippingAddress->getRegion(),
                'zipcode'  => $shippingAddress->getPostcode()
            ),
            'billing_address' => array(
                'street1'  => $billingAddress->getStreet()[0],
                'city'     => $billingAddress->getCity(),
                'state'    => $billingAddress->getRegion(),
                'zipcode'  => $billingAddress->getPostcode()
            ),
            'email'            => $shippingEmail,
            'cart_items'       => $cartItems,
//            'merchant_loan_id' => $merchant_loan_id,
            'shipping_amount'  => (real)$shippingAddress->getShippingAmount(),
            'version' => '1.9',
            'merchant_transaction_id' => $merchant_loan_id,
            'success_url' => $urlOk,
            'failure_url' => $urlKo,
            'postback_url' => $urlCallback
        );

        $username = $this->_pagantis->getUsername();
        $password = $this->_pagantis->getPassword();

        $body_json_data = json_encode($gf_data, JSON_UNESCAPED_SLASHES);

        $header_auth = base64_encode($username . ":" . $password);


        if ($this->_pagantis->getEnvironment()) {
            $url_to_post = 'https://api.getfinancing.com/merchant/';
        } else {
            $url_to_post = 'https://api-test.getfinancing.com/merchant/';
        }

        $url_to_post .= $this->_pagantis->getMerchantId()  . '/requests';

        // clean spaces in the URL.
        $url_to_post = str_replace(' ' ,'', $url_to_post);

        $post_args = array(
            'body' => $body_json_data,
            'timeout' => 60,     // 60 seconds
            'blocking' => true,  // Forces PHP wait until get a response
            'sslverify' => false,
            'headers' => array(
              'Content-Type' => 'application/json',
              'Authorization' => 'Basic ' . $header_auth,
              'Accept' => 'application/json'
             )
        );

        $gf_response = $this->_remote_post( $url_to_post, $post_args );

        if($debug){
            $this->_logger->debug("GF URL: ".var_export($url_to_post, 1));
            $this->_logger->debug("GF connection : request: ".var_export($post_args,1));
            $this->_logger->debug("GF connection : request: ".var_export($gf_response,1));
        }
        $gf_response = json_decode($gf_response);


        if(isset($gf_response->type) && $gf_response->type == "error"){
          if($debug){
            $this->_logger->debug('GF ERROR - redirecting to fail');
          }
            //$this->_redirect('getfinancing/getfinancing/fail');
            $form['href'] = $urlKo;
        }else{
          $form['href'] = $gf_response->href;
          $form['inv_id'] = $gf_response->inv_id;
        } 
        
        $increment_id = $order->getRealOrderId(); // Create the order asociated with this cart

        //insert hash to order_id
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()
        ->get('Magento\Framework\App\ResourceConnection');
        $connection= $this->_resources->getConnection();

        $tablename = $this->_resources->getTableName('getfinancing');
        $sql = sprintf(
            "Insert into %s (order_id,merchant_transaction_id) Values ('%s','%s' )",
            $tablename,
//            $transactionId,
            $increment_id,
            $merchant_loan_id
        );
        $connection->query($sql);

        $this->addData(
            [
            'form' => $form
            ]
        );
    }


    /**
     * Set up RemotePost / Curl.
     */
    function _remote_post($url,$args=array()) {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $args['body']);
        curl_setopt($curl, CURLOPT_USERAGENT, ' GetFinancing Payment Module ' );
        if (defined('CURLOPT_POSTFIELDSIZE')) {
            curl_setopt($curl, CURLOPT_POSTFIELDSIZE, 0);
        }
        curl_setopt($curl, CURLOPT_TIMEOUT, $args['timeout']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        $array_headers = array();
        foreach ($args['headers'] as $k => $v) {
            $array_headers[] = $k . ": " . $v;
        }
        if (sizeof($array_headers)>0) {
          curl_setopt($curl, CURLOPT_HTTPHEADER, $array_headers);
        }

        if (strtoupper(substr(@php_uname('s'), 0, 3)) === 'WIN') {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $resp = curl_exec($curl);
        curl_close($curl);

        if (!$resp) {
          return false;
        } else {
          return $resp;
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

    public function getDefaultResult()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setRefererOrBaseUrl();
    }
}
