<?php
/**
 * Class FieldsetExtension
 */
namespace Moro\Platform\Form\Type\Extension;
use \Symfony\Component\Form\AbstractTypeExtension;
use \Symfony\Component\Form\Extension\Core\Type\FormType;
use \Symfony\Component\Form\FormView;
use \Symfony\Component\Form\FormInterface;
use \Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FieldsetExtension
 * @package Form\Extension
 */
class FieldsetExtension extends AbstractTypeExtension
{
	/**
	 * @return string
	 */
	public function getExtendedType()
	{
		// расширение будет работать с любым типом полей
		return FormType::class;
	}

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			// По умолчанию группировка не происходит.
			'group' => null,
		));
	}

	/**
	 * @param FormView $view
	 * @param FormInterface $form
	 * @param array $options
	 */
	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$group = $options['group'];

		if (null === $group) {
			return;
		}

		$root = $this->getRootView($view);
		$root->vars['groups'][$group][] = $form->getName();
	}

	/**
	 * @param FormView $view
	 * @return FormView
	 */
	public function getRootView(FormView $view)
	{
		$root = $view->parent;

		while (null === $root) {
			$root = $root->parent;
		}

		return $root;
	}
}