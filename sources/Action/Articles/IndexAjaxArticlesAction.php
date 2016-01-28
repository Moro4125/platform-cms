<?php
/**
 * Class IndexAjaxArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application as SilexApplication;
use \Moro\Platform\Model\Implementation\Content\Decorator\AjaxSelectDecorator;


/**
 * Class IndexAjaxArticlesAction
 * @package Action
 */
class IndexAjaxArticlesAction
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
		$service = $app->getServiceContent();
		$service->appendDecorator(new AjaxSelectDecorator($app));
		$pageSize = $this->_pageSize;

		$list = [];
		$page = max(1, (int)$request->query->get('page'));
		$query = trim((string)$request->query->get('q'));
		$offset = $pageSize * ($page - 1);
		$orderBy = strlen($query) ? 'name' : '!updated_at';

		($dots = strpos($query, '…')) && $query = substr($query, 0, $dots);

		if ($size = $service->getCount([$field = '~name'], [$query]))
		{
			$list = $service->selectEntities($offset, $pageSize, $orderBy, ['~name'], [$query]);
		}
		elseif ($size = $service->getCount([$field = strpos($query, ',') ? 'tag' : '~tag'], [$query]))
		{
			$list = $service->selectEntities($offset, $pageSize, $orderBy, [$field], [$query]);
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