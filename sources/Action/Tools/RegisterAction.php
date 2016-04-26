<?php
/**
 * Class RegisterAction
 */
namespace Moro\Platform\Action\Tools;
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Class RegisterAction
 * @package Moro\Platform\Action\Tools
 */
class RegisterAction
{
	public $template = '@PlatformCMS/pages/register.html.twig';

	/**
	 * @param Application $application
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function __invoke(Application $application, Request $request)
	{
		if ($error = $application['security.last_error']($request))
		{
			$application->getServiceFlash()->error($error);
		}

		return $application->render($this->template, [], new Response('', 200, [
			Application::HEADER_WITHOUT_BAR => 1,
		]));
	}
}