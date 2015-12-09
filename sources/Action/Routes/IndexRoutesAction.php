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
	 * @var int
	 */
	protected $_count = 50;

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

			$page = max(1, (int)$request->query->get('page', 1));
			$offset = ($page - 1) * $this->_count;

			/** @var \Moro\Platform\Model\Implementation\Routes\ServiceRoutes $service */
			$list = $service->selectEntities($offset, $this->_count, $order, array_keys($filter), array_values($filter));
			$form = $service->createAdminListForm($app, $list);
			$total = $service->getCount(array_keys($filter), array_values($filter));

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
				'page'   => $page,
				'pages'  => ceil(($total ?: 1) / $this->_count),
				'offset' => $offset,
				'total'  => $total,
			]);
		});
	}
}