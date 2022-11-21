<?php
/**
 * Getfinancing_Getfinancing payment form
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
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

    protected $_gfModel;
    protected $quoteManagement;

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
        \Getfinancing\Getfinancing\Model\Getfinancing $getfinancing,
        \Magento\Payment\Helper\Data $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;
        $this->quoteManagement = $quoteManagement;
        $this->_gfModel = $getfinancing;
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

    public function getProductNameWithOptions ($product) {
        $productOptions = '';
        if ($product->getHasOptions()) {
            $customOptions = $product->getTypeInstance(true)->getOrderOptions($product);
            foreach ($customOptions['attributes_info'] as $co) {
                $option = $co['label'].': '.$co['value'];
                $productOptions.=($productOptions!='')?', ':'';
                $productOptions.=$option;
            }
        }
        return $productOptions;
    }

    public function prepareCartItems ($allCartItems) { // With allCartItems prepare in required format cartItems
        $cartItems = []; $prodsDiscount = 0; // Calc the total discount (by products)
        foreach($allCartItems as $item) {
            $productName = $item->getName();
            $options = $this->getProductNameWithOptions($item->getProduct());
            $productName.= ($options!='')?' ('.$options.')':'';
            $productUnitTax = ($item->getTaxAmount()>0)?((real)$item->getTaxAmount()/$item->getQty()):0;
            // Get all products to send to Get Financing
            if ($item->getPrice() > 0) { // Products with 0 amount are extra data (like colors, size, etc)
                if ($item->getDiscountAmount()>0) { 
                    $prodsDiscount += $item->getDiscountAmount(); // get total discount to apply in the future (not in use yet)
                }
                $product = ['sku'=>$item->getSku(),'display_name'=>$productName,
                            'quantity'=>(real)$item->getQty(),
                            'unit_price'=>((real)$item->getPrice()+$productUnitTax), 
                            'unit_tax'=>(real)$productUnitTax];
                $cartItems[] = $product;
            } // Get all products to save /* $items[] = ['description'=>$item->getName(), 'quantity'=>$item->getQty(), 'amount'=>$item->getRowTotalInclTax()]; */
        }
        return $cartItems;
    }

    /**
     * Since Magento 2 don't send us the guestUser Email, get customer email from GET 
     *
     * @return emails Array (customer and shipping email values)
     */
    public function manageGuestEmail ($quote) {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $om->get('Magento\Customer\Model\Session'); 
        $defaultEmail = false; $shippingAddress = $quote->getShippingAddress();
        if(!$customerSession->isLoggedIn()) { // In the case of a guest checkout
            $defaultEmail = $this->getRequest()->getParam('email'); // this was from url
            $quote->setCustomerId(null)->setCustomerEmail($defaultEmail)
            ->setCustomerIsGuest(true);
        }
        $customerEmail = trim($quote->getCustomerEmail());
        $customerEmail =($customerEmail)?$customerEmail:$defaultEmail;
        $shippingEmail = trim($shippingAddress->getEmail());
        $shippingEmail=($shippingEmail)?$shippingEmail:$defaultEmail;
        $quote->setData('customer_email', $customerEmail); 
        $quote->save(); // Save/Update the email for the case of guest checkout
        return array('customer' => $customerEmail, 'shipping' => $shippingEmail);
    }

    /**
     * Prepares FORM Data to send to gateway
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
        $session = $objectManager->get('\Magento\Framework\Session\SessionManagerInterface');
        $quote = $cart->getQuote(); // Get the products in the current cart
        // retrieve quote items collection
        // $itemsCollection = $quote->getItemsCollection(); // Not in use (we use allCartItems)
        $allCartItems = $quote->getAllItems();        
        $cartItems = $this->prepareCartItems($allCartItems);
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $quote->getPayment()->setMethod('getfinancing_gateway'); /// Paymente Method
        /* Manage Emails for cases when user is logged ir and when user is a guest user */
        $emails = $this->manageGuestEmail ($quote);
        $customerEmail = $emails["customer"];
        $shippingEmail  = $emails["shipping"];
        #$order = $this->quoteManagement->submit($quote); // clean the cart items and submit the order (we don't want do this until get Getfinancing notification)
        $transactionId = $quote->getEntityId(); // Use quote Id for save transaction data and for notification callback
        $urlOk = $this->_storeManager->getStore()->getBaseUrl() . $this->_gfModel->getUrl('ok');
        $urlKo = $this->_storeManager->getStore()->getBaseUrl() . $this->_gfModel->getUrl('ko');
        $urlCallback = $this->_storeManager->getStore()->getBaseUrl() . $this->_gfModel->getUrl('notification');
        $postUrl = $this->_gfModel->getUrl('getfinancing');
        $address = $billingAddress->getStreet()[0];
        $saddress = $shippingAddress->getStreet()[0]; 
        $currency = $this->_gfModel->getCurrency();
        $total = $shippingAddress->getSubtotal()+$shippingAddress->getShippingAmount();
        $form['order_id'] = $transactionId;
        $form['amount'] = $shippingAddress->getSubtotal()+$shippingAddress->getShippingAmount();
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
        $merchant_loan_id = md5(time() . $this->_gfModel->getMerchantId().$shippingAddress->getFirstName() . $total);
 
        $gf_data = array(
            'first_name'       => $shippingAddress->getFirstName(),
            'last_name'        => $shippingAddress->getLastName(),
            'amount'           => $total,
            'software_name'    => 'Magento 2',
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
            'phone'        => $billingAddress->getTelephone(),
            'shipping_amount'  => (real)$shippingAddress->getShippingAmount(),
            'version' => '1.9',
            'merchant_transaction_id' => $merchant_loan_id,
            'success_url' => $urlOk,
            'failure_url' => $urlKo,
            'postback_url' => $urlCallback
        );

        $username = $this->_gfModel->getUsername();
        $password = $this->_gfModel->getPassword();
        $body_json_data = json_encode($gf_data, JSON_UNESCAPED_SLASHES);
        $header_auth = base64_encode($username . ":" . $password);
        // Get Production or Staging URL from GetFinancing Model
        $url_to_post = $this->_gfModel->getUrl("getfinancing") . $this->_gfModel->getMerchantId()  . '/requests';
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
        // Create the loan on GetFinancing (using GetFinancing API)
        $gf_response = $this->_remote_post($url_to_post, $post_args);
        $gf_response = json_decode($gf_response);
        if(isset($gf_response->type) && $gf_response->type == "error"){
            $form['href'] = $urlKo;
        } else {
            $form['href'] = $gf_response->href;
            $form['inv_id'] = $gf_response->inv_id;
        } 
        //insert hash to order_id        
        $order_data = json_encode(["gf_data"=>$gf_data, "gf_response"=>$gf_response]);
        $this->_gfModel->saveOrderData($transactionId, $merchant_loan_id, $order_data);
        $this->addData(['form' => $form]); // Send data to the view
        $session->setGfResponse(json_encode($gf_response));
    }

    /**
     * Set up RemotePost / Curl. We use it to create the loan on GetFinancing (using GetFinancing API)
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
        return (!$resp)?false:$resp;
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
