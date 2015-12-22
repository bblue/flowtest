<?php
namespace bblue\ruby\Package\MailerPackage;

use Psr\Log\LoggerAwareInterface;
use bblue\ruby\Component\Logger\LoggerAwareTrait;

final class Mailer extends \PHPMailer implements LoggerAwareInterface
{
    use LoggerAwareTrait; 
    
    public function send() {
        try {
            parent::send();
        } catch (\phpmailerException $e) {
            $this->logger->danger($e->getMessage());
            throw $e;
        }
    }
}