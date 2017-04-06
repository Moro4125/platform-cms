<?php
/**
 * Class Options
 */
namespace Moro\Platform\Action\Tools;
use \Silex\Application;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Class Options
 * @package Moro\Platform\Action\Tools
 */
class OptionsAction
{
	/**
	 * @var string
	 */
	protected $template = '@PlatformCMS/admin/options.html.twig';

	/**
	 * @param \Moro\Platform\Application|Application $application
	 * @param Request $request
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function __invoke(Application $application, Request $request)
	{
		$form = $application->getServiceOptions()->createAdminForm($application);

		if ($form->handleRequest($request)->isValid())
		{
			if ($application->isGranted('ROLE_EDITOR'))
			{
				$application->getServiceOptions()->commitAdminForm($application, $form);
				$application->getServiceFlash()->success('Изменения сохранены');
				$application->getServiceRoutes()->setCompileFlagForTag('options');
			}
			else
			{
				$application->getServiceFlash()->error('У вас недостаточно прав для изменения настроек.');
			}

			return $application->redirect($request->getUri());
		}

		return $application->render($this->template, [
			'title' => 'Настройки'.' :: '.$request->getHost(),
			'form' => $form->createView(),
		]);
	}
}