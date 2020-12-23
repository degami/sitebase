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

namespace App\Base\Abstracts\Commands;

use \Symfony\Component\Console\Command\Command as SymfonyCommand;
use \Psr\Container\ContainerInterface;
use \Dotenv\Dotenv;
use \App\Base\Traits\ContainerAwareTrait;
use \App\App;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base for cli commands
 */
class BaseCommand extends SymfonyCommand
{
    use ContainerAwareTrait;

    /** @var InputInterface */
    protected $input = null;

    /** @var OutputInterface */
    protected $output = null;

    /** @var SymfonyStyle */
    protected $io = null;

    /**
     * BaseCommand constructor.
     *
     * @param null $name
     * @param ContainerInterface|null $container
     */
    public function __construct($name = null, ContainerInterface $container = null)
    {
        parent::__construct($name);
        $this->container = $container;
        $this->bootstrap();
    }

    /**
     * boostrap command
     *
     * @return void
     */
    protected function bootstrap()
    {
        // load environment variables
        $dotenv = Dotenv::create(App::getDir(App::ROOT));
        $dotenv->load();

        $this->getContainer()->set(
            'env',
            array_combine(
                $dotenv->getEnvironmentVariableNames(),
                array_map(
                    'getenv',
                    $dotenv->getEnvironmentVariableNames()
                )
            )
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        parent::initialize($input, $output);
    }

    /**
     * @return SymfonyStyle
     */
    protected function getIo(): ?SymfonyStyle
    {
        if ($this->input == null || $this->output == null) {
            return null;
        }

        if ($this->io == null) {
            $this->io = new SymfonyStyle($this->input, $this->output);
        }

        return $this->io;
    }

    /**
     * @return QuestionHelper
     */
    protected function getQuestionHelper(): QuestionHelper
    {
        return $this->getHelper('question');
    }

    /**
     * @param string $option_name
     * @param string $question_message
     * @return bool|mixed|string|string[]|null
     */
    protected function keepAskingForOption(string $option_name, string $question_message)
    {
        if ($this->input == null || $this->output == null) {
            return null;
        }

        $value = $this->input->getOption($option_name);
        while (trim($value) == '') {
            $question = new Question($question_message);
            $value = $this->getQuestionHelper()->ask($this->input, $this->output, $question);
        }

        return $value;
    }

    /**
     * @param string $confirmation_message
     * @param $not_confirm_message
     * @return bool
     */
    protected function confirmMessage(string $confirmation_message, $not_confirm_message): bool
    {
        $question = new ConfirmationQuestion($confirmation_message, false);
        if (!$this->getQuestionHelper()->ask($this->input, $this->output, $question)) {
            $this->output->writeln('<info>'.$not_confirm_message.'</info>');
            return false;
        }

        return true;
    }

    /**
     * @param string $confirmation_message
     * @return bool
     */
    protected function confirmSave(string $confirmation_message): bool
    {
        return $this->confirmMessage($confirmation_message, 'Not Saving');
    }

    /**
     * @param string $confirmation_message
     * @return bool
     */
    protected function confirmDelete(string $confirmation_message): bool
    {
        return $this->confirmMessage($confirmation_message, 'Not deleted');
    }
}
