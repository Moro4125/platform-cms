<?php
/**
 * Class IndexRoutesAction
 */
namespace Moro\Platform\Action\Routes;
use \Silex\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Implementation\Routes\Decorator\AdminDecorator;
use \Moro\Platform\Model\Implementation\Routes\RoutesInterface;

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
			$filter = $request->query->get('all') ? [] : ['!'.RoutesInterface::PROP_ROUTE => 'inner'];
			$order = ['!'.RoutesInterface::PROP_COMPILE_FLAG, '!'.RoutesInterface::PROP_UPDATED_AT];

			$page = max(1, (int)$request->query->get('page', 1));
			$offset = ($page - 1) * $this->_count;

			/** @var \Moro\Platform\Model\Implementation\Routes\ServiceRoutes $service */
			$list = $service->selectEntities($offset, $this->_count, $order, array_keys($filter), array_values($filter));
			$form = $service->createAdminListForm($app, $list);
			$total = $service->getCount(array_keys($filter), array_values($filter), EntityInterface::FLAG_GET_FOR_UPDATE);

			if ($form->handleRequest($request)->isValid())
			{
				/** @noinspection PhpUndefinedMethodInspection */
				if ($form->get('select_all')->isClicked())
				{
					$count = $app->getServiceRoutes()->setCompileFlagForAll();
					$app->getServiceFlash()->info(sprintf('Дополнительно помеченных записей: %1$s.', $count));
				}
				else
				{
					$app->getServiceRoutes()->commitAdminListForm($app, $form);
				}

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