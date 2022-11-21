<?php
/**
 * Getfinancing_Getfinancing Success Controller get/manage the info to send to the Success page/view
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
 */

namespace Getfinancing\Getfinancing\Controller\Getfinancing;


class Success extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $_gfModel;
    protected $_objectManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_gfModel = $this->_objectManager->get('\Getfinancing\Getfinancing\Model\Getfinancing');
        //$client = new Raven_Client('https://ab22360f77e34a81a9f444f8b15a38ab@sentry.getfinancing.us/5');
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();  
        // Show the order data on Success page
        $cartData = $this->_gfModel->getSuccessData();
        $resultPage->getLayout()->getBlock("success_block_view")
                    ->setData('cartData', $cartData);
        return $resultPage;
    }
}
