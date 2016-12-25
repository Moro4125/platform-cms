<?php
/**
 * Class PlatformUserProvider
 */
namespace Moro\Platform\Security\User;
use \Symfony\Component\Security\Core\User\UserProviderInterface;
use \Symfony\Component\Security\Core\User\UserInterface;
use \Symfony\Component\Security\Core\User\User;
use \Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use \Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use \Moro\Platform\Model\Implementation\Users\ServiceUsers;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Moro\Platform\Model\Implementation\Users\Auth\ServiceUsersAuth;

/**
 * Class PlatformUserProvider
 * @package Moro\Platform\Security\User
 */
class PlatformUserProvider implements UserProviderInterface
{
	/**
	 * @var ServiceUsers
	 */
	protected $_usersService;

	/**
	 * @var ServiceUsersAuth
	 */
	protected $_authService;

	/**
	 * @var PlatformUser
	 */
	protected $_lastUser;

	/**
	 * @var string
	 */
	protected $_provider;

	/**
	 * PlatformUserProvider constructor.
	 * @param ServiceUsers $users
	 * @param ServiceUsersAuth $authService
	 * @param null|string $provider
	 */
	public function __construct(ServiceUsers $users, ServiceUsersAuth $authService, $provider = null)
	{
		$this->_usersService = $users;
		$this->_authService = $authService;
		$this->_provider = $provider ?: UsersAuthInterface::MAIN_PROVIDER;
	}

	/**
	 * @param string $username
	 * @return PlatformUser
	 */
	public function loadUserByUsername($username)
	{
		$list = $this->_authService->selectEntities(0, 1, null, [
			UsersAuthInterface::PROP_PROVIDER => $this->_provider,
			UsersAuthInterface::PROP_IDENTIFIER => $username,
		], null, UsersAuthInterface::FLAG_GET_FOR_UPDATE);

		/** @var UsersAuthInterface $auth */
		if (empty($list) || !$auth = reset($list))
		{
			throw new UsernameNotFoundException(sprintf('User "%s" does not exist (%s).', $username, $this->_provider));
		}

		/** @var \Moro\Platform\Model\Implementation\Users\UsersInterface $profile */
		if (!$profile = $this->_usersService->getEntityById($auth->getProperty(UsersAuthInterface::PROP_USER_ID), true))
		{
			throw new UsernameNotFoundException(sprintf('Profile "%s" does not exist.', $username));
		}

		if ($profile->hasTag('флаг: удалено'))
		{
			throw new UsernameNotFoundException(sprintf('Profile "%s" is deleted.', $username));
		}

		$roles = array_merge(explode(',', $auth->getRoles()), ['ROLE_USER']);
		$credential = $auth->getCredential();

		$this->_lastUser = new PlatformUser($username, $credential, $roles, $profile, $auth, !$auth->getBanned());
		$token = new UsernamePasswordToken($this->_lastUser, $credential, $this->_provider, $roles);

		$this->_usersService->setServiceUser($token);

		return $this->_lastUser;
	}

	/**
	 * @return PlatformUser|null
	 */
	public function getLastUser()
	{
		return $this->_lastUser;
	}

	/**
	 * @param UserInterface|PlatformUser $user
	 * @return User|PlatformUser
	 */
	public function refreshUser(UserInterface $user)
	{
		if (!$user instanceof PlatformUser)
		{
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
		}

		$this->_provider = $user->getAuthEnter()->getProvider();
		return $this->loadUserByUsername($user->getUsername());
	}

	/**
	 * @param string $class
	 * @return bool
	 */
	public function supportsClass($class)
	{
		return $class === PlatformUser::class || is_subclass_of($class, PlatformUser::class);
	}
}