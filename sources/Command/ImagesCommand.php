<?php
/**
 * Class ImagesCommand
 */
namespace Moro\Platform\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \DirectoryIterator;

/**
 * Class Images
 * @package Moro\Platform\Command
 */
class ImagesCommand extends AbstractCommand
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setName(defined('CLI_NAMESPACE_PLATFORM') ? 'images' : 'platform:images')
			->setDescription('Work with generated images.')
			->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Show status of images.', false)
			->addOption('clean', null, InputOption::VALUE_OPTIONAL, 'Clean unused images.', false)
			->ignoreValidationErrors();
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return null|int null or 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET)
		{
			$output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
		}

		$flag = false;

		if ($this->hasArgumentFlag('status'))
		{
			$flag = true;
			$this->doStatus($output);
		}

		if ($this->hasArgumentFlag('clean'))
		{
			$flag = true;
			$this->doClean($output);
		}

		if (!$flag)
		{
			/** @noinspection HtmlUnknownTag */
			$output->writeln('<error>Please, enter on of the actions: status or clean.</error>');
		}

		return null;
	}

	/**
	 * @return string
	 */
	protected function _getUploadPath()
	{
		return $this->_application->getOption('path.data').DIRECTORY_SEPARATOR.'upload';
	}

	/**
	 * @return string
	 */
	protected function _getImagesPath()
	{
		return $this->_application->getOption('path.root').DIRECTORY_SEPARATOR.'images';
	}

	/**
	 * @return string
	 */
	protected function _getPagesPath()
	{
		return $this->_application->getOption('path.root');
	}

	/**
	 * @return \Doctrine\DBAL\Driver\Statement
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function _getStatementFindByHash()
	{
		return $this->_application->getServiceDataBase()->prepare(
			$this->_application->getServiceDataBase()->createQueryBuilder()
				->select('id')->from('content_file')->where('hash = ?')
		);
	}

	/**
	 * @param string $path
	 * @return \Generator
	 */
	protected function _findFiles($path)
	{
		/** @var \SplFileInfo $info */
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $info)
		{
			if ($info->isFile() && $name = $info->getFilename())
			{
				yield $name => $info;
			}
		}
	}

	/**
	 * @return \Generator
	 */
	protected function _findUploaded()
	{
		/** @var \SplFileInfo $info */
		foreach ($this->_findFiles($this->_getUploadPath()) as $name => $info)
		{
			yield $name => $info;
		}
	}

	/**
	 * @return \Generator
	 */
	protected function _findImages()
	{
		/** @var \SplFileInfo $info */
		foreach ($this->_findFiles($this->_getImagesPath()) as $name => $info)
		{
			yield $name => $info;
		}
	}

	/**
	 * @return \Generator
	 */
	protected function _findPages()
	{
		/** @var \SplFileInfo $info */
		foreach (new DirectoryIterator($this->_getPagesPath()) as $info)
		{
			$name = $info->getFilename();

			if (strncmp($name, '.', 1) !== 0)
			{
				if ($info->isFile() && substr($name, -5) == '.html')
				{
					yield $name => $info;
				}
				elseif ($info->isDir() && $name != 'images')
				{
					foreach ($this->_findFiles($info->getRealPath()) as $name2 => $info2)
					{
						if (substr($name2, -5) == '.html')
						{
							yield $name2 => $info2;
						}
					}
				}
			}
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function doStatus(OutputInterface $output)
	{
		$verbose = ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL);

		$hashList = [];

		$usedList = [];
		$total = 0;
		$tSize = 0;

		$unusedList = [];
		$cCount = 0;
		$cSize  = 0;

		$bCount = 0;

		$output->writeln('Check status of generated images');

		/** @var \SplFileInfo $info */
		foreach ($this->_findUploaded() as $name => $info)
		{
			$hashList[$name] = true;
		}

		$pagesRootLength = strlen($this->_getPagesPath());

		/** @var \SplFileInfo $info */
		foreach ($this->_findPages() as $info)
		{
			preg_match_all('{/images/[0-9a-v]{2}/([^.]+\\.[a-z]+)}', file_get_contents($info->getRealPath()), $match, PREG_PATTERN_ORDER);

			foreach ($match[1] as $name)
			{
				$usedList[$name] = substr($info->getRealPath(), $pagesRootLength);
			}
		}

		/** @var \SplFileInfo $info */
		foreach ($this->_findImages() as $name => $info)
		{
			$total++;
			$tSize += $info->getSize();

			if (isset($usedList[$name]))
			{
				$usedList[$name] = false;
			}
			elseif ((strpos($name, '_96_96.') || strpos($name, '_154_96.')) && isset($hashList[substr($name, 0, 32)]))
			{
				$usedList[$name] = false;
			}
			else
			{
				$cCount || $verbose && $output->writeln('Unused images:');
				$verbose && $output->writeln('  '.$name);

				$unusedList[$name] = true;
				$cCount++;
				$cSize += $info->getSize();
			}
		}

		asort($usedList);

		foreach (array_filter($usedList) as $name => $page)
		{
			$bCount || $verbose && $output->writeln('Broken links:');
			$verbose && $output->writeln('  '.$name."\t".strtr($page, '\\', '/'));

			$bCount++;
		}

		$bCount && $output->writeln('Broken links: '.$bCount);
		$output->writeln('Unused images: '.$cCount.' ('.ceil($cSize / 1024 / 1024).' MB)');
		$output->writeln('Images count:  '.$total.' ('.ceil($tSize / 1024 / 1024).' MB)');
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function doClean(OutputInterface $output)
	{
		$verbose = ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL);

		$hashList = [];
		$usedList = [];

		$cCount = 0;
		$cSize  = 0;
		$errors = 0;

		$output->writeln('Read list of generated images for clean');

		/** @var \SplFileInfo $info */
		foreach ($this->_findUploaded() as $name => $info)
		{
			$hashList[$name] = true;
		}

		/** @var \SplFileInfo $info */
		foreach ($this->_findPages() as $info)
		{
			preg_match_all('{/images/[0-9a-v]{2}/([^.]+\\.[a-z]+)}', file_get_contents($info->getRealPath()), $match, PREG_PATTERN_ORDER);

			foreach ($match[1] as $name)
			{
				$usedList[$name] = true;
			}
		}

		/** @var \SplFileInfo $info */
		foreach ($this->_findImages() as $name => $info)
		{
			if (isset($usedList[$name]))
			{
				continue;
			}
			elseif ((strpos($name, '_96_96.') || strpos($name, '_154_96.')) && isset($hashList[substr($name, 0, 32)]))
			{
				continue;
			}

			$size = $info->getSize();

			if (@unlink($info->getRealPath()))
			{
				$cCount || $verbose && $output->writeln('Deleted images:');
				$verbose && $output->writeln('  '.$name);
				$cCount++;
				$cSize += $size;
			}
			else
			{
				$errors++;
				/** @noinspection HtmlUnknownTag */
				$verbose && $output->writeln('  <error>'.$name.'</error>');
			}
		}

		$output->writeln('Deleted images: '.$cCount.' ('.ceil($cSize / 1024 / 1024).' MB)');
		/** @noinspection HtmlUnknownTag */
		$errors && $output->writeln('<error>Detect '.$errors.' error(s).</error>');
	}
}