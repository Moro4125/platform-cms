<?php
/**
 * Class AbstractCreateAction
 */
namespace Moro\Platform\Action;


use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;


/**
 * Class AbstractCreateAction
 * @package Action
 */
abstract class AbstractCreateAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по отображению списочной страницы.
	 */
	public $routeIndex;

	/**
	 * @var string  Название "пути" к действию по редактированию записи.
	 */
	public $routeUpdate;

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->route));
		assert(!empty($this->routeIndex));
		assert(!empty($this->routeUpdate));

		$this->setApplication($app);
		$this->setRequest($request);

		if (!$request->query->has('back'))
		{
			return $app->redirect($app->url($this->route, [
				'back' => ($request->headers->has('Referer') && $request->headers->get('Referer'))
					? $request->headers->get('Referer')
					: 0,
			]));
		}

		if (!$app->isGranted('ROLE_EDITOR'))
		{
			$app->getServiceFlash()->error('У вас недостаточно прав для создания новой записи.');

			return $app->redirect($request->query->get('back') ?: $app->url($this->routeIndex));
		}

		return $app->redirect($app->url($this->routeUpdate, [
			'id'   => $this->getService()->createNewEntityWithId()->getId(),
			'back' => $request->query->get('back') ?: $app->url($this->routeIndex)
		]));
	}
}