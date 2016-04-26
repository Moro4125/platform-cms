<?php
/**
 * File with middlewares.
 */
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\Routing\Exception\ResourceNotFoundException;
use \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

// ============================================== //
//    Преобразование объектов запроса и ответа    //
// ============================================== //
Application::getInstance(function(Application $app) {
	$relinkRouteId = null;
	$lastRouteId = null;
	$usedURI = [];

	// === Обработка POST запроса с данными в формате "application/json".
	$app->before(function(Request $request) {
		if (strncmp($request->headers->get('Content-Type'), 'application/json', 16) === 0)
		{
			$data = json_decode($request->getContent(), true);
			$request->request->replace(is_array($data) ? $data : []);
		}
	});

	// === Формирование страницы для кода 401 ответа сервера.
	$app->after(function(Request $request, Response $response) use ($app) {
		$contentType = $response->headers->get('Content-Type');

		if ($contentType == 'application/json')
		{
			$response->headers->set('Content-Type', $contentType.'; charset=utf-8', true);
		}

		if (false === strpos($request->getUri(), '/admin/'))
		{
			return;
		}

		if ($response->getStatusCode() == 401 && strncmp($contentType, 'text/html', 9) === 0)
		{
			$filePath = dirname($app->getOption('path.ir6e')).DIRECTORY_SEPARATOR.'error401.html';
			$response->setContent(file_get_contents($filePath));
		}
	});

	// === Устновка игнорирования утилитой автоматической расстановки ссылок в тексте ссылки на текущую страницу.
	$app->afore(function(Request $request) use ($app, &$relinkRouteId) {
		$route = $request->get('_route');
		$accept = $request->headers->get('Accept', '');
		$flag = $app->getOption('content.relink') != false;

		if ($flag && !preg_match('{^(GET_|admin-|_)}', $route) && false !== strpos($accept, 'text/html'))
		{
			$service = $app->getServiceRelinkTool();
			$uri = substr($request->getRequestUri(), (strpos($request->getRequestUri(), 'index.php') ?: -9) + 9);
			$uri = substr($uri, 0, strpos($uri, '?') ?: strlen($uri));
			$skip = $service->getSkipMarker();
			$prefix = '';

			foreach ($app->getServiceRelink()->selectByHref($uri) as $entity)
			{
				$parameters = $entity->getParameters();
				$words = isset($parameters['nominativus'])
					?( is_array($parameters['nominativus'])
						? $parameters['nominativus'][0]
						: explode(',', $parameters['nominativus'])[0]
					): '';

				$prefix .= $words ? '<!--'.$skip.':'.$words.'-->' : '';
			}

			if ($prefix)
			{
				$service->setContentPrefix($prefix);
				$relinkRouteId = $route;
			}
		}
	});

	// === Применение утилиты автоматической расстановки ссылок в тексте (для основного и внутренних запросов).
	$app->behind(function(Request $request, Response $response) use ($app, &$relinkRouteId) {
		$route = $request->get('_route');
		$contentType = $response->headers->get('Content-Type');
		$flag = $app->getOption('content.relink') == 'global';

		if ($flag && !preg_match('{^(GET_|admin-|_)}', $route) && strncmp($contentType, 'text/html', 9) === 0)
		{
			$service = $app->getServiceRelinkTool();
			$service->setUseBlockMarker(true);
			$content = $response->getContent();

			if ($found = $service->search($content))
			{
				$tags = (array)$response->headers->get(Application::HEADER_CACHE_TAGS);

				foreach (array_unique(array_intersect_key($app->getServiceRelink()->getIdMap(), $found)) as $id)
				{
					$tags[] = 'link-'.$id;
				}

				$response->headers->set(Application::HEADER_CACHE_TAGS, implode(',', $tags));
			}

			$content = $service->apply($content);
			$response->setContent($content);
		}

		if ($route === $relinkRouteId)
		{
			$service = $app->getServiceRelinkTool();
			$service->setContentPrefix('');
			$relinkRouteId = null;
		}
	});

	// === Проверка корректности ссылок на другие страницы сайта (для основного и внутренних запросов).
	$app->behind(function(Request $request, Response $response) use ($app, &$usedURI) {
		$host = preg_quote($request->getHost(), '}');
		$goodContentType = (strncmp($response->headers->get('Content-Type'), 'text/html', 9) === 0);
		$ignore = ($response->headers->get(Application::HEADER_EXPERIMENTAL) != false);

		if (!preg_match('{^(GET_|admin-|_)}', $request->get('_route')) && $goodContentType && !$ignore)
		{
			$service = $app->getServiceRoutes();
			$rootPath = $app->getOption('path.root');
			preg_match_all("{href=('|\")(?:https?://$host)?(/.*?)\\1}", $response->getContent(), $matches, PREG_SET_ORDER);

			foreach ($matches as $match)
			{
				$uri = ($pos = strpos($match[2], 'index.php')) ? substr($match[2], $pos + 9) : $match[2];

				if (isset($usedURI[$uri]))
				{
					continue;
				}

				$path = $rootPath.explode('?', $uri, 2)[0];

				if ($usedURI[$uri] = (file_exists($path) || $service->getByFileName($uri)))
				{
					continue;
				}

				try
				{
					$parameters = $app["url_matcher"]->match($uri);
					$route = $parameters['_route'];
					unset($parameters['_route'], $parameters['_controller']);

					$entity = $service->getByRouteAndQuery($route, $parameters);
					$entity->setCompileFlag(2);
					$entity->setTitle('~ требуется предпросмотр ~');
					$service->commit($entity);
				}
				catch (ResourceNotFoundException $exception)
				{
					// Ignore it! Generated in $app["url_matcher"]->match($uri).
				}
			}
		}
	});

	// === Фиксирование используемого маршрута и его параметров при предпросмотре страниц сайта.
	$app->after(function(Request $request, Response $response) use ($app, &$lastRouteId) {
		$route = $request->get('_route');
		$ignore = ((bool)$request->query->get('compiled') || $response->headers->get(Application::HEADER_EXPERIMENTAL));
		$type = $response->headers->get('Content-Type');

		if ($request->isMethod('GET') && !preg_match('{^(GET_|admin-|_)}', $route) && strncmp($type, 'image/', 6))
		{
			if ($app->isGranted('ROLE_EDITOR') || $app->isGranted('ROLE_CLIENT') || !strncmp($route, 'users-', 6))
			{
				$title = preg_match('{<title>(.*?)</title>}', $response->getContent(), $match) ? $match[1] : null;
				$parameters = array_filter($request->attributes->all(), 'is_scalar');
				unset($parameters['_route'], $parameters['_controller']);

				$service = $app->getServiceRoutes();
				$entity = $service->getByRouteAndQuery($route, $parameters);

				$entity->setTitle(htmlspecialchars_decode($title));
				$entity->setCompileFlag(true);
				$entity->setTags(['предпросмотр']);

				if ($file = $response->headers->get(Application::HEADER_CACHE_FILE))
				{
					$entity->setFile($file);
				}

				if ($tags = $response->headers->get(Application::HEADER_CACHE_TAGS))
				{
					$entity->addTags(explode(',', $tags));
				}

				$ignore || $service->commit($entity);
				$app->isGranted('ROLE_EDITOR') && $lastRouteId = $entity->getId();
			}
		}
	});

	// === Вывод административной панели для HTML страниц сайта при их предпросмотре.
	$app->after(function(Request $request, Response $response) use ($app, &$lastRouteId) {
		$route = $request->get('_route');
		$contentType = $response->headers->get('Content-Type');
		$back = rtrim(preg_replace('{\\?(compiled=Y)?|$}', '?compiled=Y&', $request->getRequestUri(), 1), '&');
		$flag = $request->isMethod('GET') && !$response->headers->has(Application::HEADER_WITHOUT_BAR);

		if (strncmp($route, 'admin-', 6) !== 0 && $flag && strncmp($contentType, 'text/html', 9) === 0)
		{
			$url1 = $app->url('admin-compile-list');
			$url2 = $lastRouteId ? $app->url('admin-compile', ['back' => $back, 'id' => $lastRouteId]) : 0;
			$url3 = $app->getServiceRoutes()->getUnwatchedHtmlUrl($app, $lastRouteId);

			$bar = '$0<div class="b-admin-panel" style="position:fixed;top:0;width:100%;background:#eee;text-align:right;z-index:9999;">';
			$bar.= '<div style="border:solid 1px;border-color:#fff gray gray #fff;font-size:12px;line-height:30px;font-family:sans-serif;">';
			$bar.= '<div style="float:left;color:gray;padding:0 8px;">ПРЕДВАРИТЕЛЬНЫЙ ПРОСМОТР</div>';
			$bar.= '<a style="padding-right:16px;" href="'.htmlspecialchars($url1).'">Административная панель</a>';
			$bar.= $url3 ? '<a style="padding-right:16px;" href="'.htmlspecialchars($url3).'">Следующая страница</a>' : '';
			$bar.= $url2 ? '<a style="padding-right:16px;" href="'.htmlspecialchars($url2).'">Скомпилировать</a>' : '';
			$bar.= '</div></div>';

			$response->setContent(preg_replace('{<body[^>]*>}', $bar, $response->getContent(), 1));
		}

		$lastRouteId = null;
	});

	// === Запоминаем файл сформированного изображения.
	$app->after(function(Request $request, Response $response) use ($app) {
		$flag = $request->query->has('remember') ? (int)$request->query->get('remember') : true;

		if ($flag && $request->get('_route') == 'admin-image' && $response->getStatusCode() == 200)
		{
			$uri = substr($request->getRequestUri(), (strpos($request->getRequestUri(), 'index.php') ?: -9) + 9);
			$uri = substr($uri, 0, strpos($uri, '?') ?: strlen($uri));
			$path = $app->getOption('path.root').strtr($uri, '/', DIRECTORY_SEPARATOR);

			file_exists(dirname($path)) || @mkdir(dirname($path), 0755, true);
			file_put_contents($tempImage = tempnam(dirname($path), 'img'), $response->getContent());
			rename($tempImage, $path) && @chmod($path, 0644);

			$app->getServiceRoutes()->deleteByFileName($uri);
		}
	});

	// === Обработка запрета доступа.
	$app->error(function(AccessDeniedHttpException $exception) use ($app) {
		($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
		$request = Request::createFromGlobals();
		$uri = preg_replace('{/[^/]+/?$}', '', explode('?', explode('#', $request->getRequestUri())[0])[0], 1);

		$app->getServiceFlash()->error('Доступ был запрещён');
		return $app->redirect($uri);
	});
});
