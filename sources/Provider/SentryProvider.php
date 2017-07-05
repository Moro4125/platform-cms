<?php
/**
 * Class SentryProvider
 */
namespace Moro\Platform\Provider;
use \Symfony\Component\Security\Core\Role\RoleInterface;
use \Symfony\Component\HttpKernel\KernelEvents;
use \Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use \Silex\Application;
use \Pimple\ServiceProviderInterface;
use \Pimple\Container;
use \Raven_Client;
use \Exception;

/**
 * Class SentryProvider
 * @package Provider
 */
class SentryProvider implements ServiceProviderInterface
{
	/**
	 * @var array
	 */
	protected $_controllersStack = [];

	/**
	 * Registers services on the given app.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param \Moro\Platform\Application|Container $app
	 */
	public function register(Container $app)
	{
		$app['sentry'] = function() use ($app) {
			$options = $app->getOptions('sentry');
			$dsn = isset($options['dsn']) ? $options['dsn'] : null;
			unset($options['dsn'], $options['active']);

			if (isset($options['exclude']) && isset($options['include']))
			{
				$options['exclude'] = array_diff($options['exclude'], $options['include']);
				unset($options['include']);
			}

			if ($securityToken = $app->getServiceSecurityToken())
			{
				$name  = $securityToken->getUsername();
				$roles = $app->getServiceSecurityToken()->getRoles();
				$roles = array_map(function(RoleInterface $role) { return $role->getRole(); }, $roles);
			}

			$options['release'] = $app->getVersion();

			$client = $dsn ? new Raven_Client($dsn, $options) : new Raven_Client($options);
			$client->user_context([
				'username' => isset($name) ? $name : '~unknown~',
				'roles' => isset($roles) ? implode(', ', $roles) : '~unknown~',
			]);

			return $client;
		};

		$app->on(KernelEvents::CONTROLLER, function(FilterControllerEvent $event) use ($app) {
			$controller = $event->getRequest()->attributes->get('_route');
			$app->getServiceSentry()->tags_context(['controller' => $controller]);
			array_push($this->_controllersStack, $controller);
		});

		$app->error(function(Exception $exception) use ($app) {
			$options = $exception->getPrevious() ? ['stacktrace' => []] : null;
			$app->getServiceSentry()->captureException($exception, $options);
		}, Application::EARLY_EVENT);

		$app->on(KernelEvents::FINISH_REQUEST, function() use ($app) {
			array_pop($this->_controllersStack);
			$app->getServiceSentry()->tags_context(['controller' => end($this->_controllersStack)]);
		});
	}
}