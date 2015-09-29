<?php
/**
 * Class FormServiceProvider
 */
namespace Moro\Platform\Provider;
use \Silex\Provider\FormServiceProvider as CFormServiceProvider;
use \Silex\Application;
use \Moro\Platform\Form\Type\Extension\FieldsetExtension;
use \Moro\Platform\Form\Type\ImageChoiceType;
use \Moro\Platform\Form\Type\TagsChoiceType;

/**
 * Class FormServiceProvider
 * @package Provider
 */
class FormServiceProvider extends CFormServiceProvider
{
	/**
	 * @param Application|\Moro\Platform\Application $app
	 */
	public function register(Application $app)
	{
		CFormServiceProvider::register($app);

		$app['form.type.extensions'] = $app->extend('form.type.extensions', function($list) use ($app) {
			$list[] = new FieldsetExtension();
			return $list;
		});

		$app['form.types'] = $app->extend('form.types', function($list) use ($app) {
			$list[] = new ImageChoiceType($app);
			$list[] = new TagsChoiceType($app);
			return $list;
		});
	}
}