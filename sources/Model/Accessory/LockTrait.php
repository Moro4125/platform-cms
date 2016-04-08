<?php
/**
 * Class LockServiceTrait
 */
namespace Moro\Platform\Model\Accessory;
use \Moro\Platform\Model\AbstractService;
use \Doctrine\DBAL\Connection;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use \Exception;

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
	 * @param null|bool $withoutNotify
	 * @return bool|string
	 */
	public function isLocked($entity, $lockTime = null, $withoutNotify = null)
	{
		/** @var AbstractService $self */
		$self = $this;
		$user = $this->_lockGetUser();

		if ($withoutNotify)
		{
			$flag = false;
		}
		else
		{
			$flag = $self->notify(LockInterface::STATE_CHECK_LOCK, $entity, $lockTime, $user) ?: false;
		}

		if ($flag === false && isset($this->_connection))
		{
			/** @var Connection $connection */
			$connection = $this->_connection;

			$query = $connection->createQueryBuilder();
			$query->select('code, user, updated_at, token');
			$query->from($this->_lockTable);
			$query->where('code = ?');

			$statement = $connection->prepare($query->getSQL());

			if ($statement->execute([$this->_lockGetCode($entity)]) && $record = $statement->fetch(\PDO::FETCH_ASSOC))
			{
				$this->_lockRecords[$entity->getId()] = $record;
				$format = $connection->getDatabasePlatform()->getDateTimeFormatString();
				$redLine = gmdate($format, time() - ($lockTime ?: $this->_lockTime));

				$flag = ($redLine < $record['updated_at'] && $record['user'] !== $user)
					? $record['user']
					: false;
			}
			else
			{
				unset($this->_lockRecords[$entity->getId()]);
				$flag = false;
			}
		}

		return $flag;
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @param null|string $token
	 * @return bool|string
	 *
	 * @throws Exception
	 */
	public function tryLock($entity, $lockTime = null, $token = null)
	{
		if (isset($this->_connection))
		{
			/** @var Connection $connection */
			$connection = $this->_connection;
			$connection->beginTransaction();
			$transaction = true;
		}

		$token === null && $token = dechex(mt_rand(0x10000000, 0x7FFFFFFF));

		try
		{
			/** @var AbstractService $self */
			$self = $this;
			$user = $this->_lockGetUser();
			$flag = $self->notify(LockInterface::STATE_TRY_LOCK, $entity, $lockTime, $token, $user);

			if ($flag !== false)
			{
				$result = true;

				if (isset($this->_connection) && $result = !$this->isLocked($entity, $lockTime, true))
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
								'token' => '?',
								'updated_at' => '?',
								'user' => '?',
								'code' => '?',
								'created_at' => $now,
							])
							->getSQL();
					}
					else
					{
						$query = $connection->createQueryBuilder()
							->update($this->_lockTable)
							->set('token', '?')
							->set('updated_at', '?')
							->set('user', '?')
							->where('code = ?')
							->getSQL();
					}

					$connection->prepare($query)->execute([$token, $time, $user, $this->_lockGetCode($entity)]);
					$result = (!$this->isLocked($entity, $lockTime, true) && !empty($this->_lockRecords[$id]))
						? $this->_lockRecords[$id]['token']
						: false;
				}

				if ($flag && !$result)
				{
					$self->notify(LockInterface::STATE_TRY_UNLOCK, $entity, $lockTime, $token, $user);
				}

				return $result;
			}

			return false;
		}
		catch (Exception $exception)
		{
			if (isset($this->_connection))
			{
				/** @var Connection $connection */
				$connection = $this->_connection;
				$connection->rollBack();
				unset($transaction);
			}

			throw $exception;
		}
		finally
		{
			if (isset($this->_connection) && isset($transaction))
			{
				/** @var Connection $connection */
				$connection = $this->_connection;
				$connection->isRollbackOnly() ? $connection->rollBack() : $connection->commit();
			}
		}
	}

	/**
	 * @param \Moro\Platform\Model\EntityInterface $entity
	 * @param null|int $lockTime
	 * @param null|string $token
	 * @return bool
	 *
	 * @throws Exception
	 */
	public function tryUnlock($entity, $lockTime = null, $token = null)
	{
		if (isset($this->_connection))
		{
			/** @var Connection $connection */
			$connection = $this->_connection;
			$connection->beginTransaction();
			$transaction = true;
		}

		try
		{
			/** @var AbstractService $self */
			$self = $this;
			$user = $this->_lockGetUser();
			$flag = $self->notify(LockInterface::STATE_TRY_UNLOCK, $entity, $lockTime, $token, $user);

			if (isset($this->_connection) && $flag = !$this->isLocked($entity, $lockTime, true))
			{
				/** @var Connection $connection */
				$connection = $this->_connection;
				$id = $entity->getId();
				$flag = true;

				if (!empty($this->_lockRecords[$id]))
				{
					$parameters = [$this->_lockGetCode($entity)];
					$builder = $connection->createQueryBuilder()
						->delete($this->_lockTable)
						->where('code = ?');

					if ($token)
					{
						$parameters[] = $token;
						$builder->andWhere('token = ?');
					}

					$connection->prepare($builder->getSQL())->execute($parameters);
					$flag = !$this->isLocked($entity, $lockTime, true) && empty($this->_lockRecords[$id]);
				}
			}

			return (bool)$flag;
		}
		catch (Exception $exception)
		{
			if (isset($this->_connection))
			{
				/** @var Connection $connection */
				$connection = $this->_connection;
				$connection->rollBack();
				unset($transaction);
			}

			throw $exception;
		}
		finally
		{
			if (isset($this->_connection) && isset($transaction))
			{
				/** @var Connection $connection */
				$connection = $this->_connection;
				$connection->isRollbackOnly() ? $connection->rollBack() : $connection->commit();
			}
		}
	}
}