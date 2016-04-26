<?php
/**
 * Class LoginAction
 */
namespace Moro\Platform\Action\Tools;
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Class LoginAction
 * @package Moro\Platform\Action\Tools
 */
class LoginAction
{
	public $route    = 'users-login';
	public $template = '@PlatformCMS/pages/login.html.twig';

	/**
	 * @param Application $application
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function __invoke(Application $application, Request $request)
	{
		if (!$request->headers->get(Application::HEADER_SURROGATE) && !$request->query->get('back'))
		{
			$back = $request->headers->get('Referer', $application->url('admin-about'));
			$back = Request::create($back);

			$back->query->has('back') && $back = Request::create($back->query->get('back'));

			return $application->redirect($application->url($this->route, ['back' => $back->getRequestUri()]));
		}

		if ($error = $application['security.last_error']($request))
		{
			$application->getServiceFlash()->error($error);
		}

		return $application->render($this->template, [
			'providers' => $application['hybridauth.providers'],
		], new Response('', 200, [
			Application::HEADER_WITHOUT_BAR => 1,
		]));
	}
}