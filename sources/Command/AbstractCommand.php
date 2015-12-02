<?php
/**
 * Class AbstractCommand
 */
namespace Moro\Platform\Command;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Formatter\OutputFormatter;
use \Symfony\Component\Console\Formatter\OutputFormatterStyle;
use \Moro\Platform\Application;

/**
 * Class AbstractCommand
 * @package Moro\Platform\Command
 */
abstract class AbstractCommand extends Command
{
	/**
	 * @var Application
	 */
	protected $_application;

	/**
	 * @var InputInterface
	 */
	protected $_input;

	/**
	 * @var OutputInterface
	 */
	protected $_output;

	/**
	 * @var int
	 */
	protected $_lastState;

	/**
	 * @var bool
	 */
	protected $_hasErrors;

	/**
	 * @param Application $application
	 */
	public function __construct(Application $application)
	{
		$this->_application = $application;
		parent::__construct();
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->_input = $input;
		$this->_output = $output;
		$this->_lastState = 0;

		$formatter = new OutputFormatter(true);
		$formatter->setStyle('error', new OutputFormatterStyle('red'));
		$output->setFormatter($formatter);

		$output->writeln('Platform CMS '.Application::PLATFORM_VERSION);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	protected function hasArgumentFlag($name)
	{
		global $argv;

		return is_array($argv) && in_array('--'.$name, $argv, true);
	}
}