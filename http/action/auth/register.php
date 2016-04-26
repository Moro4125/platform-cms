<?php
/**
 * Register new user.
 */
use \Moro\Platform\Application;
use \Moro\Platform\Form\Constraints\UniqueField;
use \Moro\Platform\Model\Implementation\Users\UsersInterface;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\Validator\Validator\RecursiveValidator;
use \Symfony\Component\Validator\ConstraintViolationList;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Email;
use \Symfony\Component\Security\Core\Encoder\Pbkdf2PasswordEncoder;

/** @noinspection PhpIncludeInspection */
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

Application::action(function(Application $app, Request $request) {
	$prev = Request::create($request->headers->get('Referer', '/'));
	$back = Request::create($prev->query->get('back') ?: $request->getSchemeAndHttpHost().'/register.html');

	if ($request->getMethod() != 'POST')
	{
		return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
	}

	// Form validation.
	/** @var RecursiveValidator $validator */
	$validator = $app['validator'];
	$errors = new ConstraintViolationList();

	$errors->addAll($validator->validate(
		$request->request->get('name'),
		[
			new NotBlank(['message' => 'Псевдоним не может быть пустым.']),
			new UniqueField([
				'message' => 'Такой псевдоним уже используется.',
				'dbal'    => $app->getServiceDataBase(),
				'table'   => $app->getServiceUsers()->getTableName(),
				'field'   => UsersInterface::PROP_NAME,
			]),
		]
	));

	$errors->addAll($validator->validate(
		$request->request->get('email'),
		[
			new NotBlank(['message' => 'E-mail не может быть пустым.']),
			new Email(['message' => 'Введён некорректный e-mail адрес.']),
			new UniqueField([
				'message' => 'Такой e-mail уже присутствует в системе. Попробуйте сбросить пароль.',
				'dbal'    => $app->getServiceDataBase(),
				'table'   => $app->getServiceUsers()->getTableName(),
				'field'   => UsersInterface::PROP_EMAIL,
			]),
		]
	));

	$errors->addAll($validator->validate(
		$request->request->get('password'),
		[
			new NotBlank(['message' => 'Пароль не должен быть пустым']),
		]
	));

	if (count($errors))
	{
		foreach ($errors as $error)
		{
			$error = strtr($error, [
				'(code '.Email::INVALID_FORMAT_ERROR.')' => '',
				'(code '.Email::MX_CHECK_FAILED_ERROR.')' => '',
				'(code '.Email::HOST_CHECK_FAILED_ERROR.')' => '',
			]);
			$app->getServiceFlash()->error($error);

		}

		return $app->redirect($request->getSchemeAndHttpHost().$prev->getRequestUri());
	}

	// Add new user to DB.
	try
	{
		$app->getServiceDataBase()->beginTransaction();

		$userService = $app->getServiceUsers();
		$authService = $app->getServiceUsersAuth();
		$passEncoder = new Pbkdf2PasswordEncoder();

		$profile = $userService->createEntity($request->request->get('email'), true);
		$profile->setName($request->request->get('name'));
		$parameters = $profile->getParameters();
		$parameters['first_name']  = $request->request->get('first_name', '');
		$parameters['second_name'] = $request->request->get('second_name', '');
		$parameters['patronymic']  = $request->request->get('patronymic', '');
		$parameters['roles']       = ['ROLE_USER'];
		$profile->setParameters($parameters);
		$userService->commit($profile);

		$email = $request->request->get('email');
		$password = $request->request->get('password');
		$credential = $passEncoder->encodePassword($password, $email);

		$authRecord = $authService->createEntity('platform-cms', $email, $credential, true);
		$authRecord->setProperty(UsersAuthInterface::PROP_USER_ID, $profile->getId());
		$parameters = $request->request->all();
		unset($parameters['password'], $parameters['enter']);
		$parameters['ip'] = $request->getClientIps();
		$parameters = array_merge($parameters, array_intersect_key($request->headers->all(), [
			'host' => true,
			'cookie' => true,
			'referer' => true,
			'user-agent' => true,
		]));
		$authRecord->setParameters($parameters);
		$authService->commit($authRecord);

		$app->getServiceDataBase()->commit();
		$app->getServiceFlash()->success('Вы успешно прошли процедуру регистрации.');
		$app->getServiceFlash()->info('Пожалуйста войдите на сайт по e-mail\'у и паролю.');
	}
	catch (Exception $exception)
	{
		$app->getServiceDataBase()->rollBack();
		$app->getServiceFlash()->error($exception->getMessage());
		($sentry = $app->getServiceSentry()) && $sentry->captureException($exception);
	}

	return $app->redirect($request->getSchemeAndHttpHost().$back->getRequestUri());
});