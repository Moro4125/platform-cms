<?php
/**
 * Class IndexAjaxImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Model\Implementation\File\Decorator\AjaxSelectDecorator;


/**
 * Class IndexAjaxImagesAction
 * @package Action
 */
class IndexAjaxImagesAction
{
	/**
	 * @var int
	 */
	protected $_pageSize = 32;

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request)
	{
		$service = $app->getServiceFile();
		$service->appendDecorator(new AjaxSelectDecorator($app));
		$pageSize = $this->_pageSize;

		$list = [];
		$page = max(1, (int)$request->query->get('page'));
		$query = trim((string)$request->query->get('q'));
		$offset = $pageSize * ($page - 1);
		$orderBy = strlen($query) ? 'name' : '!updated_at';

		($dots = strpos($query, '…')) && $query = substr($query, 0, $dots);

		if ($size = $service->getCount(['kind', $field = '~name'], ['1x1', $query]))
		{
			$list = $service->selectEntities($offset, $pageSize, $orderBy, ['kind', '~name'], ['1x1', $query]);
		}
		elseif ($size = $service->getCount(['kind', $field = strpos($query, ',') ? 'tag' : '~tag'], ['1x1', $query]))
		{
			$list = $service->selectEntities($offset, $pageSize, $orderBy, ['kind', $field], ['1x1', $query]);
		}
		elseif ($size = $service->getCount(['kind', $field = ($dots ? '~hash' : 'hash')], ['1x1', $query]))
		{
			$list = $service->selectEntities($offset, $pageSize, $orderBy, ['kind', $field], ['1x1', $query]);
		}
		else
		{
			$field = 'not found';
		}

		return $app->json([
			'total' => $size,
			'chunk' => $pageSize,
			'page' => $page,
			ltrim($field, '~') => strlen($query) ? $query : '*',
			'list' => array_values($list),
		]);
	}
}