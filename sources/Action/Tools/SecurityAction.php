<?php
/**
 * Class SecurityAction
 */
namespace Moro\Platform\Action\Tools;
use \Symfony\Component\Security\Core\User\User;
use \Symfony\Component\HttpFoundation\Response;
use \Silex\Application;

/**
 * Class SecurityAction
 * @package Moro\Platform\Action\Tools
 */
class SecurityAction
{
	/**
	 * @param \Moro\Platform\Application|Application $application
	 * @param string $login
	 * @param string $password
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
	public function __invoke(Application $application, $login, $password)
	{
		/** @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
		$encoder = $application['security.encoder_factory']->getEncoder(new User($login, $password));
		return new Response($encoder->encodePassword($password, null));
	}
}