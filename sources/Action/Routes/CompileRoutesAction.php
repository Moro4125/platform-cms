<?php
/**
 * Class CompileRoutesAction
 */
namespace Moro\Platform\Action\Routes;
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\HttpKernelInterface;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Moro\Platform\Model\Implementation\Routes\Decorator\AdminDecorator;
use \Moro\Platform\Model\Implementation\Routes\RoutesInterface;
use \Exception;

/**
 * Class CompileRoutesAction
 * @package Action\Routes
 */
class CompileRoutesAction
{
	/**
	 * @var string
	 */
	protected $_route;

	/**
	 * @var \Moro\Platform\Application
	 */
	protected $_application;

	/**
	 * @var Request
	 */
	protected $_request;

	/**
	 * @var \Moro\Platform\Model\Implementation\Routes\ServiceRoutes
	 */
	protected $_service;

	/**
	 * @var array
	 */
	protected $_replace;

	/**
	 * @var array
	 */
	protected $_flashes;

	/**
	 * @var float
	 */
	protected $_workLimit;

	/**
	 * @param \Moro\Platform\Application|Application $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(Application $app, Request $request)
	{
		$this->_application = $app;
		$this->_request = $request;
		$this->_service = $app->getServiceRoutes();
		$this->_route = $request->attributes->get('_route');

		$this->_workLimit = round((intval(ini_get("max_execution_time")) ?: 30) * 0.7);

		if (!$app->isGranted('ROLE_EDITOR'))
		{
			$app->getServiceFlash()->error('У вас недостаточно прав для компиляции страниц сайта.');
			return $app->redirect($request->query->get('back', $app->url('admin-about')));
		}

		return $this->_service->with(new AdminDecorator($app), [ $this, 'execute' ]);
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function execute()
	{
		$app = $this->_application;
		$request = $this->_request;
		$service = $this->_service;

		/** @var \Moro\Platform\Model\Implementation\Routes\ServiceRoutes $service */
		$this->_flashes = $app['session']->getFlashBag()->all();
		$this->_replace = [
			$request->getSchemeAndHttpHost() => '',
			'/index.html' => '/',
			'//'.$request->getHttpHost() => $request->getSchemeAndHttpHost(),
			explode('index.php', $_SERVER['REQUEST_URI'])[0].'index.php' => '',
		];
		$list = ($id = (int)$request->query->get('id'))
			? [$service->getEntityById($id)]
			: $service->selectActiveOnly();
		$count = 0;

		while (TRUE)
		{
			/** @var \Moro\Platform\Model\Implementation\Routes\Decorator\AdminDecorator $entity */
			foreach ($list as $entity)
			{
				if (!$this->compile($entity))
				{
					break 2;
				}

				$count++;
			}

			$list = $service->selectInnerOnly();

			foreach ($list as $entity)
			{
				if (!$this->compile($entity))
				{
					break 2;
				}

				$count++;
			}

			$entity = $service->getByRouteAndQuery('compile-site-map', []);
			$this->compile($entity);
			$count++;

			break;
		}

		$app['session']->getFlashBag()->setAll($this->_flashes);
		$message = $id ? 'Страница сайта сгенерирована' : 'Страницы сайта сгенерированы';
		$app->getServiceFlash()->success($message.' (затронуто файлов: '.$count.').');

		return $app->redirect($request->query->get('back', $app->url('admin-about')));
	}

	/**
	 * @param \Moro\Platform\Model\Implementation\Routes\Decorator\AdminDecorator $entity
	 * @return bool
	 */
	public function compile($entity)
	{
		$start = microtime(true);

		$app = $this->_application;
		$request = $this->_request;
		$service = $this->_service;
		$replace = $this->_replace;
		$uri = $entity->getUri();

		try
		{
			if (empty($uri) || false !== strpos($uri, '#') || false !== strpos($uri, '?'))
			{
				throw new NotFoundHttpException();
			}

			$entity->setCompileFlag(false);

			/** @var \Symfony\Component\HttpFoundation\Response $response */
			$xRequest = Request::create($uri, 'GET', [], [], [], $request->server->all());
			$xRequest->headers->set(Application::HEADER_SURROGATE, 'SSI/1.0');
			$response = $app->handle($xRequest, HttpKernelInterface::SUB_REQUEST, false);

			if (200 != $statusCode = $response->getStatusCode())
			{
				if ($statusCode == 302)
				{
					throw new NotFoundHttpException();
				}

				throw new Exception('Действие по генерации страницы вернуло код '.$statusCode.'.');
			}

			if ($response->headers->get(Application::HEADER_EXPERIMENTAL) != false)
			{
				$message = 'Публикация страницы %1$s пропущена, т.к. она содержит экспериментальный функционал.';
				$app->getServiceFlash()->alert(sprintf($message, $uri));
				return $this->_workLimit > 0;
			}

			if ($response->headers->get(Application::HEADER_USE_FULL_URL))
			{
				unset($replace[$request->getSchemeAndHttpHost()]);
				unset($replace['//'.$request->getHttpHost()]);
			}

			if ($tags = $response->headers->get(Application::HEADER_CACHE_TAGS))
			{
				$entity->setTags(explode(',', $tags));
			}

			$title = preg_match('{<title>(.*?)</title>}', $response->getContent(), $match) ? $match[1] : null;
			$entity->setTitle(htmlspecialchars_decode($title) ?: '~ Заголовок на странице отсутствует ~');
			$content = strtr($response->getContent(), $replace);

			$folder = $app->getOption('path.root').dirname($uri);
			$file = $app->getOption('path.root').$uri;

			$oldFile = $entity->getFile();
			$oldFile && $oldFile != $uri && @unlink($app->getOption('path.root').$oldFile);
			$entity->setFile($uri);

			if (!$response->headers->get(Application::HEADER_DO_NOT_SAVE))
			{
				file_exists($folder) || @mkdir($folder, 0755, true);
				file_put_contents($temp = tempnam($folder, 'html'), $content);
				rename($temp, $file) && @chmod($file, 0644);
			}

			if ($entity->getRoute() == 'admin-image')
			{
				$id = $entity->getId();
				$service->deleteEntityById($id);
			}
			else
			{
				$service->commit($entity);
				$id = $entity->getId();
			}

			foreach ($service->selectEntities(null, null, null, RoutesInterface::PROP_FILE, $uri) as $item)
			{
				if (($itemId = $item->getId()) != $id)
				{
					$service->deleteEntityById($itemId);
				}
			}
		}
		catch (NotFoundHttpException $exception)
		{
			$service->deleteEntityById($entity->getId());

			if (($file = $entity->getFile()) && file_exists($app->getOption('path.root').$file))
			{
				$service->getByFileName($file) || unlink($app->getOption('path.root').$file);
			}
		}
		catch (Exception $exception)
		{
			$message = sprintf('При компиляции страницы %1$s произошла ошибка: ', $uri);
			$message.= $exception->getMessage();
			$app->getServiceFlash()->error($message);

			$app->getServiceLogger()->error($exception->getMessage(), [
				'class' => get_class($exception),
				'code' => $exception->getCode(),
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'url' => $uri,
			]);

			if ($sentry = $app->getServiceSentry())
			{
				$sentry->captureException($exception, [
					'tags' => ['controller' => isset($xRequest) ? $xRequest->attributes->get('_route') : $this->_route],
				]);
			}
		}
		finally
		{
			$this->_flashes = array_merge($this->_flashes, $app['session']->getFlashBag()->all());
			$this->_workLimit -= (microtime(true) - $start);
		}

		return $this->_workLimit > 0;
	}
}