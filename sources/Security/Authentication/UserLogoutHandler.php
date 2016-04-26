<?php
/**
 * Class UserLogoutHandler
 */
namespace Moro\Platform\Security\Authentication;
use \Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class UserLogoutHandler
 * @package Moro\Platform\Security\Authentication
 */
class UserLogoutHandler implements LogoutHandlerInterface
{
	/**
	 * This method is called by the LogoutListener when a user has requested
	 * to be logged out. Usually, you would unset session variables, or remove
	 * cookies, etc.
	 *
	 * @param Request        $request
	 * @param Response       $response
	 * @param TokenInterface $token
	 */
	public function logout(Request $request, Response $response, TokenInterface $token)
	{
		setcookie('REMEMBERME', 'off', time(), '/', null, true);
	}
}