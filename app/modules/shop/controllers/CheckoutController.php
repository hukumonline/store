<?php
class Shop_CheckoutController extends Zend_Controller_Action 
{
	protected $_configStore;
	protected $_user;
	
	function preDispatch()
	{
		$this->_helper->layout->setLayout('layout-store-checkout');
		Zend_Session::start();
		$storeConfig = Pandamp_Application::getOption('store');
		$this->_configStore = $storeConfig;
		$sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$sReturn = base64_encode($sReturn);
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity())
		{
			$this->_user = $auth->getIdentity();
		}
	}
	function cartAction()
	{
        if(!is_object($_SESSION['jCart']))
        {
            $this->_redirect(ROOT_URL.'/checkout/cartempty');
        }
        if(count($_SESSION['jCart']->items)==0)
        {
            $this->_redirect(ROOT_URL.'/checkout/cartempty');
        }

        $cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();
        $this->view->cart = $cart;

        $tblCatalog = new App_Model_Db_Table_Catalog();
        $rowset = $tblCatalog->fetchRow("guid='lt4d197e9f8919f'",'createdDate DESC');

        $content = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($rowset->guid,'fixedContent');
        $title = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($rowset->guid,'fixedTitle');
        $subTitle = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($rowset->guid,'fixedSubTitle');

        $this->view->content = $content;
        $this->view->title = $title;
        $this->view->subTitle = $subTitle;
        
        $tblCatalog = new App_Model_Db_Table_Catalog();
        $rowset = $tblCatalog->fetchRow("guid='lt4d196f17c9836'",'createdDate DESC');

        $content = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($rowset->guid,'fixedContent');
        $title = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($rowset->guid,'fixedTitle');
        $subTitle = App_Model_Show_CatalogAttribute::show()->getCatalogAttributeValue($rowset->guid,'fixedSubTitle');

        $this->view->cp = $content;
        $this->view->tp = $title;
        $this->view->sp = $subTitle;
        
        if($this->_isStoreClosed())
                $this->_redirect(ROOT_URL.'/checkout/closed');
		
	}
    function closedAction()
    {

    }
    function cartemptyAction()
    {

    }
    function updatepostAction()
    {
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $id = $this->_getParam('id');
        $qty = $this->_getParam('qty');

        $cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();

        if ($qty < 1)
        {
            $this->del_item($id);
        }
        else
        {
            $cart->itemqtys[$id] = $qty;
        }

        $this->_update_total();

        $this->_redirect(ROOT_URL . '/checkout/cart');
    }
    function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender(TRUE);

        $this->del_item($this->_getParam('id'));

        $this->_redirect(ROOT_URL . '/checkout/cart');
    }
    private function del_item($itemid)
    {
        $cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();

        $ti = array();
        $cart->itemqtys[$itemid] = 0;
        foreach($cart->items as $item)
        {
            if($item != $itemid)
            {
                $ti[] = $item;
            }
        }
        $cart->items = $ti;

        $this->_update_total();
    }
    private function _update_total()
    {
        $cart =& $_SESSION['jCart']; if(!is_object($cart)) $cart = new jCart();

        $cart->itemcount = 0;
        $cart->total = 0;
        if(sizeof($cart->items > 0))
        {
            foreach($cart->items as $item)
            {
                $cart->total = $cart->total + ($cart->itemprices[$item] * $cart->itemqtys[$item]);
                // TOTAL ITEMS IN CART (ORIGINAL wfCart COUNTED TOTAL NUMBER OF LINE ITEMS)
                $cart->itemcount += $cart->itemqtys[$item];
            }
        }
    }
	private function _isStoreClosed()
	{
        $auth =  Zend_Auth::getInstance();
        if(!$auth->hasIdentity())
        {
        }
        else
        {
            $acl = Pandamp_Acl::manager();
            if($acl->checkAcl("site",'all','user', $this->_user->username, false,false))
            {
                return 0;
            }
        }

        return $this->_configStore['isClosed'];
	}
}