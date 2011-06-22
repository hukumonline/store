<?php
class Membership_ManagerController extends Zend_Controller_Action
{
	function preDispatch()
	{
		$this->view->addHelperPath(KUTU_ROOT_DIR.'/library/Kutu/View/Helper','Kutu_View_Helper');
		$this->_helper->layout->setLayout('layout-membershipac');
		$this->_helper->layout->setLayoutPath(array('layoutPath'=>KUTU_ROOT_DIR.'/application/modules/membership/views/layouts'));
	}
    function activateAction()
    {
        $sReturn = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $sReturn = base64_encode($sReturn);

        $r = $this->getRequest();

        $id = $r->getParam("id");

        $modelUser = new Kutu_Core_Orm_Table_User();
        $rowset = $modelUser->find(base64_decode($id))->current();

        if ($rowset)
        {
            if ($rowset->periodeId == 2)
            {
                $this->_forward('restricted','manager','membership',array('type' => 'user','num' => 106));
            }
            elseif ($rowset->periodeId == 3)
            {
                $this->_forward('restricted','manager','membership',array('type' => 'user','num' => 102));
            }
            elseif ($rowset->periodeId == 4)
            {
                $this->_forward('restricted','manager','membership',array('type' => 'user','num' => 'downgrade'));
            }
            else
            {
                $rowset->activationDate = date("Y-m-d h:i:s");
                $rowset->isActive = 1;
                $rowset->periodeId = 3;
                $rowset->save();

                $this->_forward('redirect-url','manager','membership',array('username' => $rowset->username,'packageId'=>$rowset->packageId,'return'=>$sReturn));
            }
        }
        else
        {
            $this->_forward('restricted','manager','membership',array('type' => 'user','num' => 105));
        }
    }
    /*
	function activateAction()
	{
		$this->_helper->layout()->disableLayout();
		
		$guid = ($this->_getParam('uid'))? $this->_getParam('uid') : '';
		
		//$aclMan		= new Kutu_Acl_Adapter_Local();
		$obj 		= new Kutu_Crypt_Password();
		$formater 	= new Kutu_Core_Hol_User();
		
		$tblUser = new Kutu_Core_Orm_Table_User();
		$rowset = $tblUser->find(base64_decode($guid))->current();
		
		if ($rowset)
		{
			if ($rowset->periodeId == 2)
			{
				$this->_forward('restricted','manager','membership',array('type' => 'user','num' => 106));
			}
			elseif ($rowset->periodeId == 3)
			{
				$this->_forward('restricted','manager','membership',array('type' => 'user','num' => 102));
			}
			elseif ($rowset->periodeId == 4)
			{
				$this->_forward('restricted','manager','membership',array('type' => 'user','num' => 'downgrade'));
			}
			else 
			{
				// set activation date
				$rowset->activationDate = date("Y-m-d h:i:s");
				$rowset->isActive = 1;
				// check package
				if ($rowset->packageId == 26 or $rowset->packageId == 27)
				{
					// set period = trial
					$rowset->periodeId = 2;
					// add user to gacl
					// $aclMan->addUser($rowset->username,'member_gratis');
					// -- write invoice
					// Get disc promo
					$disc = $formater->checkPromoValidation('Disc',$rowset->packageId,$rowset->promotionId,$rowset->paymentId);
					// Get total promo
					$total = $formater->checkPromoValidation('Total',$rowset->packageId,$rowset->promotionId,$rowset->paymentId);
					$formater->_writeInvoice($rowset->kopel,$total,$disc,$rowset->paymentId);
				}
				else 
				{
					$rowset->periodeId = 3;
				}
				// update
				$result = $rowset->save();
				
				if ($result)
				{
					$this->_forward('redirect-url','manager','membership',array('username' => $rowset->username));
				}
				else 
				{
					$this->_forward('restricted','manager','membership',array('type' => 'user','num' => 101));
				}
				
			}
			
		}
		else 
		{
			$this->_forward('restricted','manager','membership',array('type' => 'user','num' => 105));	
		}
	}
	function redirectUrlAction()
	{
		$this->_helper->layout()->disableLayout();
		$username = ($this->_getParam('username'))? $this->_getParam('username') : '';
		$this->view->username = $username;
	}
	*/
    function redirectUrlAction()
    {
        $username = ($this->_getParam('username'))? $this->_getParam('username') : '';
        $packageId = ($this->_getParam('packageId'))? $this->_getParam('packageId') : '';
        $return = ($this->_getParam('return'))? $this->_getParam('return') : '';
        $this->view->username = $username;
        $this->view->packageId = $packageId;
        $this->view->return = $return;
    }
	function restrictedAction()
	{
		$type = ($this->_getParam('type'))? $this->_getParam('type') : '';
		$num = ($this->_getParam('num'))? $this->_getParam('num') : '';
		
		switch ($type)
		{
			case "user":
				
				switch ($num) 
				{
					case "downgrade":
						$error_msg = "Akun anda sudah berubah menjadi gratis";
						break;
					case 101:
						$error_msg = "Perbaharui data gagal";
						break;
					case 102:
						$error_msg = "Akun anda sudah aktif";
						break;
					case 105:
						$error_msg = "Nama pengguna tidak ditemukan";
						break;
					case 106:
						$error_msg = "Akun anda sudah aktif tapi status masa percobaan";
						break;
					default:
						$error_msg = "Kesalahan tidak diketahui di sistem manajemen pengguna";
				}
				
			break;
		}
		
		$this->view->error_msg = $error_msg;
		
	}
	
}