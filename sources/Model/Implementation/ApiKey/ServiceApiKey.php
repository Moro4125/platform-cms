<?php
/**
 * Class ServiceApiKey
 */
namespace Moro\Platform\Model\Implementation\ApiKey;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;
use \Doctrine\DBAL\DBALException;
use \PDO;
use \RuntimeException;

/**
 * Class ServiceApiKey
 * @package Moro\Platform\Model\Implementation\ApiKey
 */
class ServiceApiKey extends AbstractService
{
	/**
	 * @var string
	 */
	protected $_table = 'api_key';

	/**
	 * @param ApiKeyInterface $entity
	 * @param null|array $groups
	 * @return bool
	 */
	protected function _checkEntity(ApiKeyInterface $entity, array $groups = null)
	{
		if ((empty($groups) || !array_diff($entity->getGroups(), $groups)) && $entity->getCounter() != 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	protected function _initialization()
	{
		$this->_specials[] = ApiKeyInterface::PROP_COUNTER;
		$this->_traits[self::class][self::STATE_PREPARE_COMMIT] = '_prepareSpecialsCounter';

		parent::_initialization();
	}

	/**
	 * @param ApiKeyInterface $entity
	 * @param bool $insert
	 * @param |ArrayObject $params
	 * @return array
	 */
	protected function _prepareSpecialsCounter(ApiKeyInterface $entity, $insert, $params)
	{
		$fields = [];

		if ($insert)
		{
			$placeholder = ':'.ApiKeyInterface::PROP_COUNTER;
			$fields[ApiKeyInterface::PROP_COUNTER] = $placeholder;
			$params[$placeholder] = $entity->getCounter();
		}
		elseif (!$entity->hasFlag(ApiKeyInterface::FLAG_SYSTEM_CHANGES))
		{
			$fields[ApiKeyInterface::PROP_COUNTER] = ApiKeyInterface::PROP_COUNTER.' - 1';
		}

		return $fields;
	}

	/**
	 * @param string $key
	 * @param null|bool $withoutException
	 * @return \Moro\Platform\Model\Implementation\ApiKey\ApiKeyInterface|null
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RuntimeException
	 */
	public function getEntityByKey($key, $withoutException = null)
	{
		$sql = $this->_connection->createQueryBuilder()->select('*')->from($this->_table)->where('key = ?')->getSQL();
		$statement = $this->_connection->prepare($sql);

		if ($statement->execute([$key]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			/** @var \Moro\Platform\Model\Implementation\ApiKey\ApiKeyInterface $entity */
			if ($this->_checkEntity($entity = $this->_newEntityFromArray($record, EntityInterface::FLAG_GET_FOR_UPDATE)))
			{
				return $entity;
			}

			$this->deleteEntityById($entity->getId(), true);
		}

		if (empty($withoutException))
		{
			throw new RuntimeException(sprintf('Record with key "%1$s" is not exists.', $key));
		}

		return null;
	}

	/**
	 * @param string $user
	 * @param string $target
	 * @param null|array $groups
	 * @param null|bool $withoutException
	 * @return ApiKeyInterface|null
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RuntimeException
	 */
	public function getEntityByUserAndTarget($user, $target, array $groups = null, $withoutException = null)
	{
		$builder = $this->_connection->createQueryBuilder();
		$sql = $builder->select('*')->from($this->_table)->where('user = ?')->andWhere('target = ?')->getSQL();
		$statement = $this->_connection->prepare($sql);

		if ($statement->execute([$user, $target]) && $record = $statement->fetch(PDO::FETCH_ASSOC))
		{
			/** @var \Moro\Platform\Model\Implementation\ApiKey\ApiKeyInterface $entity */
			if ($this->_checkEntity($entity = $this->_newEntityFromArray($record, EntityInterface::FLAG_GET_FOR_UPDATE), $groups))
			{
				return $entity;
			}

			$this->deleteEntityById($entity->getId(), true);
		}

		if (empty($withoutException))
		{
			throw new RuntimeException(sprintf('Record for user "%1$s" and target "%2$s" is not exists.', $user, $target));
		}

		return null;
	}

	/**
	 * @param string $user
	 * @param string $target
	 * @param null|array $groups
	 * @param null|integer $counter
	 * @return \Moro\Platform\Model\Implementation\ApiKey\ApiKeyInterface
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function createEntityForUserAndTarget($user, $target, array $groups = null, $counter = null)
	{
		$groups || $groups = ['ROLE_USER'];

		if ($entity = $this->getEntityByUserAndTarget($user, $target, $groups, true))
		{
			if (empty($counter))
			{
				return $entity;
			}

			$this->deleteEntityById($entity->getId());
		}

		for ($i = 2; $i; $i--)
		{
			$record = [
				ApiKeyInterface::PROP_KEY     => sprintf('%08x%08x%08x%08x', mt_rand(), mt_rand(), mt_rand(), mt_rand()),
				ApiKeyInterface::PROP_USER    => $user,
				ApiKeyInterface::PROP_ROLES   => $groups ? implode(',', $groups) : '',
				ApiKeyInterface::PROP_TARGET  => $target,
				ApiKeyInterface::PROP_COUNTER => intval($counter) ?: -1,
			];

			try
			{
				$entity = $this->_newEntityFromArray($record, EntityInterface::FLAG_GET_FOR_UPDATE);
				$this->commit($entity);

				return $entity;
			}
			catch (DBALException $exception)
			{
				if ($i == 1)
				{
					throw $exception;
				}
			}
		}

		return null;
	}
}