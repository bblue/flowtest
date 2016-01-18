<?php
namespace bblue\ruby\Package\MailerPackage;

use bblue\ruby\Component\Logger\tLoggerAware;
use Psr\Log\LoggerAwareInterface;

final class Mailer extends \PHPMailer implements LoggerAwareInterface
{
    use tLoggerAware;
    
    public function send() {
        try {
            parent::send();
        } catch (\phpmailerException $e) {
            $this->logger->danger($e->getMessage());
            throw $e;
        }
    }
}