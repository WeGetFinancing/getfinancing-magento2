<?php
/**
 * Getfinancing_Getfinancing payment form

 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2016 Yameveo (http://www.yameveo.com)
 * @author	   Yameveo <yameveo@yameveo.com>
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
        return $this->resultPage;
        //$this->_redirect('checkout/onepage/success');
    }
}
