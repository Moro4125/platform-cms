<?php
/**
 * Class AboutAction
 */
namespace Moro\Platform\Action\Tools;
use \Silex\Application;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Class AboutAction
 * @package Moro\Platform\Action\Tools
 */
class AboutAction
{
	/**
	 * @param \Moro\Platform\Application|Application $application
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function __invoke(Application $application, Request $request)
	{
		return $application->render('@PlatformCMS/admin/about.html.twig', [
			'title' => 'Информация'.' :: '.$request->getHost(),
		]);
	}
}