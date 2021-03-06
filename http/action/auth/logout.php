<?php
/**
 * Logout.
 *
 * How to use this file in the application:
 *    require_once __DIR__.'/../../../bootstrap.php'; // Connect this file only once.
 *    require __DIR__.'/../../../vendor/moro/platform-cms/http/action/auth/logout.php';
 */
use \Moro\Platform\Application;
use \Symfony\Component\HttpFoundation\Request;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::action(function(Application $app, Request $request) {
	$back = Request::create($request->query->get('back') ?: $request->headers->get('Referer') ?: '/');

	// Logging user out.
	$storage = $app['security.token_storage'];
	$storage->setToken(null);

	// Invalidating the session.
	$request->getSession()->invalidate();

	// Prepare redirect.
	$response = $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());

	// Clearing the cookies.
	$cookieNames = ['PHPSESSID', 'REMEMBERME'];

	foreach ($cookieNames as $cookieName)
	{
		$response->headers->clearCookie($cookieName);
	}

	$response->send();
	exit();
});