<?php
/**
 * Class IndexImagesAction
 */
namespace Moro\Platform\Action\Images;
use \Moro\Platform\Action\AbstractIndexAction;
use \Moro\Platform\Application;

/**
 * Class IndexImagesAction
 * @package Action
 *
 * @method \Moro\Platform\Model\Implementation\File\ServiceFile getService();
 */
class IndexImagesAction extends AbstractIndexAction
{
	public $title          = 'Графические материалы сайта';
	public $serviceCode    = Application::SERVICE_FILE;
	public $template       = '@PlatformCMS/admin/content/image-list.html.twig';
	public $route          = 'admin-content-images';
	public $routeUpdate    = 'admin-content-images-update';
	public $routeDelete    = 'admin-content-images-delete';
	public $routeBindTags  = 'admin-content-images-set-tag';
	public $routeWatermark = 'admin-content-images-watermark';
	public $routeMask      = 'admin-content-images-mask';

	/**
	 * @var array  Базовые условия фильтрации данных.
	 */
	public $where = ['kind' => '1x1'];

	/**
	 * @return array
	 */
	protected function _getViewParameters()
	{
		$back = $this->getRequest()->getRequestUri();

		return array_merge(parent::_getViewParameters(), [
			'upload' => $this->getService()->createAdminUploadsForm($this->_application, $back, $this->_tags)->createView(),
		]);
	}

	/**
	 * @param array $list
	 * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
	 */
	protected function _doAction($list)
	{
		$app = $this->getApplication();
		$form = $this->getForm();

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->has('show_watermark') && $form->get('show_watermark')->isClicked())
		{
			if (count($list))
			{
				return $app->redirect($app->url($this->routeWatermark, ['ids' => implode(',', $list), 'flag' => 1]));
			}

			$app->getServiceFlash()->alert('Для установки водяного знака нужно выбрать одну или более записей.');
		}

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->has('hide_watermark') && $form->get('hide_watermark')->isClicked())
		{
			if (count($list))
			{
				return $app->redirect($app->url($this->routeWatermark, ['ids' => implode(',', $list), 'flag' => 0]));
			}

			$app->getServiceFlash()->alert('Для удаления водяного знака нужно выбрать одну или более записей.');
		}

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->has('show_mask') && $form->get('show_mask')->isClicked())
		{
			if (count($list))
			{
				return $app->redirect($app->url($this->routeMask, ['ids' => implode(',', $list), 'flag' => 1]));
			}

			$app->getServiceFlash()->alert('Для включения обрамления нужно выбрать одну или более записей.');
		}

		/** @noinspection PhpUndefinedMethodInspection */
		if ($form->has('hide_mask') && $form->get('hide_mask')->isClicked())
		{
			if (count($list))
			{
				return $app->redirect($app->url($this->routeMask, ['ids' => implode(',', $list), 'flag' => 0]));
			}

			$app->getServiceFlash()->alert('Для удаления обрамления нужно выбрать одну или более записей.');
		}

		return parent::_doAction($list);
	}
}