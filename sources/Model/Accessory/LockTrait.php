<?php
/**
 * Class LockServiceTrait
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Model\AbstractService;
use \Doctrine\DBAL\Connection;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class LockServiceTrait
 * @package Moro\Platform\Model\Accessory
 */
trait LockTrait
{
	/**
	 * @var string
	 */
	protected $_lockTable = 'locks';

	/**
	 * @var array
	 */
	protected $_lockRecords = [];

	/**
	 * @var int
	 */
	protected $_lockTime = 60;

	/**
	 * @return array
	 */
	protected function ___initTraitLock()
	{
		return [
			AbstractService::STATE_COMMIT_STARTED => '_lockCommitStarted',
		];
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 */
	protected function _lockCommitStarted($entity)
	{
		if (($lockedBy = $this->isLocked($entity)) && $this instanceof AbstractService)
		{
			$this->stopNotify();
			$message = sprintf('Изменение записи с ID %1$s временно запрещено пользователем "%2$s"!', $entity->getId(), $lockedBy);
			throw new AccessDeniedHttpException($message);
		}
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @return string
	 */
	protected function _lockGetCode($entity)
	{
		$code = $entity->getId();

		if ($this instanceof AbstractService)
		{
			$code = $this->getServiceCode().'-'.$code;
		}

		return $code;
	}

	/**
	 * @return string
	 */
	protected function _lockGetUser()
	{
		if (isset($this->_userToken))
		{
			/** @var TokenInterface $userToken */
			$userToken = $this->_userToken;
			return $userToken->getUsername();
		}

		return session_id();
	}

	/**
	 * @param int $time
	 * @return $this
	 */
	public function setLockTime($time)
	{
		$this->_lockTime = (int)$time;
		return $this;
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @return bool|string
	 */
	public function isLocked($entity, $lockTime = null)
	{
		if (isset($this->_connection))
		{
			/** @var Connection $connection */
			$connection = $this->_connection;

			$query = $connection->createQueryBuilder();
			$query->select('code, user, updated_at');
			$query->from($this->_lockTable);
			$query->where('code = ?');

			$statement = $connection->prepare($query->getSQL());

			if ($statement->execute([$this->_lockGetCode($entity)]) && $record = $statement->fetch(\PDO::FETCH_ASSOC))
			{
				$this->_lockRecords[$entity->getId()] = $record;
				$format = $connection->getDatabasePlatform()->getDateTimeFormatString();
				$redLine = gmdate($format, time() - ($lockTime ?: $this->_lockTime));

				return ($redLine < $record['updated_at'] && $record['user'] !== $this->_lockGetUser())
					? $record['user']
					: false;
			}
			else
			{
				unset($this->_lockRecords[$entity->getId()]);
			}
		}

		return false;
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @return bool|string
	 */
	public function tryLock($entity, $lockTime = null)
	{
		if (!$this->isLocked($entity, $lockTime) && isset($this->_connection))
		{
			/** @var Connection $connection */
			$connection = $this->_connection;
			$platform = $connection->getDatabasePlatform();
			$id = $entity->getId();
			$now = $platform->getNowExpression();
			$time = gmdate($platform->getDateTimeFormatString());

			if (empty($this->_lockRecords[$id]))
			{
				$query = $connection->createQueryBuilder()
					->insert($this->_lockTable)
					->values([
						'user' => '?',
						'code' => '?',
						'created_at' => $now,
						'updated_at' => $platform->quoteStringLiteral($time),
					])
					->getSQL();
			}
			else
			{
				$query = $connection->createQueryBuilder()
					->update($this->_lockTable)
					->set('user', '?')
					->set('updated_at', $platform->quoteStringLiteral($time))
					->where('code = ?')
					->getSQL();
			}

			$connection->prepare($query)->execute([$this->_lockGetUser(), $this->_lockGetCode($entity)]);
			return (!$this->isLocked($entity, $lockTime) && !empty($this->_lockRecords[$id])) ? $time : false;
		}

		return false;
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @param null|string $stamp
	 * @return bool
	 */
	public function tryUnlock($entity, $lockTime = null, $stamp = null)
	{
		if (!$this->isLocked($entity, $lockTime) && isset($this->_connection))
		{
			/** @var Connection $connection */
			$connection = $this->_connection;
			$id = $entity->getId();

			if (!empty($this->_lockRecords[$id]))
			{
				$parameters = [$this->_lockGetCode($entity)];
				$builder = $connection->createQueryBuilder()
					->delete($this->_lockTable)
					->where('code = ?');

				if ($stamp)
				{
					$parameters[] = $stamp;
					$builder->andWhere('updated_at = ?');
				}

				$connection->prepare($builder->getSQL())->execute($parameters);
				return !$this->isLocked($entity, $lockTime) && empty($this->_lockRecords[$id]);
			}

			return true;
		}

		return false;
	}
}