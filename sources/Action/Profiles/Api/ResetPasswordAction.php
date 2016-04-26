<?php
/**
 * Class ApiResetPasswordAction
 */
namespace Moro\Platform\Action\Profiles\Api;
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Class ResetPasswordAction
 * @package Moro\Platform\Action\Profiles\Api
 */
class ResetPasswordAction
{
	/**
	 * @param Application $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(Application $app, Request $request)
	{
		$provider   = UsersAuthInterface::MAIN_PROVIDER;
		$identifier = $app->getServiceSecurityToken()->getUsername();
		$credential = $request->query->get('credential');
		$flags      = UsersAuthInterface::FLAG_GET_FOR_UPDATE;

		$enter = $app->getServiceUsersAuth()->getEntityByProviderAndIdentifier($provider, $identifier, null, $flags);
		$enter->setProperty(UsersAuthInterface::PROP_CREDENTIAL, $credential);
		$app->getServiceUsersAuth()->commit($enter);

		$app->getServiceFlash()->success('Пароль был успешно изменён.');

		$back = Request::create($request->query->get('back', '/'));
		return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
	}
}