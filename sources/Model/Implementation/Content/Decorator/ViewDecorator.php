<?php
/**
 * Class ViewDecorator
 */
namespace Moro\Platform\Model\Implementation\Content\Decorator;
use \Moro\Platform\Application;

/**
 * Class ViewDecorator
 * @package Moro\Platform\Model\Implementation\Content\Decorator
 */
class ViewDecorator extends AbstractDecorator
{
	const MD_LINK_MASK_EX = '{(\\[([^\\]^]+)\\])?\\[([^\\]^]+)\\]}';

	const IMG_VIEW_HORIZONTAL = 'horizontal';
	const IMG_VIEW_VERTICAL   = 'vertical';
	const IMG_VIEW_SQUARE     = 'square';

	/**
	 * @var array
	 */
	protected $_imageViews = [
		self::IMG_VIEW_HORIZONTAL => [ 'width' => 154, 'height' => 96 ],
		self::IMG_VIEW_VERTICAL   => [ 'width' => 96,  'height' => 154 ],
		self::IMG_VIEW_SQUARE     => [ 'width' => 96,  'height' => 96 ],
	];

	/**
	 * @return bool
	 */
	public function getIsExternal()
	{
		$parameters = $this->getParameters();

		return !empty($parameters['link']) && strncmp($parameters['link'], '/', 1);
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		$files = $this->_application->getServiceFile();
		$args = $this->getParameters();
		$args['gallery'] = empty($args['gallery']) ? [] : $args['gallery'];
		$flag = (bool)$this->_application['request']->headers->get(Application::HEADER_SURROGATE);
		$text = isset($args['gallery_text']) ? $args['gallery_text'] : '';
		$adds = "\n";

		foreach (empty($args['gallery']) ? [] : $args['gallery'] as $hash)
		{
			if ($file = $files->getByHashAndKind($hash, '1x1', true))
			{
				$args['images'][$file->getHash()] = $file;
				$args['images'][$file->getName()] = $file;
			}
		}

		foreach ($files->selectByKind('a'.$this->getId()) as $file)
		{
			$args['attachment'][$file->getHash()] = $file;
			$args['attachment'][$file->getName()] = $file;
		}

		$text = preg_replace_callback(self::MD_LINK_MASK_EX, function($match) use ($files, $args, $flag, &$adds){
			if (isset($args['images'][$match[3]]))
			{
				$meta = $this->_imageViews[self::IMG_VIEW_HORIZONTAL];

				for ($i = 0; $i <= 1; $i++)
				{
					switch (substr($match[2], $i, 1))
					{
						case '-': $meta = $this->_imageViews[self::IMG_VIEW_HORIZONTAL]; break;
						case '|': $meta = $this->_imageViews[self::IMG_VIEW_VERTICAL]; break;
						case '+': $meta = $this->_imageViews[self::IMG_VIEW_SQUARE]; break;
					}
				}

				/** @var \Moro\Platform\Model\Implementation\File\FileInterface $file */
				$file = $args['images'][$match[3]];
				$hash = $file->getHash();
				$href = $this->_application->url('image', array_merge($meta, ['hash' => $hash]));
				$temp = $file->getParameters();
				$lead = strtr(isset($temp['lead']) ? $temp['lead'] : '', '"', "'");
				$adds.= "\n[".$hash."]: ".$href."\t\"".$lead."\"\t";

				return $match[1].'['.$hash.']';
			}
			elseif (isset($args['attachment'][$match[3]]))
			{
				/** @var \Moro\Platform\Model\Implementation\File\FileInterface $file */
				$file = $args['attachment'][$match[3]];
				$name = $file->getName();
				$href = $this->_application->url('download', ['file' => $file]);
				$adds.= "\n[".$name."]: ".$href.($flag ? "\t\"".$name."\"\t" : '');

				return $match[1].'['.$name.']';
			}

			return $match[0];
		}, $text);

		return $text.$adds;
	}
}