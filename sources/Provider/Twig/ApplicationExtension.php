<?php
/**
 * Class ApplicationExtension
 */
namespace Moro\Platform\Provider\Twig;
use \Twig_Extension;
use \Twig_SimpleFilter;
use \Twig_SimpleFunction;

/**
 * Class ApplicationExtension
 * @package Provider\Twig
 */
class ApplicationExtension extends Twig_Extension
{
	/**
	 * @var array
	 */
	protected $_rules = [
		// A - number of active page, C - count of page.
		// Maximum  Active page:      Flags:           Show pages:    Flags:
		//  pages.     From, to.      First, ....      left, right,   ...,  Last.
		array( 13,        0,      0,      0,   0,     'A-1',  'C-A',    0,     0),
		array(  0,        1,      9,      0,   0,     'A-1', '11-A',    1,     1),
		array(  0,       10,  'C-5',      1,   1,         6,      2,    1,     1),
		array(  0,    'C-4',      0,      1,   1,  '10-C+A',  'C-A',    0,     0),
	];

	/**
	 * @return string
	 */
	public function getName()
	{
		return 'application';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getGlobals()
	{
		return array(
			'now' => time(),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFilters()
	{
		return [
			new Twig_SimpleFilter('canonical', [$this, 'filterCanonical']),
			new Twig_SimpleFilter('hard_dash', [$this, 'filterHardDash'], ['is_safe' => ['html']]),
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFunctions()
	{
		return [
			new Twig_SimpleFunction('pager', [$this, 'doPager']),
		];
	}

	/**
	 * @param mixed $url
	 * @return string
	 */
	public function filterCanonical($url)
	{
		$url = (string)$url;

		if ($pos = strpos($url, '?'))
		{
			$url = substr($url, 0, $pos);
		}

		return $url;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function filterHardDash($text)
	{
		return implode(' ', array_map(
			function($chunk)
			{
				if (strpos($chunk, '-'))
				{
					$chunk = '<nobr>'.htmlspecialchars($chunk).'</nobr>';
				}
				else
				{
					$chunk = htmlspecialchars($chunk);
				}

				return $chunk;
			},
			explode(' ', $text))
		);
	}

	/**
	 * @param int $page
	 * @param int $count
	 * @return array
	 */
	public function doPager($page, $count)
	{
		$C = max(1, (int)$count ?: 1);
		$A = max(1, min($C, (int)$page ?: 1));
		$active = $showFirst = $showPDots = $prevCount = $nextCount = $showNDots = $showLast = $vector = false;

		foreach ($this->_rules as $index => $rule)
		{
			foreach ($rule as &$cell)
			{
				if (is_string($cell))
				{
					$value = 0;

					foreach (preg_split('~([-+])~', '+'.$cell, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $chunk)
					{
						switch ($chunk)
						{
							case '-': $vector = -1; break;
							case '+': $vector = 1;  break;
							case 'A': $value += $vector*$A; break;
							case 'C': $value += $vector*$C; break;
							default:  $value += $vector*(int)$chunk;
						}
					}

					$cell = $value;
				}
			}

			if ((empty($rule[0]) || $rule[0] >= $C) && (empty($rule[1]) || $rule[1] <= $A) && (empty($rule[2]) || $rule[2] >= $A))
			{
				list($showFirst, $showPDots, $prevCount, $nextCount, $showNDots, $showLast) = array_slice($rule, 3);
				$active = true;
				break;
			}
		}

		return [
			'active'    => $active ? $A : 0,
			'showFirst' => $showFirst,
			'showPDots' => $showPDots,
			'prevCount' => $prevCount,
			'nextCount' => $nextCount,
			'showNDots' => $showNDots,
			'showLast'  => $showLast,
			'count'     => $C,
		];
	}
}