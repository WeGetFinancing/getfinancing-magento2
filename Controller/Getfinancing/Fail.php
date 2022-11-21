<?php
/**
 * Getfinancing_Getfinancing payment form
 * @package    Getfinancing_Getfinancing
 * @copyright  Copyright (c) 2018 Getfinancing (http://www.getfinancing.com)
 * @author	   Getfinancing <services@getfinancing.com>
 */

namespace Getfinancing\Getfinancing\Controller\Getfinancing;

class Fail extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        //$client = new Raven_Client('https://ab22360f77e34a81a9f444f8b15a38ab@sentry.getfinancing.us/5');
        parent::__construct($context);
    }

    public function execute()
    {
        /**
         * Create the Fail message, use session to retrieve the reason (saved at Block\Redirect.php file)
        */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $session = $objectManager->get('\Magento\Framework\Session\SessionManagerInterface');
        $gf_response = $session->getGfResponse();
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getLayout()->getBlock("fail_block_view")->setData('gf_response', json_decode($gf_response, 1));
        $session->unsGfResponse();
        return $resultPage;
    }
}
