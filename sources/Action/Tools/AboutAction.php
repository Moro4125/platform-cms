<?php
/**
 * Class AboutAction
 */
namespace Moro\Platform\Action\Tools;
use \Silex\Application;

/**
 * Class AboutAction
 * @package Moro\Platform\Action\Tools
 */
class AboutAction
{
	/**
	 * @param \Moro\Platform\Application|Application $application
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function __invoke(Application $application)
	{
		return $application->render('@PlatformCMS/admin/about.html.twig');
	}
}