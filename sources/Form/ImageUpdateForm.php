<?php
/**
 * Class ImageUpdateForm
 */
namespace Moro\Platform\Form;
use \Moro\Platform\Form\Type\TagsChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\TextType;
use \Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use \Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use \Symfony\Component\Form\Extension\Core\Type\HiddenType;
use \Symfony\Component\Form\Extension\Core\Type\SubmitType;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints\NotBlank;
use \Symfony\Component\Validator\Constraints\Regex;
use \Moro\Platform\Application;

/**
 * Class ImageUpdateForm
 * @package Form
 */
class ImageUpdateForm extends AbstractContent
{
	/**
	 * @var array
	 */
	protected $_kinds;

	/**
	 * @var bool
	 */
	protected $_useWatermark;

	/**
	 * @var bool
	 */
	protected $_useMask;

	/**
	 * @param array $kinds
	 * @return $this
	 */
	public function setKinds(array $kinds)
	{
		$this->_kinds = $kinds;
		return $this;
	}

	/**
	 * @param bool $flag
	 * @return $this
	 */
	public function setUseWatermarkFlag($flag)
	{
		$this->_useWatermark = (bool)$flag;
		return $this;
	}

	/**
	 * @param bool $flag
	 * @return $this
	 */
	public function setUseMaskFlag($flag)
	{
		$this->_useMask = (bool)$flag;
		return $this;
	}

	/**
	 * @param FormBuilderInterface $builder
	 * @param \Moro\Platform\Model\Implementation\Content\EntityContent[] $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->setMethod('POST');

		$builder->add('name', TextType::class, [
			'label' => 'Название',
			'constraints' => [
				new NotBlank(['message' => 'Необходимо заполнить поле "Название".']),
				new Regex([
					'message' => 'Значение поля "Название" не должно содержать квадратных скобок.',
					'pattern' => '{^[^\[\]]*$}',
				]),
				new Regex([
					'message' => 'Значение поля "Название" не должно содержать двойных пробелов.',
					'pattern' => '{^[^ ]*([ ][^ ]+)*$}',
				]),
			],
			'attr' => [
				'autofocus' => 'autofocus',
			],
		]);

		$builder->add('lead', TextType::class, [
			'label' => 'Описание',
			'required' => false,
			'attr' => ['placeholder' => 'Необязательное текстовое описание изображения'],
		]);

		$builder->add('tags', TagsChoiceType::class, [
			'label'    => 'Ярлыки',
			'filter'   => 'service:'.Application::SERVICE_FILE,
			'multiple' => true,
			'required' => false,
			'choices'  => array_combine($this->_tags, $this->_tags),
		]);

		foreach ($this->_kinds as $kind)
		{
			if ($this->_useWatermark)
			{
				$builder->add('watermark'.$kind, ChoiceType::class, [
					'label' => 'Логотип',
					'choices' => [
						'1' => 'верхний правый угол',
						'2' => 'нижний правый угол',
						'3' => 'нижний левый угол',
						'4' => 'верхний левый угол',
						'0' => 'отсутствует',
					],
					'attr' => ['style' => 'width:100%;'],
				]);
			}

			if ($this->_useMask)
			{
				$builder->add('hide_mask'.$kind, CheckboxType::class, [
					'label' => 'Не накладывать маску',
					'required' => false,
				]);
			}

			$builder->add('copy'.$kind, SubmitType::class, [
				'label' => 'Вырезать область',
				'attr'  => ['title' => 'Вырезать выделенную область и сохранить в качестве нового изображения.'],
			]);
			$builder->add('append'.$kind, SubmitType::class, [
				'label' => 'Добавить поля',
				'attr'  => ['title' => 'Добавить поля так, что бы изображение полностью вписывалось в заданное соотношение сторон. Сохранить в качестве нового изображения.'],
			]);
			$builder->add('crop'.$kind.'_a', CheckboxType::class, [
				'label'    => '',
				'required' => false,
			]);
			$builder->add('crop'.$kind.'_x', HiddenType::class, [
				'label' => 'X',
			]);
			$builder->add('crop'.$kind.'_y', HiddenType::class, [
				'label' => 'Y',
			]);
			$builder->add('crop'.$kind.'_w', HiddenType::class, [
				'label' => 'W',
			]);
			$builder->add('crop'.$kind.'_h', HiddenType::class, [
				'label' => 'H',
			]);
		}


		parent::buildForm($builder, $options);
	}
}