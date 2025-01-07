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

namespace App\Site\Commands\App;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;

/**
 * Modify Env Command
 */
class ModEnv extends BaseCommand
{
    /**
     * @var array dotenv file sections
     */
    protected $dotenv_sections = [
        'Basic Info' => ['APPNAME', 'APPDOMAIN', 'SALT'],
        'Database Info' => ['DATABASE_HOST', 'DATABASE_NAME', 'DATABASE_USER', 'DATABASE_PASS'],
        'Admin Info' => ['ADMINPAGES_GROUP', 'ADMIN_USER', 'ADMIN_PASS', 'ADMIN_EMAIL', 'USE2FA_ADMIN'],
        'Cache Info' => ['CACHE_LIFETIME', 'DISABLE_CACHE', 'ENABLE_FPC', 'PRELOAD_REWRITES'],
        'Other Info' => ['DEBUG','GTMID', 'ENABLE_LOGGEDPAGES', 'USE2FA_USERS', 'LOGGEDPAGES_GROUP','GOOGLE_API_KEY','MAPBOX_API_KEY', 'ADMIN_DARK_MODE'],
        'ElasticSearch Info' => ['ELASTICSEARCH','ELASTICSEARCH_HOST','ELASTICSEARCH_PORT'],
        'Smtp Info' => ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS'],
        'SES Info' => ['SES_REGION','SES_PROFILE'],
        'Redis Info' => ['REDIS_CACHE','REDIS_HOST','REDIS_PORT','REDIS_PASSWORD','REDIS_DATABASE'],
        'GraphQL Info' => ['GRAPHQL'],
        'WebHooks Info' => ['WEBHOOKS'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Edit .env file')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('key', null, InputOption::VALUE_OPTIONAL,'', null),
                        new InputOption('value', null, InputOption::VALUE_OPTIONAL,'', null),
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
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $dotenv = parse_ini_file('.env.sample');
        if (file_exists('.env')) {
            $dotenv = parse_ini_file('.env');
        }

        $values = [];

        foreach ($this->dotenv_sections as $label => $keys) {
            foreach ($keys as $key) {
                if (file_exists('.install_done') && in_array($key, ['ADMIN_EMAIL', 'ADMIN_PASS', 'ADMIN_USER'])) {
                    continue;
                }

                if (substr($key, 0, 1) == '#' || substr($key, 0, 1) == ';') {
                    continue;
                }

                $old_value = $dotenv[$key] ?? '';

                if ($input->getOption('key') != null) {
                    if ($key == $input->getOption('key')) {
                        $value = $input->getOption('value') ?? '';
                    } else {
                        $value = $old_value;
                    }
                } else {
                    $question = new Question($key . ' value? defaults to [' . $old_value . ']');
                    $value = (string) $this->getQuestionHelper()->ask($input, $output, $question);
                    if (trim($value) == '' && in_array($key, ['ADMIN_EMAIL', 'ADMIN_PASS', 'ADMIN_USER'])) {
                        $value = $this->keepAsking($key . " can't be empty. ".$key.' value?');
                    }
                    if (trim($value) == '') {
                        $value = $old_value;
                    }    
                }

                $values[$key] = $value;
            }
        }

        $dotenv = '';
        foreach ($this->dotenv_sections as $label => $keys) {
            $dotenv .= "\n# -- {$label} --\n";
            foreach ($keys as $key) {
                if (file_exists('.install_done') && in_array($key, ['ADMIN_EMAIL', 'ADMIN_PASS', 'ADMIN_USER'])) {
                    continue;
                }

                $value = $values[$key];
                if (preg_match("/\s/i", $value)) {
                    $value = '"' . $value . '"';
                }
                $dotenv .= "{$key}={$value}\n";
            }
        }

        if ($input->getOption('key') == null) {
            $this->renderTitle("new .env file");
            $output->writeln($dotenv);
    
            if (!$this->confirmSave('Save Config? ')) {
                return Command::SUCCESS;
            }    
        }

        file_put_contents('.env', trim($dotenv) . "\n", LOCK_EX);
        $this->getIo()->success('Config saved');

        return Command::SUCCESS;
    }
}
