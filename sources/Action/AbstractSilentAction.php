<?php
/**
 * Class AbstractSilentAction
 */
namespace Moro\Platform\Action;
use \Moro\Platform\Model\EntityInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Application;
use \Exception;

/**
 * Class AbstractSilentAction
 * @package Action
 */
abstract class AbstractSilentAction extends AbstractContentAction
{
	/**
	 * @var string  Название "пути" к действию по отображению списочной страницы.
	 */
	public $routeIndex;

	/**
	 * @var \Moro\Platform\Model\EntityInterface[]
	 */
	protected $_entities;

	/**
	 * @var bool
	 */
	protected $_flag;

	/**
	 * @param Application|SilexApplication $app
	 * @param Request $request
	 * @param string $ids
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request, $ids)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->route));
		assert(!empty($this->routeIndex));

		$this->_flag = !empty($request->query->get('flag'));
		$this->setApplication($app);
		$this->setRequest($request);
		$service = $this->getService();

		$list = array_filter(array_map('str_to_int', explode(',', $ids)));
		$this->_setEntities(array_filter(array_map(function($id) use ($service) {
			return $service->getEntityById($id, true, EntityInterface::FLAG_GET_FOR_UPDATE);
		}, $list)));

		if (!$back = $request->query->has('back'))
		{
			$back = ($request->headers->has('Referer') && $request->headers->get('Referer'))
				? $request->headers->get('Referer')
				: false;
		}

		try
		{
			if (!$app->isGranted('ROLE_EDITOR'))
			{
				$app->getServiceFlash()->error('У вас недостаточно прав для данного действия.');
			}
			elseif ($response = $this->_execute())
			{
				return $response;
			}
		}
		catch (Exception $exception)
		{
			$app->getServiceFlash()->error(basename(get_class($exception)).': '.$exception->getMessage());
			($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
		}

		$fragment = '#selected='.implode(',', $list);
		return $app->redirect(($back ?: $app->url($this->routeIndex)).$fragment);
	}

	/**
	 * @param array|EntityInterface[] $entities
	 * @return $this
	 */
	protected function _setEntities(array $entities)
	{
		$this->_entities = $entities;
		return $this;
	}

	/**
	 * @return EntityInterface[]
	 */
	public function getEntities()
	{
		return $this->_entities;
	}

	/**
	 * @return null|Response
	 */
	abstract protected function _execute();
}