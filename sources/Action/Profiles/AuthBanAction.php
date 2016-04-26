<?php
/**
 * Class AuthBanAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Application;
use \Moro\Platform\Action\AbstractContentAction;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class AuthBanAction
 * @package Moro\Platform\Action\Profiles
 *
 * @method \Moro\Platform\Model\Implementation\Users\Auth\ServiceUsersAuth getService()
 */
class AuthBanAction extends AbstractContentAction
{
	public $serviceCode = Application::SERVICE_USERS_AUTH;
	public $route       = 'admin-users-profiles-auth-ban';

	/**
	 * @param Application $application
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function __invoke(Application $application, Request $request, $id)
	{
		$this->setApplication($application);
		$this->setRequest($request);

		$flags = UsersAuthInterface::FLAG_SYSTEM_CHANGES;

		if ($request->getMethod() !== 'POST' || !$entity = $this->getService()->getEntityById($id, true, $flags))
		{
			return Response::create('', 404);
		}

		/** @var UsersAuthInterface $entity */
		$profile = $application->getServiceUsers()->getEntityById($entity->getUserId());
		$parameters = $profile->getParameters();
		$roles = isset($parameters['roles']) ? $parameters['roles'] : [];

		foreach ($roles as $role)
		{
			if (!$application->isGranted($role))
			{
				throw new AccessDeniedException();
			}
		}

		$entity->setProperty(UsersAuthInterface::PROP_BANNED, $entity->getBanned() ? 0 : 1);
		$this->getService()->commit($entity);

		return Response::create((string)$entity->getBanned(), $entity->getBanned() ? 202 : 200);
	}
}