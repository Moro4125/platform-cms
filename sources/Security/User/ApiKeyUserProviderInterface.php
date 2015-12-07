<?php
/**
 * Interface ApiKeyUserProviderInterface
 */
namespace Moro\Platform\Security\User;
use \Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Interface ApiKeyUserProviderInterface
 * @package Moro\Platform\Security\User
 */
interface ApiKeyUserProviderInterface extends UserProviderInterface
{
	/**
	 * Load user by an API key
	 *
	 * @param string $apiKey the user's API key
	 * @return \Symfony\Component\Security\Core\User\UserInterface
	 */
	public function loadUserByApiKey($apiKey);
}
