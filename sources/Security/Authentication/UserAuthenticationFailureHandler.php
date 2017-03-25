<?php
/**
 * Class UserAuthenticationFailureHandler
 */
namespace Moro\Platform\Security\Authentication;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Security\Http\HttpUtils;
use \Symfony\Component\HttpKernel\HttpKernel;
use \Symfony\Component\Security\Core\Exception\AuthenticationException;
use \Symfony\Component\Security\Core\Exception\BadCredentialsException;
use \Silex\Application;

/**
 * Class UserAuthenticationFailureHandler
 * @package Moro\Platform\Security\Authentication
 */
class UserAuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
	/**
	 * @var \Moro\Platform\Application
	 */
	protected $_app = null;

	/**
	 * UserAuthenticationSuccessHandler constructor.
	 *
	 * @param HttpKernel $httpKernel
	 * @param HttpUtils $httpUtils
	 * @param array $options
	 * @param Application $app
	 */
	public function __construct(HttpKernel $httpKernel, HttpUtils $httpUtils, array $options, Application $app)
	{
		parent::__construct($httpKernel, $httpUtils, $options);
		$this->_app = $app;
	}

	/**
	 * @param Request $request
	 * @param AuthenticationException $exception
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
	{
		if (($parentException = $exception->getPrevious()) && $parentException instanceof BadCredentialsException)
		{
			/** @var \Moro\Platform\Security\User\PlatformUserProvider $usersProvider */
			if ($usersProvider = $this->_app['security.user_provider.admin'])
			{
				if ($userToken = $usersProvider->getLastUser())
				{
					if ($enter = $userToken->getAuthEnter())
					{
						$count = $enter->getProperty(UsersAuthInterface::PROP_FAILURE);
						$enter->setProperty(UsersAuthInterface::PROP_UPDATED_IP, implode(', ', $request->getClientIps()));
						$enter->setProperty(UsersAuthInterface::PROP_FAILURE, $count + 1);
						$enter->setProperty(UsersAuthInterface::PROP_RESULT, 0);
						$this->_app->getServiceUsersAuth()->commit($enter);
					}
				}
			}
		}

		return parent::onAuthenticationFailure($request, $exception);
	}
}