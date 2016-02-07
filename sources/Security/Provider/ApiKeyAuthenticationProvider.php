<?php
/**
 * Class ApiKeyAuthenticationProvider
 */
namespace Moro\Platform\Security\Provider;
use \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \Symfony\Component\Security\Core\Exception\AuthenticationException;
use \Symfony\Component\Security\Core\User\UserInterface;
use \Moro\Platform\Security\Authentication\Token\ApiKeyToken;
use \Moro\Platform\Security\User\ApiKeyUserProviderInterface;
use \Moro\Platform\Security\Encoder\SaltLessPasswordEncoderInterface;

/**
 * Class ApiKeyAuthenticationProvider
 * @package Moro\Platform\Security\Provider
 */
class ApiKeyAuthenticationProvider implements AuthenticationProviderInterface
{
	/**
	 * Encoder used to encode the API key
	 *
	 * We're using a salt-less password encoder.
	 * There is no way of looking up the salt since we don't know who the user is
	 * The encoder can of course implement a static, common salt for all passwords
	 *
	 * @var SaltLessPasswordEncoderInterface
	 */
	protected $encoder;

	/**
	 * User provider
	 * @var ApiKeyUserProviderInterface
	 */
	protected $userProvider;

	/**
	 * @param ApiKeyUserProviderInterface $userProvider
	 * @param SaltLessPasswordEncoderInterface $encoder
	 */
	public function __construct(ApiKeyUserProviderInterface $userProvider, SaltLessPasswordEncoderInterface $encoder)
	{
		$this->userProvider = $userProvider;
		$this->encoder = $encoder;
	}

	/**
	 * Authenticate the user based on an API key
	 *
	 * @param TokenInterface $token
	 * @return ApiKeyToken|TokenInterface
	 */
	public function authenticate(TokenInterface $token)
	{
		$user = $this->userProvider->loadUserByApiKey($this->encoder->encodePassword($token->getCredentials()));

		if (!$user || !($user instanceof UserInterface))
		{
			throw new AuthenticationException('Bad credentials');
		}

		$token = new ApiKeyToken($token->getCredentials(), $user->getRoles());
		$token->setUser($user);

		return $token;
	}

	/**
	 * @param TokenInterface $token
	 * @return bool
	 */
	public function supports(TokenInterface $token)
	{
		return $token instanceof ApiKeyToken;
	}
}
