<?php
/**
 * Class IndexRoutesAction
 */
namespace Moro\Platform\Action\Routes;
use \Silex\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Moro\Platform\Model\Implementation\Routes\Decorator\AdminDecorator;

/**
 * Class IndexRoutesAction
 * @package Action\Routes
 */
class IndexRoutesAction
{
	/**
	 * @var string
	 */
	protected $_template = '@PlatformCMS/admin/compile-list.html.twig';

	/**
	 * @param \Moro\Platform\Application|Application $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(Application $app, Request $request)
	{
		return $app->getServiceRoutes()->with(new AdminDecorator($app), function($service) use ($request, $app) {
			$filter = $request->query->get('all') ? [] : ['!route' => 'inner'];
			$order = ['!compile_flag', '!updated_at'];

			/** @var \Moro\Platform\Model\Implementation\Routes\ServiceRoutes $service */
			$list = $service->selectEntities(null, 100, $order, array_keys($filter), array_values($filter));
			$form = $service->createAdminListForm($app, $list);

			if ($form->handleRequest($request)->isValid())
			{
				$app->getServiceRoutes()->commitAdminListForm($app, $form);

				/** @noinspection PhpUndefinedMethodInspection */
				return $app->redirect($form->get('compile')->isClicked()
					? $app->url('admin-compile', ['back' => $request->getRequestUri()])
					: $request->getUri()
				);
			}

			return $app->render($this->_template, [
				'form'   => $form->createView(),
				'routes' => $list,
			]);
		});
	}
}