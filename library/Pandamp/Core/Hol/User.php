<?php
class Pandamp_Core_Hol_User
{
	/**
	 * checkPromoValidation : Individual & Korporasi
	 * @return disc :: Total
	 */
	function checkPromoValidation($whatPromo,$package,$promotionId='',$payment=0)
	{
		$tblPackage = new App_Model_Db_Table_Package();
		$rowPackage = $tblPackage->fetchRow("packageId=$package");
		$periode = $rowPackage->charge * $payment;
		
		$tblPromosi = new App_Model_Db_Table_Promotion();
		$rowPromo = $tblPromosi->find($promotionId)->current();
		
		// check promotionID if exist then dischard query
		if (isset($rowPromo)) {
			
			if ($payment == 6) {
				$disc = $rowPromo->discount + 5;
			} elseif ($payment == 12) {
				$disc = $rowPromo->discount + 10;
			} else {
				$disc = $rowPromo->discount;
			}
			
			$total = ($periode - ($disc/100 * $periode)) * 1.1;
			
		} else {
			
			$getPromo = $tblPromosi->fetchRow("periodeStart <= '".date("Y-m-d")."' AND periodEnd >= '".date("Y-m-d")."' AND monthlySubscriber=".$payment."");
			
			if (!empty($getPromo))
			{
				if ($payment == 6) {
					$disc = $getPromo->discount + 5;
				} elseif ($payment == 12) {
					$disc = $getPromo->discount + 10;
				} else {
					$disc = $getPromo->discount;
				}
				
				$total = ($periode - ($disc/100 * $periode)) * 1.1;
				
			} else { 
				
				if ($payment == 6) {
					$disc = 5;
				} elseif ($payment == 12) {
					$disc = 10;
				} else {
					$disc = 0;
				}
				
				$total = ($periode - ($disc/100 * $periode)) * 1.1;
				
			}
		}
		
		switch ($whatPromo)
		{
			case 'Disc':
				return $disc;
			break;
			case 'Total':
				return $total;
			break;
		}
	}

}
?>