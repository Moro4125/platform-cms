<?php
/**
 * Class AuthInformationAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Application;
use \Moro\Platform\Action\AbstractContentAction;
use \Moro\Platform\Model\Implementation\Users\Auth\UsersAuthInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthInformationAction
 * @package Moro\Platform\Action\Profiles
 *
 * @method \Moro\Platform\Model\Implementation\Users\Auth\ServiceUsersAuth getService()
 */
class AuthInformationAction extends AbstractContentAction
{
	public $serviceCode = Application::SERVICE_USERS_AUTH;
	public $route       = 'admin-users-profiles-auth-info';

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

		if ($entity = $this->getService()->getEntityById($id, true))
		{
			$response = $application->json($parameters = $entity->getProperty(UsersAuthInterface::PROP_PARAMETERS));
			$response->setContent(json_encode($parameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
			return $response;
		}

		return Response::create('', 404);
	}
}