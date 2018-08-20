<?php
/*
in folder:
/var/www/html/github/getfinancing-magento2/docker_magento_2/app 
do: 
clear; ./vendor/bin/phpunit --color  vendor/getfinancing/getfinancing/Test/Unit/ --coverage-text -c vendor/getfinancing/getfinancing/Test/Unit/phpunit.xml.dist
*/
use PHPUnit\Framework\TestCase;

require './vendor/autoload.php';

class Aaa extends TestCase
{
    protected $_objectManager;
    public function setUp() {
        parent::setUp();
        //$this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->_objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
    }

    public function testMethod1() {
        $this->assertFalse(false); 
    }

    public function testMethod2() {
        $model = $this->_objectManager->getObject('\Getfinancing\Getfinancing\Model\Getfinancing');
        $test1 = $model->getOrderPlaceRedirectUrl();
        $test2 = $model->getCartDataByInv_id();
        echo "\n -----------"; 
        print_r($test1);
        echo "\n -----------"; 
        print_r($test2);
        echo "\n -----------"; 
        /*$page = $this->_objectManager->getObject("\Getfinancing\Getfinancing\Controller\Getfinancing\Notification");
        $res = $page->execute();
        echo "RES IS\n"; print_r($page); */
        $this->assertFalse(false); 
    }

    public function testMethod3() {
        $this->assertFalse(false); 
    }

}
