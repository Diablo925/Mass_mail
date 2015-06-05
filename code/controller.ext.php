<?php

class module_controller extends ctrl_module
{

		static $ok;
		
    /**
     * The 'worker' methods.
     */
    
	static function ExectuteSend($packages, $subject, $message)
	{
        global $zdbh;
		if($packages == "ALL") {
		$sql = $zdbh->prepare("SELECT * FROM x_accounts WHERE ac_deleted_ts IS NULL"); } else { 
		$sql = $zdbh->prepare("SELECT * FROM x_accounts WHERE ac_package_fk = :packages AND ac_deleted_ts IS NULL");
		$sql->bindParam(':packages', $packages);
		}
        $sql->execute();
		while ($rowgroups = $sql->fetch()) { 
			$email = $rowgroups['ac_email_vc'];
			$emailsubject = $subject;
            $emailbody = "$message";

            $phpmailer = new sys_email();
			$phpmailer->CharSet = "UTF-8";
            $phpmailer->Subject = $emailsubject;
            $phpmailer->Body = $emailbody;
            $phpmailer->AddAddress($email);
            $phpmailer->SendEmail();
			self::$ok = true;
		} 
        // return true;
    }


	
   	static function ListPackages($uid)
    {
        global $zdbh;
        $sql = "SELECT * FROM x_packages WHERE pk_deleted_ts IS NULL";
        //$numrows = $zdbh->query($sql);
        $numrows = $zdbh->prepare($sql);
        $numrows->bindParam(':uid', $uid);
        $numrows->execute();
        if ($numrows->fetchColumn() <> 0) {
            $sql = $zdbh->prepare($sql);
            $sql->bindParam(':uid', $uid);
            $res = array();
            $sql->execute();
            while ($rowgroups = $sql->fetch()) {
                array_push($res, array('packageid' => $rowgroups['pk_id_pk'],
                    'packagename' => ui_language::translate($rowgroups['pk_name_vc'])));
            }
            return $res;
        } else {
            return false;
        }
    }
	
    /**
     * End 'worker' methods.
     */

    /**
     * Webinterface sudo methods.
     */

    static function getPackageList()
    {
        global $controller;
        $currentuser = ctrl_users::GetUserDetail();
        return self::ListPackages($currentuser['userid']);
    }
	
	static function doSend()
    {
        global $controller;
        runtime_csfr::Protect();
        $formvars = $controller->GetAllControllerRequests('FORM');
        return self::ExectuteSend($formvars['inPackage'], $formvars['inSubject'], $formvars['inMessage']);
    }
	
	static function getResult()
    {
		 if (self::$ok) {
            return ui_sysmessage::shout(ui_language::translate("Your mail has been sent"), "zannounceok");
        }
        return;
    }

    /**
     * Webinterface sudo methods.
     */
}