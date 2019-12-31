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
namespace App\Site\Commands\Cache;

use \App\Base\Abstracts\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use \Symfony\Component\Console\Helper\Table;
use \Psr\Container\ContainerInterface;

/**
 * Cache Get Element Command
 */
class Get extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Get Cache item')
            ->setDefinition(
                new InputDefinition(
                    [
                    new InputOption('key', 'k', InputOption::VALUE_OPTIONAL),
                    new InputOption('format', 'f', InputOption::VALUE_OPTIONAL),
                    ]
                )
            );
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

        $key = $input->getOption('key');
        while (trim($key) == '') {
            $question = new Question('Cache item key? ');
            $key = $helper->ask($input, $output, $question);
        }

        $format = $input->getOption('format');
        $callback = null;
        switch ($format) {
            case 'json':
                $callback = 'json_encode';
                break;
            case 'serialize':
                $callback = 'serialize';
                break;
            default:
                $callback = 'serialize';
                break;
        }

        $output->writeln($callback($this->getCache()->get($key)));
    }
}
