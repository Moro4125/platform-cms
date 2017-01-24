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
			'filter'            => '',
		));
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form, array $options)
	{
		$service = $this->_application->getServiceTags();

		/** @var \Moro\Platform\Model\Implementation\Tags\TagsInterface[] $list */
		$list = [];
		$filter = [
			TagsInterface::PROP_KIND => TagsInterface::KIND_STANDARD,
			'tag' => normalizeTag('Цель: ∅'),
		];

		/** @var \Moro\Platform\Model\Implementation\Tags\TagsInterface $tag */
		foreach ($service->selectEntities(0, 1000, '!updated_at', array_keys($filter), array_values($filter)) as $tag)
		{
			$list[$tag->getCode()] = $tag;
		}

		ksort($list, SORT_STRING);

		if (!empty($options['filter']) && $tempList = $service->selectEntities(0, 1, null, 'tag', $options['filter']))
		{
			/** @var TagsInterface $record */
			$record = reset($tempList);
			$filter['tag'] = $record->getName();
			$topTags = [];

			/** @var \Moro\Platform\Model\Implementation\Tags\TagsInterface $tag */
			foreach ($service->selectEntities(0, 1000, '!id', array_keys($filter), array_values($filter)) as $tag)
			{
				$topTags[$tag->getCode()] = $tag;
			}

			ksort($topTags, SORT_STRING);
			$list = array_merge($topTags, $list);
		}

		$usedTagsGroups = [];

		foreach ($view->vars['value'] as $name)
		{
			if ($position = strpos($name, ':'))
			{
				$usedTagsGroups[substr($name, 0, $position + 1)] = true;
			}
		}

		$lastTags = [];

		foreach ($list as $code => $entity)
		{
			if (($p = strpos($name = $entity->getName(), ':')) && isset($usedTagsGroups[substr($name, 0, $p + 1)]))
			{
				$lastTags[$code] = $entity;
			}
		}

		$list = array_merge(array_diff_key($list, $lastTags), $lastTags);

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