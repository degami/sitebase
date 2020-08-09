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
namespace App\Site\Commands\App;

use \App\Base\Abstracts\Commands\BaseCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Modify Env Command
 */
class ModEnv extends BaseCommand
{
    /**
     * @var array dotenv file sections
     */
    protected $dotenv_sections = [
        'Basic Info' => ['APPNAME','APPDOMAIN','SALT'],
        'Database Info' => ['DATABASE_HOST','DATABASE_NAME','DATABASE_USER','DATABASE_PASS'],
        'Admin Info' => ['ADMINPAGES_GROUP','ADMIN_USER','ADMIN_PASS','ADMIN_EMAIL'],
        'Cache Info' => ['CACHE_LIFETIME','DISABLE_CACHE','ENABLE_FPC'],
        'Other Info' => ['ENABLE_LOGGEDPAGES','LOGGEDPAGES_GROUP','DEBUG','GTMID'],
        'Smtp Info' => ['SMTP_HOST','SMTP_PORT','SMTP_USER','SMTP_PASS'],
        'SES Info' => ['SES_REGION','SES_PROFILE'],
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Edit .env file');
    }

    /**
     * {@inheritdocs}
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $io = new SymfonyStyle($input, $output);

        $dotenv = parse_ini_file('.env.sample');
        if (file_exists('.env')) {
            $dotenv = parse_ini_file('.env');
        }

        $values = [];

        foreach ($this->dotenv_sections as $label => $keys) {
            foreach ($keys as $key) {
                if (substr($key, 0, 1) == '#' || substr($key, 0, 1) == ';') {
                    continue;
                }
                $old_value = $dotenv[$key] ?? '';

                $question = new Question($key.' value? defaults to ['.$old_value.']');
                $value = $helper->ask($input, $output, $question);
                if (trim($value) == '') {
                    $value = $old_value;
                }
                $values[$key] = $value;
            }
        }

        $dotenv = '';
        foreach ($this->dotenv_sections as $label => $keys) {
            $dotenv .= "\n# -- {$label} --\n";
            foreach ($keys as $key) {
                $value = $values[$key];
                if (preg_match("/\s/i", $value)) {
                    $value = '"'.$value.'"';
                }
                $dotenv .= "{$key}={$value}\n";
            }
        }


        $io->title("new .env file");
        $output->writeln($dotenv);

        $question = new ConfirmationQuestion('Save Config? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not Saving</info>');
            return;
        }

        file_put_contents('.env', trim($dotenv)."\n", LOCK_EX);
        $output->writeln('<info>Config saved</info>');
    }
}
