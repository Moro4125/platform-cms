<?php
/**
 * Class UploadedCommand
 */
namespace Moro\Platform\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;
use \PDO;
use \Exception;

/**
 * Class Images
 * @package Moro\Platform\Command
 */
class UploadedCommand extends AbstractCommand
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setName(defined('CLI_NAMESPACE_PLATFORM') ? 'uploaded' : 'platform:uploaded')
			->setDescription('Work with uploaded files.')
			->addOption('status', null, InputOption::VALUE_OPTIONAL, 'Show status of uploaded files.', false)
			->addOption('restore', null, InputOption::VALUE_OPTIONAL, 'Restore erased uploaded files.', false)
			->addOption('clean', null, InputOption::VALUE_OPTIONAL, 'Delete erased uploaded files.', false)
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

		if ($this->hasArgumentFlag('restore'))
		{
			$flag = true;
			$this->doRestore($output);
		}

		if ($this->hasArgumentFlag('clean'))
		{
			$flag = true;
			$this->doClean($output);
		}

		if (!$flag)
		{
			/** @noinspection HtmlUnknownTag */
			$output->writeln('<error>Please, enter on of the actions: status, restore, clean.</error>');
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
	 * @return \Generator
	 */
	protected function _findFiles()
	{
		/** @var \SplFileInfo $info */
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_getUploadPath())) as $info)
		{
			if ($info->isFile() && $hash = $info->getFilename())
			{
				yield $hash => $info;
			}
		}
	}

	/**
	 * @return \Generator
	 */
	protected function _findDeletedFiles()
	{
		$statement = $this->_getStatementFindByHash();

		/** @var \SplFileInfo $info */
		foreach ($this->_findFiles() as $hash => $info)
		{
			if ($flagDeleted = ($statement->execute([$hash]) && !$statement->fetchAll()))
			{
				yield $hash => $info;
			}
		}
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function doStatus(OutputInterface $output)
	{
		$verbose = ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL);

		$output->writeln('Check status of uploaded files');
		$hashList = [];
		$total = 0;
		$tSize = 0;
		$count = 0;
		$dSize = 0;

		/** @var \SplFileInfo $info */
		foreach ($this->_findFiles() as $hash => $info)
		{
			$hashList[$hash] = true;
			$total++;
			$tSize += $info->getSize();
		}

		/** @var \SplFileInfo $info */
		foreach ($this->_findDeletedFiles() as $hash => $info)
		{
			$count || $verbose && $output->writeln('Deleted:');
			$hashList[$hash] = false;
			$verbose && $output->writeln('  '.$hash);
			$count++;
			$dSize += $info->getSize();
		}

		$count && $output->writeln(($verbose ? '' : 'Find erased: ').$count.' entity(ies) ('.ceil($dSize / 1014 / 1024).' MB)');

		$statement = $this->_application->getServiceDataBase()->prepare(
			$this->_application->getServiceDataBase()->createQueryBuilder()
				->select('hash')->from('content_file')->groupBy('hash')
		);
		$count = 0;

		foreach ($statement->execute() ? $statement->fetchAll(PDO::FETCH_COLUMN) : [] as $hash)
		{
			if (!isset($hashList[$hash]))
			{
				$count || $verbose && $output->writeln('Broken:');
				$verbose && $output->writeln('  '.$hash);
				$count++;
			}
		}

		$count && $output->writeln(($verbose ? '' : 'Find broken: ').$count.' entity(ies).');

		$output->writeln('Files count: '.$total.' ('.ceil($tSize / 1024 / 1024).' MB)');
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function doRestore(OutputInterface $output)
	{
		$verbose = ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL);
		$count = 0;
		$errors = null;
		$maxSize = 1024 * 1024 * 8;

		$service = $this->_application->getServiceFile();

		/** @var \SplFileInfo $info */
		foreach ($this->_findDeletedFiles() as $hash => $info)
		{
			$count !== null || $verbose && $output->writeln('Restore:');

			if ($info->getSize() > $maxSize)
			{
				$errors++;
				/** @noinspection HtmlUnknownTag */
				$verbose && $output->writeln('  <error>'.$hash.' - file is too big</error>');
				continue;
			}

			$count++;

			$parameters = [
				'size'   => $info->getSize(),
				'tags' => ['Флаг: восстановленные'],
			];

			try
			{
				$image   = $this->_application->getServiceImagine()->open($info->getRealPath());
				$width   = $image->getSize()->getWidth();
				$height  = $image->getSize()->getHeight();
				$minSize = min($width, $height);

				$parameters = array_merge($parameters, [
					'width'  => $width,
					'height' => $height,
					'crop_x' => floor(($width - $minSize) / 2),
					'crop_y' => 0,
					'crop_w' => $minSize,
					'crop_h' => $minSize,
				]);
			}
			catch (Exception $exception)
			{
				$image = null;
			}

			if ($image)
			{
				$entity = $service->createEntity($hash, '1x1', true);
				$entity->setName($hash);
				$entity->setParameters($parameters);

				$service->commit($entity);
				$verbose && $output->writeln('  '.$hash);
			}
			else
			{
				$count--;
				$verbose && $output->writeln('  skip '.$hash);
			}
		}

		$count && $output->writeln(($verbose ? '' : 'Restore ').$count.' entity(ies).');
		/** @noinspection HtmlUnknownTag */
		$errors && $output->writeln('<error>Restore '.$errors.' error(s).</error>');
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function doClean(OutputInterface $output)
	{
		$verbose = ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL);
		$count = 0;
		$errors = null;

		/** @var \SplFileInfo $info */
		foreach ($this->_findDeletedFiles() as $hash => $info)
		{
			$count !== null || $verbose && $output->writeln('Delete:');

			if (@unlink($info->getRealPath()))
			{
				$count++;
				$verbose && $output->writeln('  '.$hash);
			}
			else
			{
				$errors++;
				/** @noinspection HtmlUnknownTag */
				$verbose && $output->writeln('  <error>'.$hash.'</error>');
			}
		}

		$count && $output->writeln(($verbose ? '' : 'Delete ').$count.' entity(ies).');
		/** @noinspection HtmlUnknownTag */
		$errors && $output->writeln('<error>Detect '.$errors.' error(s).</error>');
	}
}