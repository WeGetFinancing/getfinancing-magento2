<?php
/**
 * Getfinancing_Getfinancing payment form
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
 */

namespace Getfinancing\Getfinancing\Model;

class Getfinancing extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_PAGANTIS_CODE = 'getfinancing_gateway';

    const URL_OK = 'getfinancing/getfinancing/success';
    const URL_KO = 'getfinancing/getfinancing/fail';
    const URL_NOTIFICATION = 'getfinancing/getfinancing/notification';
    const URL_PAGANTIS_PROD = "https://api.getfinancing.com/merchant/";
    const URL_PAGANTIS_STAGE = "https://api-test.getfinancing.com/merchant/";
    const CURRENCY = 'USD';
    const LOCALE = 'EN';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_PAGANTIS_CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    public function getOrderPlaceRedirectUrl()
    {
        return 'getfinancing/getfinancing/redirect';
    }

    public function getEnvironment()
    {
        //Data from system.xml fields
        return $this->getConfigData('environment');
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function getUrlToPost () {
        
    }

    public function getMerchantId()
    {
        return $this->getConfigData('merch_id');
    }

    public function getDeleteCancelledOrders()
    {
        return $this->getConfigData('delete_cancelled_orders');
    }

    public function getUsername()
    {
        return $this->getConfigData('username');
    }

    public function getPassword()
    {
        return $this->getConfigData('password');
    }

    public function getCreateInvoice() {
        return $this->getConfigData('create_invoice');
    }

    public function getSendEmail() {
        return $this->getConfigData('send_email');
    }

    public function getCurrency() {
        return self::CURRENCY;
    }

    public function getLocale() {
        return self::LOCALE;
    }

    public function getUrl($key) {
        switch ($key) {
            case 'notification':
                return self::URL_NOTIFICATION;
                break;
            case 'ok':
                return self::URL_OK;
                break;
            case 'ko':
                return self::URL_KO;
                break;
            case 'pagantis':
              if($this->getEnvironment()) {
                  error_log (self::URL_PAGANTIS_PROD, 3, '/tmp/log');
                return self::URL_PAGANTIS_PROD;
              }else{
                return self::URL_PAGANTIS_STAGE;
              }
                break;
        }
    }

    private function getObjectManager () {
        return \Magento\Framework\App\ObjectManager::getInstance();
    }

    private function getSessionManager () {
        $om = $this->getObjectManager ();
        return $om->get('\Magento\Framework\Session\SessionManagerInterface');
    }

    private function getDBConnection () {
        $om = $this->getObjectManager ();
        return $om->get('Magento\Framework\App\ResourceConnection')->getConnection();
    }

    public function getSuccessData () {
        /**
         * Prepares the data for success page using session values set at checkout (Block\Redirect.php)
         */
        $ret = false;
        $session = $this->getSessionManager();
        if ($GfResponse = $session->getGfResponse()) {
            $GfResponse = json_decode($GfResponse, 1);
            if (isset($GfResponse["inv_id"])) {
                $cartData = $this->getCartDataByInv_id($GfResponse["inv_id"]);
                $ret = $cartData;
            } // Else We don't have the inv_id
        } // Else We don't have the GetFinancing response in the session
        return $ret;
    }

    public function getCartDataByInv_id ($inv_id) {
        $om = $this->getObjectManager();
        $connection= $this->getDBConnection();
        $tablename = $connection->getTableName('getfinancing');
        $sql = "select * from $tablename where ";
        $sql.= "order_data like '%".$inv_id."%' ";
        $cartData = $connection->fetchAll($sql);
        return $cartData;
    }

    public function getDebug() {
        return $this->getConfigData('debug');
    }
}
