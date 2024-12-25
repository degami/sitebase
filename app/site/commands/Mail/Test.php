<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Commands\Mail;

use App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Information Statistics Command
 */
class Test extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Test Email')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('mail_to', '', InputOption::VALUE_OPTIONAL),
                        new InputOption('mail_type', '', InputOption::VALUE_OPTIONAL),
                    ]
                )
            );
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $to = $this->keepAskingForOption('mail_to', 'Send mail to? ');

        $type = $this->keepAskingForOption('mail_type', 'Mail mail format (html, text)? ', ['html', 'text']);

        $out = null;

        if ($type == 'html') {
            $subject = 'Test Email from ' . $this->getSiteData()->getCurrentWebsite()->getDomain();
            $body = $this->getUtils()->getWrappedMailBody($subject, 'This is a test email to check functionality');

            $out = $this->getMailer()->sendMail(
                $this->getSiteData()->getSiteEmail() ?? 'testmail@' . $this->getSiteData()->getCurrentWebsite()->getDomain(),
                $to,
                $subject,
                $body
            );
        } else {
            $out = $this->getMailer()->sendMail(
                $this->getSiteData()->getSiteEmail(),
                $to,
                'Test Email from ' . $this->getSiteData()->getCurrentWebsite()->getDomain(),
                'This is a test email to check functionality',
                'plain/text'
            );
        }

        if ($out) {
            $this->getIo()->success('Mail sent.');
        } else {
            $this->getIo()->error('Mail not sent.');
        }

        return Command::SUCCESS;
    }
}
