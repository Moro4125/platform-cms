<?php
/**
 * File with middlewares.
 */
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

// ============================================== //
//    Преобразование объектов запроса и ответа    //
// ============================================== //
Application::getInstance(function(Application $app) {
	$lastRouteId = null;

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

		if (strncmp('/admin/', $request->getUri(), 7))
		{
			return;
		}

		if ($response->getStatusCode() == 401 && strncmp($contentType, 'text/html', 9) === 0)
		{
			$filePath = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'resources', 'error401.html']);
			$response->setContent(file_get_contents($filePath));
		}
	});

	// === Применение утилиты автоматической расстановки ссылок в тексте (для основного и внутренних запросов).
	$app->behind(function(Request $request, Response $response) use ($app) {
		$route = $request->get('_route');
		$contentType = $response->headers->get('Content-Type');

		if (!preg_match('{^(GET_|admin-|_)}', $route) && strncmp($contentType, 'image/', 6) !== 0)
		{
			$service = $app->getServiceRelinkTool();
			$content = $response->getContent();

			if ($found = $service->search($content))
			{
				$tags = (array)$response->headers->get('X-Cache-Tags');

				foreach (array_unique(array_intersect_key($app->getServiceRelink()->getIdMap(), $found)) as $id)
				{
					$tags[] = 'link-'.$id;
				}

				$content = $service->apply($content);
				$response->setContent($content);
				$response->headers->set('X-Cache-Tags', implode(',', $tags));
			}
		}
	});

	// === Фиксирование используемого маршрута и его параметров при предпросмотре страниц сайта.
	$app->after(function(Request $request, Response $response) use ($app, &$lastRouteId) {
		$route = $request->get('_route');
		$ignore = (bool)$request->query->get('compiled');
		$type = $response->headers->get('Content-Type');

		if ($app->isGranted('ROLE_EDITOR') && !preg_match('{^(GET_|admin-|_)}', $route) && strncmp($type, 'image/', 6))
		{
			$title = preg_match('{<title>(.*?)</title>}', $response->getContent(), $match) ? $match[1] : null;
			$parameters = array_filter($request->attributes->all(), 'is_scalar');
			unset($parameters['_route'], $parameters['_controller']);

			$service = $app->getServiceRoutes();
			$entity = $service->getByRouteAndQuery($route, $parameters);

			$entity->setTitle(htmlspecialchars_decode($title));
			$entity->setCompileFlag(true);
			$entity->setTags(['предпросмотр']);

			if ($tags = $response->headers->get('X-Cache-Tags'))
			{
				$entity->addTags(explode(',', $tags));
			}

			$ignore || $service->commit($entity);
			$lastRouteId = $entity->getId();
		}
	});

	// === Вывод административной панели для HTML страниц сайта при их предпросмотре.
	$app->after(function(Request $request, Response $response) use ($app, &$lastRouteId) {
		$route = $request->get('_route');
		$contentType = $response->headers->get('Content-Type');
		$back = rtrim(preg_replace('{\\?|$}', '?compiled=Y&', $request->getRequestUri(), 1), '&');

		if (strncmp($route, 'admin-', 6) !== 0 && strncmp($contentType, 'text/html', 9) === 0)
		{
			$url1 = $app->url('admin-compile-list');
			$url2 = $lastRouteId ? $app->url('admin-compile', ['back' => $back, 'id' => $lastRouteId]) : 0;

			$bar = '$0<div class="b-admin-panel" style="position:fixed;top:0;width:100%;background:#eee;text-align:right;z-index:9999;">';
			$bar.= '<div style="border:solid 1px;border-color:#fff gray gray #fff;font-size:12px;line-height:30px;font-family:sans-serif;">';
			$bar.= '<div style="float:left;color:gray;padding:0 8px;">ПРЕДВАРИТЕЛЬНЫЙ ПРОСМОТР</div>';
			$bar.= '<a style="padding-right:16px;" href="'.htmlspecialchars($url1).'">Административная панель</a>';
			$bar.= $url2 ? '<a style="padding-right:16px;" href="'.htmlspecialchars($url2).'">Скомпилировать страницу</a>' : '';
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

			$service = $app->getServiceRoutes();
			$routeEntity = $service->getByFileName($uri);
			$routeEntity && $service->deleteEntityById($routeEntity->getId());
		}
	});
});
