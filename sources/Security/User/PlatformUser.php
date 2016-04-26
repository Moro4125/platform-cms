<?php
/**
 * Class PlatformUser
 */
namespace Moro\Platform\Security\User;
use \Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * Class PlatformUser
 * @package Moro\Platform\Security\User
 */
class PlatformUser implements AdvancedUserInterface
{
	private $username;
	private $password;
	private $enabled;
	private $accountNonExpired;
	private $credentialsNonExpired;
	private $accountNonLocked;
	private $roles;
	private $profile;
	private $authEnter;

	/**
	 * PlatformUser constructor.
	 *
	 * @param string $username
	 * @param string $password
	 * @param array $roles
	 * @param \Moro\Platform\Model\Implementation\Users\UsersInterface $profile
	 * @param \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface $enter
	 * @param bool|true $enabled
	 * @param bool|true $userNonExpired
	 * @param bool|true $credentialsNonExpired
	 * @param bool|true $userNonLocked
	 */
	public function __construct($username, $password, array $roles = array(), $profile, $enter, $enabled = true, $userNonExpired = true, $credentialsNonExpired = true, $userNonLocked = true)
	{
		if ('' === $username || null === $username)
		{
			throw new \InvalidArgumentException('The username cannot be empty.');
		}

		$this->username = $username;
		$this->password = $password;
		$this->enabled = $enabled;
		$this->accountNonExpired = $userNonExpired;
		$this->credentialsNonExpired = $credentialsNonExpired;
		$this->accountNonLocked = $userNonLocked;
		$this->roles = $roles;
		$this->profile = $profile;
		$this->authEnter = $enter;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getUsername();
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Users\UsersInterface
	 */
	public function getProfile()
	{
		return $this->profile;
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface
	 */
	public function getAuthEnter()
	{
		return $this->authEnter;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRoles()
	{
		return $this->roles;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSalt()
	{
		return $this->username;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isAccountNonExpired()
	{
		return $this->accountNonExpired;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isAccountNonLocked()
	{
		return $this->accountNonLocked;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isCredentialsNonExpired()
	{
		return $this->credentialsNonExpired;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * {@inheritdoc}
	 */
	public function eraseCredentials()
	{
		// Do not work with Remember_Me, if not use IS_AUTHENTICATED_REMEMBERED in security.access_rules options.
		// $this->password = null;
	}
}
