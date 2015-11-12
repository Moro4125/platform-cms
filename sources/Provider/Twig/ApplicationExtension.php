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
	protected $_rules = [ 'default' => [
		// A - number of active page, C - count of page.
		// Pages     Active page:      Flags:           Show pages:    Flags:
		// min, max.    From, to.      First, ....      left, right,   ...,  Last.
		array(0, 13,           0,      0,      0,   0,     'A-1',  'C-A',    0,     0),
		array(0,  0,           1,      9,      0,   0,     'A-1', '11-A',    1,     1),
		array(0,  0,          10,  'C-5',      1,   1,         6,      2,    1,     1),
		array(0,  0,       'C-4',      0,      1,   1,  '10-C+A',  'C-A',    0,     0),
	]];

	/**
	 * @var array
	 */
	protected $_rulesMeta = ['default' => [
		'useArrows' => false,
	]];

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
			new Twig_SimpleFilter('hyphenate', [$this, 'filterHyphenate']),
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
	 * @param string $name
	 * @param array $rules
	 * @param null|array $meta
	 * @return $this
	 */
	public function addRules($name, array $rules, array $meta = null)
	{
		$this->_rules[$name] = $rules;
		$this->_rulesMeta[$name] = $meta ?: $this->_rulesMeta['default'];
		return $this;
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
	 * @param null|string $kind
	 * @return array
	 */
	public function doPager($page, $count, $kind = null)
	{
		$C = max(1, (int)$count ?: 1);
		$A = max(1, min($C, (int)$page ?: 1));
		$active = $showFirst = $showPDots = $prevCount = $nextCount = $showNDots = $showLast = $vector = false;
		$result = $this->_rulesMeta[$kind ?: 'default'];

		foreach ($this->_rules[$kind ?: 'default'] as $index => $rule)
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

			if ((empty($rule[0]) || $rule[0] <= $C) && (empty($rule[1]) || $rule[1] >= $C))
			{
				if ((empty($rule[2]) || $rule[2] <= $A) && (empty($rule[3]) || $rule[3] >= $A))
				{
					list($showFirst, $showPDots, $prevCount, $nextCount, $showNDots, $showLast) = array_slice($rule, 4);
					$active = true;
					break;
				}
			}
		}

		return array_merge($result, [
			'active'    => $active ? $A : 0,
			'findPrev'  => ($page ?: 1) - 1,
			'showFirst' => $showFirst,
			'showPDots' => $showPDots,
			'prevCount' => $prevCount,
			'nextCount' => $nextCount,
			'showNDots' => $showNDots,
			'showLast'  => $showLast,
			'findNext'  => ($page < $C) ? $page + 1 : 0,
			'count'     => $C,
		]);
	}

	/**
	 * @var string
	 */
	protected $_texFilePath;

	/**
	 * @var array
	 */
	protected $_hypPatterns;

	/**
	 * @var string
	 */
	protected $_hypAlphabetU = 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';

	/**
	 * @var string
	 */
	protected $_hypAlphabetL = 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя';

	/**
	 * @var array
	 */
	protected $_hypAlphabetU2L;

	/**
	 * @param string $path
	 * @return $this
	 */
	public function setTexFilePath($path)
	{
		$this->_texFilePath = $path;
		return $this;
	}

	/**
	 * Инициализация паттернов расстановки переносов в словах.
	 *
	 * @see http://www.tug.org/docs/liang/
	 *
	 * @param string $patternsFile
	 * @return void
	 */
	public function hyphenateInit($patternsFile)
	{
		preg_match_all('~.~u', $this->_hypAlphabetL, $matchL);
		preg_match_all('~.~u', $this->_hypAlphabetU, $matchU);
		$this->_hypAlphabetU2L = array_combine($matchU[0], $matchL[0]);

		$lines = preg_split('~\\s*[%].*?\\r?\\n|\\r?\\n~', file_get_contents($patternsFile));
		$meta = array();

		while ($line = array_shift($lines))
		{
			list($key, $value) = explode(':', $line, 2);
			$meta[strtolower($key)] = trim($value);
		}

		$encoding = empty($meta['content-encoding'])
			?( (isset($meta['content-type']) && ($pos = strpos($meta['content-type'], 'charset=')))
				? trim(rtrim(substr($meta['content-type'], $pos + 8), ';'))
				: false
			): $meta['content-encoding'];

		strtolower($encoding) == 'utf-8' && $encoding = false;

		foreach ($lines as $line)
		{
			if (empty($line))
			{
				continue;
			}

			$encoding && $line = iconv($encoding, 'utf-8', $line);
			$key = str_replace('~', '', strtr($line, '0123456789', '~~~~~~~~~~'));

			if (strlen($key) == strlen($line))
			{
				$key = ".$key.";
				$line = '.'.strtr($line, '-0', '98').'.';
			}

			preg_match_all('~[0-9]?([^0-9]|$)~u', $line, $match);
			$this->_hypPatterns[$key] = array_map('intval', $match[0]);
		}
	}

	/**
	 * Расстановка переносов в конкретном слове.
	 *
	 * @see http://www.tug.org/docs/liang/
	 *
	 * @param string $word
	 * @return string
	 */
	public function hyphenateWord($word)
	{
		$this->_hypPatterns || $this->hyphenateInit($this->_texFilePath);
		is_array($word) && $word = reset($word);

		$len = preg_match_all('~.~u', ".$word.", $match);
		$max = array_fill(0, $len + 1, 0);
		$chars = $match[0];

		isset($this->_hypAlphabetU2L[$chars[1]]) && $chars[1] = $this->_hypAlphabetU2L[$chars[1]];

		for ($l = --$len - 1; $l >= 0; $l--)
		{
			for ($u = $l, $k = $chars[$u]; $u < $len; $u++, $k .= $chars[$u])
			{
				if (isset($this->_hypPatterns[$k]))
				{
					$i = $l;

					foreach ($this->_hypPatterns[$k] as $v)
					{
						$max[$i++] < $v && $max[$i - 1] = $v;
					}
				}
			}
		}

		$chars = array_slice($match[0], 1, -1, false);
		$result = $chars[0].$chars[1];
		$i = 2;

		foreach (array_slice($max, 3, -3) as $v)
		{
			$result.= $v % 2 ? "\xC2\xAD".$chars[$i++] : $chars[$i++];
		}

		return $result.end($chars);
	}

	/**
	 * Расстановка переносов в тексте.
	 *
	 * @param string $text
	 * @return string
	 */
	public function filterHyphenate($text)
	{
		$pattern = "/(?<![!{$this->_hypAlphabetU}{$this->_hypAlphabetL}])"
			."(?>[{$this->_hypAlphabetU}{$this->_hypAlphabetL}][$this->_hypAlphabetL]{3,})/u";

		return preg_replace_callback($pattern, array($this, 'hyphenateWord'), $text);
	}
}