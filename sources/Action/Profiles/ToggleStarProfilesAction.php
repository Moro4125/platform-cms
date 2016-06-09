<?php
/**
 * Class ToggleStarProfilesAction
 */
namespace Moro\Platform\Action\Profiles;
use \Moro\Platform\Action\AbstractToggleStar;
use \Moro\Platform\Application;

/**
 * Class ToggleStarProfilesAction
 * @package Moro\Platform\Action\Profiles
 */
class ToggleStarProfilesAction extends AbstractToggleStar
{
	public $serviceCode = Application::SERVICE_USERS;
}