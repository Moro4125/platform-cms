<?php
/**
 * Class PrefixAction
 */
namespace Moro\Platform\Action\Tools;
use \Silex\Application;

/**
 * Class PrefixAction
 * @package Moro\Platform\Action\Tools
 */
class PrefixAction
{
	/**
	 * @param \Moro\Platform\Application|Application $application
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function __invoke(Application $application)
	{
		if ($application->isGranted('ROLE_RS_PANEL'))
		{
			return $application->redirect($application->url('admin-about'));
		}
		else
		{
			return $application->redirect($application->url('users-login'));
		}
	}
}