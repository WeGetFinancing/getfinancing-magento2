<?php
/**
 * Getfinancing_Getfinancing payment form
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
 */

namespace Getfinancing\Getfinancing\Controller\Getfinancing;


class Success extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->resultPage = $this->resultPageFactory->create();  
        // Show the order data on Success page
        // send $merchantTransactionId to ok url??? as a parameter?
        /*$connection= $this->_resources->getConnection();
        $tablename = $this->_resources->getTableName('getfinancing');
        $sql = $connection->select()->from($tablename)
                        ->where('merchant_transaction_id = ?', $merchantTransactionId);
        $result = $connection->fetchAll($sql);
        $quoteId = (int)$result[0]['order_id'];*/
        //$this->resultPage->getLayout()->getBlock("success_view")->setData('orderData', json_decode($session->getOrderForm(), 1));
        return $this->resultPage;
    }
}
