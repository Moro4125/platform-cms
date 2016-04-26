<?php
/**
 * Class UserAuthenticationSuccessHandler
 */
namespace Moro\Platform\Security\Authentication;
use \Moro\Platform\Security\User\PlatformUser;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Security\Http\HttpUtils;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \Silex\Application;

/**
 * Class UserAuthenticationSuccessHandler
 * @package Moro\Platform\Security\Authentication
 */
class UserAuthenticationSuccessHandler extends DefaultAuthenticationSuccessHandler
{
	/**
	 * @var \Moro\Platform\Application
	 */
	protected $_app = null;

	/**
	 * UserAuthenticationSuccessHandler constructor.
	 *
	 * @param HttpUtils $httpUtils
	 * @param array $options
	 * @param Application $app
	 */
	public function __construct(HttpUtils $httpUtils, array $options, Application $app)
	{
		parent::__construct($httpUtils, $options);
		$this->_app = $app;
	}

	/**
	 * @param Request $request
	 * @param TokenInterface $token
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function onAuthenticationSuccess(Request $request, TokenInterface $token)
	{
		if (($user = $token->getUser()) && $user instanceof PlatformUser)
		{
			if (($enter = $user->getAuthEnter()) && $enter instanceof UsersAuthInterface)
			{
				$count = $enter->getProperty(UsersAuthInterface::PROP_SUCCESS);
				$enter->setProperty(UsersAuthInterface::PROP_UPDATED_IP, implode(', ', $request->getClientIps()));
				$enter->setProperty(UsersAuthInterface::PROP_ORDER_AT, time());
				$enter->setProperty(UsersAuthInterface::PROP_SUCCESS, $count + 1);
				$enter->setProperty(UsersAuthInterface::PROP_RESULT, 1);
				$this->_app->getServiceUsersAuth()->commit($enter);
			}
		}

		// Magic auth hack :-)
		$this->_app['session']->set('_security_public', serialize($token));
		$this->_app['session']->set('_security_admin',  serialize($token));

		return parent::onAuthenticationSuccess($request, $token);
	}
}