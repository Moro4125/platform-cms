<?php
/**
 * Trait MonologServiceTrait
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Monolog\Logger;

/**
 * Trait MonologServiceTrait
 * @package Model\Accessory
 */
trait MonologServiceTrait
{
	/**
	 * @var Logger
	 */
	protected $_logger;

	/**
	 * @return array
	 */
	protected function ___initTraitMonolog()
	{
		return [
			AbstractService::STATE_COMMIT_SUCCESS  => '_monologCommitSuccess',
			AbstractService::STATE_COMMIT_FAILED   => '_monologCommitFailed',
			AbstractService::STATE_DELETE_FINISHED => '_monologDeleteFinished',
		];
	}

	/**
	 * @param Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger)
	{
		$this->_logger = $logger;
		return $this;
	}

	/**
	 * @param EntityInterface $entity
	 * @param string $table
	 */
	protected function _monologCommitSuccess($entity, $table)
	{
		$message = sprintf('Commit entity with id %2$s to %1$s - SUCCESS.', $table, $entity->getId());
		$this->_logger && $this->_logger->info($message);
	}

	/**
	 * @param EntityInterface $entity
	 * @param string $table
	 * @param \Exception $exception
	 */
	protected function _monologCommitFailed($entity, $table, $exception)
	{
		$message = $exception->getMessage().' in '.$exception->getFile().' ('.$exception->getLine().').';
		$message = sprintf('Commit entity with id %2$s to %1$s - FAILED: %3$s', $table, $entity->getId(), $message);
		$this->_logger && $this->_logger->error($message);
	}

	/**
	 * @param EntityInterface $entity
	 * @param string $table
	 */
	protected function _monologDeleteFinished($entity, $table)
	{
		$message = sprintf('Delete'.' entity with id %2$s from %1$s.', $table, $entity->getId());
		$this->_logger && $this->_logger->info($message);
	}
}