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

namespace App\Site\Commands\Mail;

use \App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Block;
use App\Site\Models\Contact;
use App\Site\Models\ContactSubmission;
use App\Site\Models\LinkExchange;
use App\Site\Models\MailLog;
use App\Site\Models\MediaElement;
use App\Site\Models\RequestLog;
use App\Site\Models\User;
use App\Site\Models\Website;
use App\Site\Models\Page;
use App\Site\Models\News;
use App\Site\Models\Taxonomy;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableCell;
use \Symfony\Component\Console\Helper\TableSeparator;

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
     * {@inheritdocs}
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $to = $this->keepAskingForOption('mail_to', 'Send mail to? ');

        $type = $this->keepAskingForOption('mail_type', 'Mail mail format (html, text)? ', ['html', 'text']);

        $out = null;

        if ($type == 'html') {
            $subject = 'Test Email from '.$this->getSiteData()->getCurrentWebsite()->getDomain();
            $body = $this->getUtils()->getWrappedMailBody($subject, 'This is a test email to check functionality');

            $out = $this->getMailer()->sendMail(
                $this->getSiteData()->getSiteEmail() ?? 'testmail@'.$this->getSiteData()->getCurrentWebsite()->getDomain(),
                $to,
                $subject,
                $body
            );
        } else {
            $out = $this->getMailer()->sendMail(
                $this->getSiteData()->getSiteEmail(),
                $to,
                'Test Email from '.$this->getSiteData()->getCurrentWebsite()->getDomain(),
                'This is a test email to check functionality',
                'plain/text'
            );
        }

        $output->writeln("Mail sent. result:".var_export($out, true));

    }
}
