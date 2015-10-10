<?php
/**
 * Class TagsChoiceType
 */
namespace Moro\Platform\Form\Type;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\ChoiceList\View\ChoiceView;
use \Symfony\Component\Form\FormView;
use \Symfony\Component\Form\FormInterface;
use \Symfony\Component\OptionsResolver\OptionsResolver;
use \Moro\Platform\Model\Implementation\Tags\TagsInterface;
use \Moro\Platform\Application;

/**
 * Class TagsChoiceType
 * @package Form\Extension
 */
class TagsChoiceType extends AbstractType
{
	/**
	 * @var Application
	 */
	protected $_application;

	/**
	 * @param Application $application
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
		return 'choice_tags';
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
		$iterator = new \RegexIterator($iterator, '{^.+$}');

		$choices = iterator_to_array($iterator);
		$choices = array_combine($choices, $choices);

		$resolver->setDefaults(array(
			'validation_groups' => false,
			'multiple'          => true,
			'choices'           => $choices,
		));
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$service = $this->_application->getServiceTags();
		$list = $service->selectEntities(0, 100, 'code', TagsInterface::PROP_KIND, TagsInterface::KIND_STANDARD);

		/** @var \Moro\Platform\Model\Implementation\Tags\TagsInterface $entity */
		foreach ($list as $entity)
		{
			if (!in_array($id = $entity->getName(), $view->vars['value']))
			{
				$view->vars['choices'][] = new ChoiceView($id, $id, $id);
			}
		}

		$view->vars['placeholder'] = null;
		$view->vars['attr'] = array_merge($view->vars['attr'], [
			'data-placeholder' => 'Ярлыки',
			'data-tags'        => true,
			'style'            => 'width: 100%;',
		]);
	}
}