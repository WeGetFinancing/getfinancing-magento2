<?php
/**
 * Getfinancing_Getfinancing payment form

 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2016 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
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
    const CURRENCY = 'EUR';
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

    public function getMerchantId()
    {
        return $this->getConfigData('merch_id');
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
                return self::URL_PAGANTIS_PROD;
              }else{
                return self::URL_PAGANTIS_STAGE;
              }
                break;
        }
    }

    public function getDebug() {
        return $this->getConfigData('debug');
    }
}
