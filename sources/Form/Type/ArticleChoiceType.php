<?php
/**
 * Class ArticleChoiceType
 */
namespace Moro\Platform\Form\Type;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\ChoiceList\View\ChoiceView;
use \Symfony\Component\Form\FormView;
use \Symfony\Component\Form\FormInterface;
use \Symfony\Component\OptionsResolver\OptionsResolver;
use \Moro\Platform\Model\Implementation\Content\Decorator\AjaxSelectDecorator;
use \Moro\Platform\Model\Implementation\Content\ServiceContent;
use \Moro\Platform\Application;

/**
 * Class ArticleChoiceType
 * @package Form\Extension
 */
class ArticleChoiceType extends AbstractType
{
	/**
	 * @var \Moro\Platform\Application
	 */
	protected $_application;

	/**
	 * @param \Moro\Platform\Application $application
	 */
	public function __construct(Application $application)
	{
		$this->_application = $application;
	}

	/**
	 * Returns the name of this type.
	 *
	 * @return string The name of this type
	 */
	public function getName()
	{
		return 'choice_article';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParent()
	{
		return 'choice';
	}

	/**
	 * Configures the options for this type.
	 *
	 * @param OptionsResolver $resolver The resolver for the options.
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$iterator = new \RecursiveArrayIterator($_POST);
		$iterator = new \RecursiveIteratorIterator($iterator);
		$iterator = new \RegexIterator($iterator, '{^\\d+$}');

		$choices = iterator_to_array($iterator);
		$choices = $this->_application->getServiceContent()->filterIdList($choices);
		$choices = array_combine($choices, $choices);

		$resolver->setDefaults(array(
			'validation_groups' => false,
			'choices' => $choices,
		));
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$service = $this->_application->getServiceContent();
		$decorator = new AjaxSelectDecorator($this->_application);

		$list = $service->with($decorator, function(ServiceContent $service) use ($view)
		{
			$result = [];

			foreach ((array)$view->vars['data'] as $id)
			{
				if ($entity = $service->getEntityById($id, true))
				{
					$result[$id] = json_decode(json_encode($entity), true);
				}
			}

			return $result;
		});

		$view->vars['choices'] = [];
		$view->vars['value']   = [];

		foreach (array_keys($list) as $id)
		{
			$view->vars['choices'][] = new ChoiceView($id, $id, '');
			$view->vars['value'][] = $id;
		}

		if (empty($view->vars['multiple']))
		{
			$view->vars['value'] = reset($view->vars['value']);
		}

		$view->vars['placeholder'] = null;
		$view->vars['attr'] = array_merge($view->vars['attr'], [
			'data-ajax--dataType' => 'json',
			'data-ajax--url'      => $this->_application->url('admin-content-articles-select'),
			'data-template'       => 'templateSelect2Article',
			'data-json'           => json_encode($list, JSON_UNESCAPED_UNICODE),
			'style'               => 'width: 100%;',
		]);
	}
}