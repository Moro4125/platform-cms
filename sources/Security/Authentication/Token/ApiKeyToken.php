<?php
/**
 * Class ApiKeyToken
 */
namespace Moro\Platform\Security\Authentication\Token;
use \Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Class ApiKeyToken implements an api key token
 * @package Moro\Platform\Security\Authentication\Token
 */
class ApiKeyToken extends AbstractToken
{
	/**
	 * @var string
	 */
	protected $apiKey;

	/**
	 * Constructor
	 *
	 * @param string $apiKey the users API key
	 * @param array $roles an array of optional user roles
	 */
	public function __construct($apiKey, array $roles = array())
	{
		parent::__construct($roles);
		$this->apiKey = $apiKey;
		parent::setAuthenticated(count($roles) > 0);
	}

	/**
	 * @param bool $isAuthenticated
	 */
	public function setAuthenticated($isAuthenticated)
	{
		if ($isAuthenticated)
		{
			throw new \LogicException('Cannot set this token to trusted after instantiation.');
		}

		parent::setAuthenticated(false);
	}

	/**
	 * @return string
	 */
	public function getCredentials()
	{
		return $this->apiKey;
	}
}
