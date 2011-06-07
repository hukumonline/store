<?php

class App_Model_Store_Mailer
{
    public function sendUserBankConfirmationToAdmin($orderId)
    {
        $config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'mail');

        $sOrderId = '';

        if(is_array($orderId))
        {
            for($i=0; $i< count($orderId);$i++)
            {
                $sOrderId .= $orderId[$i].', ';
            }
        }
        else
        {
            $sOrderId = $orderId;
        }
        
        $registry = Zend_Registry::getInstance();
        $remote = $registry->get('config');

        $message =
"
You just have received Bank Transfer Confirmation for Order ID $sOrderId please check thru admin page.".

$remote->admin->website."/id/store/confirm.

==============================";

        $this->send($config->mail->sender->support->email, $config->mail->sender->support->name,
                                        $config->mail->sender->billing->email, $config->mail->sender->billing->name,
                                        "[HUKUMONLINE] Bank Transfer Payment Confirmation ", $message);
    }

    public function send($mailFrom, $fromName, $mailTo, $mailToName, $subject, $body)
    {
        $config = new Zend_Config_Ini(CONFIG_PATH.'/mail.ini', 'mail');
        
        $options = array('auth' => $config->mail->auth,
                        'username' => $config->mail->username,
                        'password' => $config->mail->password);

        if(!empty($config->mail->ssl))
        {
            $options = array('auth' => $config->mail->auth,
                            'ssl' => $config->mail->ssl,
                            'username' => $config->mail->username,
                            'password' => $config->mail->password);
        }

        $transport = new Zend_Mail_Transport_Smtp($config->mail->host, $options);

        $mail = new Zend_Mail();
        $mail->setBodyText($body);
        $mail->setFrom($mailFrom, $fromName);
        $mail->addTo($mailTo, $mailToName);
        $mail->setSubject($subject);

        try
        {
            $mail->send($transport);
        }
        catch (Zend_Exception $e)
        {
            echo $e->getMessage();
        }
    }
}