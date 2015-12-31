<?php

namespace bblue\ruby\Package\MailerPackage;

use bblue\ruby\Component\Package\AbstractPackage;

final class MailerPackage extends AbstractPackage
{  
    public function boot()
    {
        // Create a hook to retrieve the mailer
        $this->container
            ->register('mailer', 'bblue\ruby\Package\MailerPackage\Mailer', realpath(VENDOR_PATH) . '/PHPMailer/phpmailer.php')
            ->addConstructorArgument(true)
            ->addMethodCall('isSMTP')
            ->addMethodCall('addReplyTo', [$this->config->MAIL_SERVER_USERNAME, 'Aleksander Lanes'])
            ->addClassParameter('SMTPDebug', 2)
            ->addClassParameter('CharSet', 'UTF-8')
            ->addClassParameter('From', $this->config->MAIL_FROM_ADDRESS)
            ->addClassParameter('FromName', $this->config->MAIL_FROM_NAME)
            ->addClassParameter('Host', $this->config->MAIL_SERVER_ADDR)
            ->addClassParameter('Port', $this->config->MAIL_SERVER_PORT);
        return true;
    }
    
    public function sendTestMail()
    {
        $mailer = $this->container->get('mailer');
        
        $mailer->addAddress('aleksander.lanes@gmail.com', 'Aleksander Lanes');
        $mailer->Subject = 'Test fra dev.intensjon.no';
        $mailer->Body = 'Hei! Dette er en test';
        
        $mailer->send();
    }
}