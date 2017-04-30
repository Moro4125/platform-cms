<?php
/**
 * Class ServiceUsersAuth
 */
namespace Moro\Platform\Model\Implementation\Users\Auth;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\Exception\EntityNotFoundException;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;

/**
 * Class ServiceUsersAuth
 * @package Moro\Platform\Model\Implementation\Users\Auth
 */
class ServiceUsersAuth extends AbstractService
{
	use \Moro\Platform\Model\Accessory\OrderAt\OrderAtServiceTrait;
	use \Moro\Platform\Model\Accessory\MonologServiceTrait;

	protected $_table = 'users_auth';

	/**
	 * @param string $provider
	 * @param string $identifier
	 * @param string $credential
	 * @param null|bool $withoutCommit
	 * @return UsersAuthInterface
	 */
	public function createEntity($provider, $identifier, $credential, $withoutCommit = null)
	{
		$fields = [
			UsersAuthInterface::PROP_PROVIDER   => $provider,
			UsersAuthInterface::PROP_IDENTIFIER => $identifier,
			UsersAuthInterface::PROP_CREDENTIAL => $credential,
		];
		$entity = $this->_newEntityFromArray($fields, UsersAuthInterface::FLAG_GET_FOR_UPDATE);
		$withoutCommit || $this->commit($entity);

		return $entity;
	}

	/**
	 * @param string $provider
	 * @param string $identifier
	 * @param null|bool $withoutException
	 * @param null|int $flags
	 * @return UsersAuthInterface|null
	 */
	public function getEntityByProviderAndIdentifier($provider, $identifier, $withoutException = null, $flags = null)
	{
		$filter = [EntityAuth::PROP_PROVIDER => $provider, EntityAuth::PROP_IDENTIFIER => $identifier];

		foreach ($this->selectEntities(0, 1, null, $filter, null, $flags) as $entity)
		{
			return $entity;
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'identifier', $provider.':'.$identifier);
			throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_CODE);
		}

		$this->_cacheDependency = null;
		return null;
	}

	/**
	 * @param string $provider
	 * @param UsersInterface|int $user
	 * @param null|bool $withoutException
	 * @param null|int $flags
	 * @return UsersAuthInterface|null
	 */
	public function getEntityByProviderAndUser($provider, $user, $withoutException = null, $flags = null)
	{
		$userId = ($user instanceof UsersInterface) ? $user->getId() : (int)$user;
		$filter = [EntityAuth::PROP_PROVIDER => $provider, EntityAuth::PROP_USER_ID => $userId];

		foreach ($this->selectEntities(0, 1, null, $filter, null, $flags) as $entity)
		{
			return $entity;
		}

		if (empty($withoutException))
		{
			$message = sprintf(EntityNotFoundException::M_NOT_FOUND, 'identifier', $provider.':'.$userId);
			throw new EntityNotFoundException($message, EntityNotFoundException::C_BY_ID);
		}

		$this->_cacheDependency = null;
		return null;
	}

	/**
	 * @param UsersInterface|int $user
	 * @param null|int $flags
	 * @return UsersAuthInterface[]
	 */
	public function selectEntitiesByUser($user, $flags = null)
	{
		$userId = ($user instanceof UsersInterface) ? $user->getId() : (int)$user;
		$filter = [EntityAuth::PROP_USER_ID => $userId];

		return $this->selectEntities(0, null, null, $filter, null, $flags);
	}
}