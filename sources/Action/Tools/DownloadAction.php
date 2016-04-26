<?php
/**
 * Class DownloadAction
 */
namespace Moro\Platform\Action\Tools;
use \Moro\Platform\Application;
use \Moro\Platform\Action\AbstractContentAction;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \SplPriorityQueue;

/**
 * Class DownloadAction
 * @package Moro\Platform\Action\Tools
 *
 * @method \Moro\Platform\Model\Implementation\File\ServiceFile getService()
 */
class DownloadAction extends AbstractContentAction
{
	public $serviceCode = Application::SERVICE_FILE;
	public $route       = 'admin-download';
	public $template    = '@PlatformCMS/admin/download.html.twig';

	/**
	 * @param Application $application
	 * @param Request $request
	 * @param string $salt
	 * @param string $hash
	 * @param string $extension
	 * @return Response
	 */
	public function __invoke(Application $application, Request $request, $salt, $hash, $extension)
	{
		$this->setApplication($application);
		$this->setRequest($request);

		$service = $this->getService();
		$serviceContent = $application->getServiceContent();
		$queue = new SplPriorityQueue();
		$eLength = strlen($extension);
		$list = [];

		foreach ($service->selectByHash($hash) as $file)
		{
			$priority = 0;

			if (mb_strtolower(substr($file->getName(), -1 * $eLength)) === $extension)
			{
				$priority++;
			}

			if ($file->getKind() == 'a'.($contentId = (int)substr($file->getKind(), 1)))
			{
				$list[] = $serviceContent->getEntityById($contentId, true);
			}

			$queue->insert($file, $priority);
		}

		if ($queue->isEmpty())
		{
			throw new NotFoundHttpException();
		}

		/** @var \Moro\Platform\Model\Implementation\File\FileInterface $entity */
		$entity = $queue->extract();

		$uri = preg_replace('{^https?://[^/]+|.*index\\.php}', '', $application->url('download', ['file' => $entity]));
		$target = strtr($application->getOption('path.root').$uri, '/', DIRECTORY_SEPARATOR);
		$source = $service->getPathForHash($entity->getHash());

		if (!file_exists($target))
		{
			file_exists(dirname($target)) || @mkdir(dirname($target), 0755, true);
			link($source, $target) || copy($source, $target);
			@chmod($target, 0644);
		}

		$back = $request->headers->get('Referer', '/');
		$back.= (strpos($back, '#') ? '&' : '#').'close=Y';

		return $application->render($this->template, [
			'title'    => 'Download file: '.$entity->getName(),
			'entity'   => $entity,
			'back'     => $back,
			'url'      => $uri,
			'usedList' => array_filter($list),
		], new Response('', 200, [
			Application::HEADER_WITHOUT_BAR => 1,
			Application::HEADER_DO_NOT_SAVE => 1,
			Application::HEADER_CACHE_TAGS  => 'file-'.$entity->getSmallHash(),
			Application::HEADER_CACHE_FILE  => $uri,
		]));
	}
}