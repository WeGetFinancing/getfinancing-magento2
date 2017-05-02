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
        \Getfinancing\Getfinancing\Model\Getfinancing $pagantis,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->_pagantis = $pagantis;
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
        $checkout = $this->_checkoutSession;

        $order = $this->_checkoutSession->getLastRealOrder();
        $transactionId = (string)$order->getRealOrderId();
        $debug = $this->_pagantis->getDebug();

        $urlOk = $this->_storeManager->getStore()->getBaseUrl() . $this->_pagantis->getUrl('ok');
        $urlKo = $this->_storeManager->getStore()->getBaseUrl() . $this->_pagantis->getUrl('ko');
        $urlCallback = $this->_storeManager->getStore()->getBaseUrl() . $this->_pagantis->getUrl('notification');

        $postUrl = $this->_pagantis->getUrl('pagantis');

        $address = $order->getBillingAddress();
        $saddress = $order->getShippingAddress();

        $currency = $this->_pagantis->getCurrency();

        $total = floor($order->getGrandTotal());



        $form['order_id'] = $transactionId;
        $form['amount'] = $total;
        $form['currency'] = $currency;

        $form['ok_url'] = $urlOk;
        $form['nok_url'] = $urlKo;


        if(!$order->getCustomerFirstname()) {
            $billingAddress = $order->getBillingAddress();
            $fullName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            $first_name = $billingAddress->getFirstname();
            $last_name = $billingAddress->getLastname();
        } else {
            $fullName = $order->getCustomerFirstname()  . ' ' . $order->getCustomerLastname();
            $first_name = $order->getCustomerFirstname();
            $last_name = $order->getCustomerLastname();
        }

        $form['full_name'] = $fullName;

        $form['email'] = $order->getCustomerEmail();
        $form['dni'] = $order->getCustomerTaxvat();

        $form['address']['street'] = !empty($address) ? array_values($address->getStreet())[0] : '';
        $form['address']['city'] = !empty($address) ? $address->getCity() : '';
        $form['address']['province'] = !empty($address) ? $address->getRegion() : '';
        $form['address']['zipcode'] = !empty($address) ? $address->getPostcode() : '';

        $i = 0;
        $items = array();

        foreach ($order->getAllItems() as $key => $value){
            $items[$i]['description'] = $value->getName() ? $value->getName() : '';
            $items[$i]['quantity'] = round($value->getQtyOrdered(),0);
            $items[$i]['amount'] = round($value->getRowTotalInclTax(),2);
            $i++;
        }

        $shippingAmount = round($order->getShippingInclTax(),2);
        if($shippingAmount) {
            $items[$i]['description'] = "Gastos de envío";
            $items[$i]['quantity'] = "1";
            $items[$i]['amount'] = $shippingAmount;
            $i++;
        }

        $discountAmount = round($order->getBaseDiscountAmount(),2);
        if($discountAmount) {
            $items[$i]['description'] = "Descuento";
            $items[$i]['quantity'] = "1";
            $items[$i]['amount'] = $discountAmount;
        }

        $form['callback_url'] = $urlCallback;
        $form['phone'] = $address->getTelephone();
        $form['dob'] = $order->getCustomerDob();

        //$form['secret'] = $secretKey;
        $form['post_url'] = $postUrl;
        $merchant_loan_id = md5(time() . $this->_pagantis->getMerchantId() .$first_name . $total);
        $gf_data = array(
            'amount'           => $total,
            'product_info'     => $transactionId,
            'first_name'       => $first_name,
            'last_name'        => $last_name,
            'shipping_address' => array(
                'street1'  => array_values($address->getStreet())[0],
                'city'    => $saddress->getCity(),
                'state'   => $saddress->getRegion(),
                'zipcode' => $saddress->getPostcode()
            ),
            'billing_address' => array(
                'street1'  => array_values($address->getStreet())[0],
                'city'    => $address->getCity(),
                'state'   => $address->getRegion(),
                'zipcode' => $address->getPostcode()
            ),
            'email'            => $order->getCustomerEmail(),
            'merchant_loan_id' => $merchant_loan_id,
            'version' => '1.9',
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
            $this->_logger->debug("GF connection failure: request: ".var_export($post_args,1));
            $this->_logger->debug("GF connection response: request: ".var_export($gf_response,1));
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

        //insert hash to order_id

        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()
        ->get('Magento\Framework\App\ResourceConnection');
        $connection= $this->_resources->getConnection();

        $tablename = $this->_resources->getTableName('getfinancing');
        $sql = sprintf(
            "Insert into %s (order_id,merchant_transaction_id) Values ('%s','%s' )",
            $tablename,
            $transactionId,
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
