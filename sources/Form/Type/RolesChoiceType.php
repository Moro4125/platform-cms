<?php
/**
 * Class RolesChoiceType
 */
namespace Moro\Platform\Form\Type;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use \Symfony\Component\Form\FormView;
use \Symfony\Component\Form\FormInterface;
use \Symfony\Component\OptionsResolver\OptionsResolver;
use \Moro\Platform\Application;

/**
 * Class RolesChoiceType
 * @package Moro\Platform\Form\Type
 */
class RolesChoiceType extends AbstractType
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
		return 'choice_roles';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParent()
	{
		return ChoiceType::class;
	}

	/**
	 * Configures the options for this type.
	 *
	 * @param OptionsResolver $resolver The resolver for the options.
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$choices = array_merge($this->_application['security.role_hierarchy'], ['ROLE_USER' => 0]);
		$service = $this->_application->getServiceTags();

		foreach ($choices as $role => &$name)
		{
			$name = ($list = $service->selectEntities(0, 1, null, 'tag', strtr($role, ['ROLE_' => 'Role: '])))
				? reset($list)->getName()
				: $role;
		}

		$choices = array_flip($choices);

		$resolver->setDefaults(array(
			'validation_groups' => false,
			'multiple'          => true,
			'choices_as_values' => true,
			'choices'           => $choices,
		));
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$view->vars['placeholder'] = null;
		$view->vars['attr'] = array_merge($view->vars['attr'], [
			'data-placeholder' => 'Группы доступа',
			'data-tags'        => true,
			'style'            => 'width: 100%;',
		]);
	}
}