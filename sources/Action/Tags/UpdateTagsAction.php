<?php
/**
 * Class UpdateTagsAction
 */
namespace Moro\Platform\Action\Tags;
use \Moro\Platform\Action\AbstractUpdateAction;
use \Moro\Platform\Application;

/**
 * Class UpdateTagsAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\Tags\ServiceTags getService()
 * @method \Moro\Platform\Model\Implementation\Tags\EntityTags getEntity()
 */
class UpdateTagsAction extends AbstractUpdateAction
{
	public $serviceCode = Application::SERVICE_TAGS;
	public $template    = '@PlatformCMS/admin/content/tags-update.html.twig';
	public $route       = 'admin-content-tags-update';
	public $routeIndex  = 'admin-content-tags';
	public $routeDelete = 'admin-content-tags-delete';

	public $useTags = false;

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	public function getForm()
	{
		$request = $this->getRequest();

		if ($request->isMethod('POST') && ($data = $request->get('admin_update')) && isset($data['code']) && isset($data['name']))
		{
			$data['code'] = normalizeTag($data['name']);
			$request->request->set('admin_update', $data);
		}

		return parent::getForm();
	}
}