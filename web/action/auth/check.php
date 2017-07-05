<?php
/**
 * Get current user information (AJAX request).
 *
 * How to use this file in the application:
 *    require_once __DIR__.'/../../../bootstrap.php'; // Connect this file only once.
 *    require __DIR__.'/../../../vendor/moro/platform-cms/web/action/auth/check.php';
 */
use \Moro\Platform\Application;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::action(function(Application $app) {
	$response = [];
	$responseCode = 200;

	try
	{
		$response['flash'] = isset($app['session']) ? $app['session']->getFlashBag()->all() : [];

		if (!($token = $app->getServiceSecurityToken()) || $token->getUsername() == 'anon.' )
		{
			throw new NotFoundHttpException();
		}

		$response['enter'] = array_diff_key($token->getUser()->getAuthEnter()->jsonSerialize(), [
			UsersAuthInterface::PROP_ID         => null,
			UsersAuthInterface::PROP_USER_ID    => null,
			UsersAuthInterface::PROP_CREDENTIAL => null,
			UsersAuthInterface::PROP_PARAMETERS => null,
			UsersAuthInterface::PROP_ORDER_AT   => null,
			UsersAuthInterface::PROP_RESULT     => null,
		]);

		/** @var \Moro\Platform\Model\Implementation\Users\UsersInterface $profile */
		$profile = $token->getUser()->getProfile();
		$response['user'] = $profile;
	}
	catch (NotFoundHttpException $exception)
	{
		$response['error'] = ['message' => 'Пользователь не авторизован', 'code' => 401];
		$responseCode = 202;
	}

	return $app->json($response, $responseCode);
});