<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Base\Tools\Utils;

use \App\Base\Abstracts\ContainerAwareObject;
use \Swift_Message;
use \Exception;
use \Aws\Exception\AwsException;
use \App\Site\Models\MailLog;
use \Swift_TransportException;

/**
 * Mailer Helper Class
 */
class Mailer extends ContainerAwareObject
{
    /**
     * send a mail
     * @param  string  $from
     * @param  string  $to
     * @param  string  $subject
     * @param  string  $body
     * @param  string  $content_type
     * @param  boolean $log
     * @return boolean
     */
    public function sendMail($from, $to, $subject, $body, $content_type = 'text/html', $log = true)
    {
        $result = false;

        $use_ses = (trim($this->getEnv('SES_REGION')) != '' && trim($this->getSiteData()->getConfigValue('app/mail/ses_sender')) != '');
        if (!$use_ses || ($result = $this->sendSesMail($from, $to, $subject, $body, $content_type)) != true) {
            // use smtp or fallback to smtp
            $result = $this->sendSmtpMail($from, $to, $subject, $body, $content_type);
        }

        if ($log == true) {
            $this->logMail($from, $to, $subject, $result);
        }

        return $result;
    }

    /**
     * send email using template
     * @param  string $from
     * @param  string $to
     * @param  string $subject
     * @param  string $mail_template
     * @param  string  $mail_variables
     * @return boolean
     */
    public function sendTemplateMail($from, $to, $subject, $mail_template, $mail_variables = [])
    {
        $this->getTemplates()->addFolder('mails', App::getDir(App::TEMPLATES).DS.'mails');

        $template = $this->getTemplates()->make('mails::'.$mail_template);
        $mail_variables['subject'] = $subject;
        $template->data($mail_variables);
        $body = $template->render();
        $result = $this->sendMail($from, $to, $subject, $body, 'text/html', false);
        
        $this->logMail($from, $to, $subject, $result, $mail_template);

        return $result;
    }

    /**
     * sends a mail using SMTP
     * @param  string $from
     * @param  string $to
     * @param  string $subject
     * @param  string $body
     * @param  string $content_type
     * @return boolean
     */
    protected function sendSmtpMail($from, $to, $subject, $body, $content_type = 'text/html')
    {
        try {
            if (!is_array($from)) {
                $from = [$from => $from];
            }
            if (!is_array($to)) {
                $to = [$to => $to];
            }

            // Create a message
            $message = $this->getContainer()->make(Swift_Message::class)
              ->setFrom($from)
              ->setTo($to)
              ->setContentType($content_type)
              ->setSubject($subject)
              ->setBody($body);

            // Send the message
            $result = $this->getSmtpMailer()->send($message);

            return $result;
        } catch (Swift_TransportException $e) {
            $this->getUtils()->logException($e, "Error sending SMTP mail");
        }

        return false;
    }

    /**
     * send a mail using SES
     * @param  string $from
     * @param  string $to
     * @param  string $subject
     * @param  string $body
     * @param  string $content_type
     * @return boolean
     */
    protected function sendSesMail($from, $to, $subject, $body, $content_type = 'text/html')
    {
        // Specify a configuration set. If you do not want to use a configuration
        // set, comment the following variable, and the
        // 'ConfigurationSetName' => $configuration_set argument below.
       
        // $configuration_set = 'ConfigSet';

        $char_set = 'UTF-8';

        if (is_array($from)) {
            $from = reset($from);
        }
        if (!is_array($to)) {
            $to = [$to];
        }

        try {
            $result = $this->getSesMailer()->sendEmail([
                'Destination' => [
                    'ToAddresses' => $to,
                ],
                'ReplyToAddresses' => [$from],
                'Source' => $this->getSiteData()->getConfigValue('app/mail/ses_sender'),
                'Message' => [
                  'Body' => [
                      'Html' => [
                          'Charset' => $char_set,
                          'Data' => $body,
                      ],
                      'Text' => [
                          'Charset' => $char_set,
                          'Data' => $body,
                      ],
                  ],
                  'Subject' => [
                      'Charset' => $char_set,
                      'Data' => $subject,
                  ],
                ],
                // If you aren't using a configuration set, comment or delete the
                // following line

                // 'ConfigurationSetName' => $configuration_set,
            ]);
            $messageId = $result['MessageId'];
            return true;
        } catch (AwsException $e) {
            // output error message if fails
            $this->getUtils()->logException($e, "Error sending SES mail");
        }
        return false;
    }

    /**
     * logs mail sent
     * @param  string $from
     * @param  string $to
     * @param  string $subject
     * @param  integer $result
     * @param  string $mail_template
     * @return MailLog|boolean
     */
    protected function logMail($from, $to, $subject, $result, $mail_template = null)
    {
        if (is_array($from)) {
            $from = implode(",", array_values($from));
        }

        if (is_array($to)) {
            $to = implode(",", array_values($to));
        }

        try {
            $maillog = $this->getContainer()->make(MailLog::class);
            $maillog->from = $from;
            $maillog->to = $to;
            $maillog->subject = $subject;
            $maillog->template_name = $mail_template;
            $maillog->result = $result;
            $maillog->persist();

            return $maillog;
        } catch (Exception $e) {
            $this->getUtils()->logException($e, "Can't write MailLog");
        }

        return false;
    }
}