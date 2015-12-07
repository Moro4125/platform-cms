<?php
/**
 * Class UserProvider
 */
namespace Moro\Platform\Security\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use \Symfony\Component\Security\Core\User\UserInterface;
use \Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use \Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use \Symfony\Component\Security\Core\User\User;
use \Moro\Platform\Application;
use \RuntimeException;

/**
 * Class UserProvider
 * @package Moro\Platform\Security\User
 */
class ApiKeyUserProvider implements ApiKeyUserProviderInterface
{
	/**
	 * @var Application;
	 */
	protected $_application;

	/**
	 * @param Application $application
	 */
	public function __construct(Application $application)
	{
		$this->_application = $application;
	}

	/**
	 * Load user by an API key
	 *
	 * @param string $apiKey the user's API key
	 * @return \Symfony\Component\Security\Core\User\UserInterface
	 */
	public function loadUserByApiKey($apiKey)
	{
		try
		{
			$service = $this->_application->getServiceApiKey();
			$entity = $service->getEntityByKey($apiKey);
			$service->commit($entity);

			return new User($entity->getUser(), $entity->getTarget(), $entity->getGroups());
		}
		catch (RuntimeException $exception)
		{
			throw new AccessDeniedHttpException('', $exception, 403);
		}
	}

	/**
	 * Loads the user for the given username.
	 *
	 * This method must throw UsernameNotFoundException if the user is not
	 * found.
	 *
	 * @param string $username The username
	 *
	 * @return UserInterface
	 *
	 * @see UsernameNotFoundException
	 *
	 * @throws UsernameNotFoundException if the user is not found
	 */
	public function loadUserByUsername($username)
	{
		throw new UsernameNotFoundException($username);
	}

	/**
	 * Refreshes the user for the account interface.
	 *
	 * It is up to the implementation to decide if the user data should be
	 * totally reloaded (e.g. from the database), or if the UserInterface
	 * object can just be merged into some internal array of users / identity
	 * map.
	 *
	 * @param UserInterface $user
	 *
	 * @return UserInterface
	 *
	 * @throws UnsupportedUserException if the account is not supported
	 */
	public function refreshUser(UserInterface $user)
	{
		return $user;
	}

	/**
	 * Whether this provider supports the given user class.
	 *
	 * @param string $class
	 *
	 * @return bool
	 */
	public function supportsClass($class)
	{
		return $class === User::class;
	}
}