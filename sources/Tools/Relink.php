<?php
/**
 * Class Relink
 */
namespace Moro\Platform\Tools;

/**
 * Class Relink
 */
class Relink
{
	/**
	 * @var array
	 */
	protected $_links;

	/**
	 * @var string
	 */
	protected $_regex;

	/**
	 * @var int  Maximum records in one regular expression.
	 */
	protected $_linksInRegex = 1000;

	/**
	 * @var string  Used in text as <!--relink-block-->...<!--/relink-block-->
	 */
	protected $_blockMarker = 'relink-block';

	/**
	 * @var string  Used in text as <!--no-relink-->...<!--/no-relink-->
	 */
	protected $_skipMarker = 'no-relink';

	/**
	 * @var int
	 */
	protected $_uniqueLinkLimit = 1;

	/**
	 * @var int
	 */
	protected $_totalLinksLimit = 100;

	/**
	 * @var bool
	 */
	protected $_utf8 = true;

	/**
	 * @var array
	 */
	protected $_tagNameEnd = [':' => 1, ' ' => 1, "\r" => 1, "\n" => 1, "\t" => 1, '>' => 1];

	/**
	 * @var array
	 */
	protected $_blackTags = [
		'a' => 1, 'h1' => 1, 'h2' => 1, 'h3' => 1, 'h4' => 1, 'h5' => 1, 'h6' => 1,
		'A' => 1, 'H1' => 1, 'H2' => 1, 'H3' => 1, 'H4' => 1, 'H5' => 1, 'H6' => 1,
	];

	/**
	 * @return array
	 */
	protected function _getLinks()
	{
		return (array)$this->_links;
	}

	/**
	 * @param array $links
	 * @return array
	 */
	protected function _links2tree(array $links)
	{
		$tree = [];

		foreach ($links as $name => $temp)
		{
			$cursor = &$tree;

			for ($length = $this->_utf8 ? mb_strlen($name, 'UTF-8') : strlen($name); $length--;)
			{
				if ($this->_utf8)
				{
					$char = mb_substr($name, 0, 1, 'UTF-8');
					$name = mb_substr($name, 1, $length, 'UTF-8');
				}
				else
				{
					$char = substr($name, 0, 1);
					$name = substr($name, 1, $length);
				}

				if (empty($cursor[$char]))
				{
					$cursor[$char] = [];
				}
				elseif (true === $cursor[$char])
				{
					$cursor[$char] = ['' => $cursor[$char]];
				}

				$cursor = &$cursor[$char];
			}

			$cursor = true;
		}

		return $tree;
	}

	/**
	 * @param array $tree
	 * @return string
	 */
	protected function _tree2regex(array $tree)
	{
		if (1 === count($tree))
		{
			$value = reset($tree);

			return (true === $value)
				? preg_quote(key($tree), '}')
				: preg_quote(key($tree), '}').$this->_tree2regex($value);
		}

		$result = '';
		$strict = true;

		foreach ($tree as $char => $value)
		{
			if ($char === '')
			{
				$strict = false;
				continue;
			}

			$result .= (($result === '') ? '' : '|').preg_quote($char, '}');

			if (true !== $value)
			{
				$result .= $this->_tree2regex($value);
			}
		}

		return '(?>'.$result.($strict ? ')' : ')?');
	}

	/**
	 * @param int $index
	 * @return string
	 */
	protected function _getRegex($index = null)
	{
		if (empty($this->_regex) && $list = $this->_getLinks())
		{
			$noLinkMarker = preg_quote((string)$this->_skipMarker, '}');
			$blockMarker = preg_quote((string)$this->_blockMarker, '}');

			$count = count($list);
			$limit = ceil($count / ceil($count / intval($this->_linksInRegex) ?: $count));

			$prefix = '{';
			$blockMarker  && $prefix .= '(?><!\-\-/'.$blockMarker.'\-\->(?:[^<]*<)+?!\-\-'.$blockMarker.'\-\->)|';
			$noLinkMarker && $prefix .= '(?><!\-\-'.$noLinkMarker.'\-\->(?:[^<]*<)+?!\-\-/'.$noLinkMarker.'\-\->)|';
			$prefix.= '(?><!\-(?:[-][^-]*)+?\-\->)|(?></?[A-Za-z]+[^>]*>)|';
			$suffix = '(?=[\\x00-\\x2C\\x2E\\x2F\\x3A-\\x40\\x5B-\\x60\\x7B-\\x7E])}'.($this->_utf8 ? 'u' : '');

			for ($i = $u = 0; $i < $count; $i += $limit, $u++)
			{
				$links = array_slice($list, $i, $limit);
				$tree  = $this->_links2tree($links);
				$regex = $this->_tree2regex($tree);

				$this->_regex[$u] = $prefix . str_replace(' ', '\\s+', $regex) . $suffix;
			}
		}

		return isset($this->_regex[(int)$index]) ? $this->_regex[(int)$index] : '';
	}

	/**
	 * @param array|null $links
	 * @return $this
	 */
	public function setLinks(array $links = null)
	{
		$this->_links = $links;
		$this->_regex = null;
		return $this;
	}

	/**
	 * @param int $count
	 * @return $this
	 */
	public function setLinksInRegex($count)
	{
		$this->_linksInRegex = (int)$count;
		$this->_regex = null;
		return $this;
	}

	/**
	 * @param string $marker
	 * @return $this
	 */
	public function setBlockMarker($marker)
	{
		$this->_blockMarker = (string)$marker;
		$this->_regex = null;
		return $this;
	}

	/**
	 * @param string $marker
	 * @return $this
	 */
	public function setSkipMarker($marker)
	{
		$this->_skipMarker = (string)$marker;
		$this->_regex = null;
		return $this;
	}

	/**
	 * @param int|bool $count
	 * @return $this
	 */
	public function setUniqueLinksLimit($count)
	{
		$this->_uniqueLinkLimit = (int)$count;
		return $this;
	}

	/**
	 * @param int|bool $count
	 * @return $this
	 */
	public function setTotalLinksLimit($count)
	{
		$this->_totalLinksLimit = (int)$count;
		return $this;
	}

	/**
	 * @param bool $flag
	 * @return $this
	 */
	public function setUseUTF8($flag)
	{
		$this->_utf8 = (bool)$flag;
		$this->_regex = null;
		$this->_links = null;
		return $this;
	}

	/**
	 * @param array $tags
	 * @return $this
	 */
	public function setBlackTags(array $tags)
	{
		$this->_blackTags = array_fill_keys($tags, 1);
		return $this;
	}

	/**
	 * @param string $html
	 * @return array
	 */
	public function search($html)
	{
		$links = $this->_getLinks();
		$result = [];
		$this->_blockMarker && $html = '<!--/'.$this->_blockMarker.'-->'.$html.'<!--'.$this->_blockMarker.'-->';

		$spaceless = '{\\s+}'.($this->_utf8 ? 'u' : '');

		for ($i = 0; $regex = $this->_getRegex($i); $i++)
		{
			$anchors = 0;
			preg_match_all($regex, $html, $matches, PREG_PATTERN_ORDER);

			foreach ($matches[0] as $text)
			{
				if ($text[0] == '<')
				{
					if ($text[1] == '/')
					{
						for ($u = 2, $tag = ''; empty($this->_tagNameEnd[$text[$u]]); $u++)
						{
							$tag .= $text[$u];
						}

						if (isset($this->_blackTags[$tag]) && $anchors)
						{
							$anchors--;
						}
					}
					else
					{
						for ($u = 1, $tag = ''; empty($this->_tagNameEnd[$text[$u]]); $u++)
						{
							$tag .= $text[$u];
						}

						if (isset($this->_blackTags[$tag]))
						{
							$anchors++;
						}
					}
				}
				elseif ($anchors === 0 && isset($links[$key = preg_replace($spaceless, ' ', $text)]))
				{
					$result[$key] = isset($result[$key]) ? ($result[$key] + 1) : 1;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $html
	 * @return string
	 */
	public function apply($html)
	{
		$links = $this->_getLinks();
		$parts = [];
		$html = $this->_blockMarker ? '<!--/'.$this->_blockMarker.'-->'.$html.'<!--'.$this->_blockMarker.'-->' : $html;

		$found = [];
		$total = $this->_totalLinksLimit ?: -1;
		$spaceless = '{\\s+}'.($this->_utf8 ? 'u' : '');

		$m1c = $this->_blockMarker ? strlen($this->_blockMarker) + 8 : 0;
		$m2c = $this->_skipMarker ? strlen($this->_skipMarker) + 7 : 0;
		$m1t = '<!--/'.$this->_blockMarker.'-->';
		$m2t = '<!--'.$this->_skipMarker.'-->';

		for ($i = 0; $regex = $this->_getRegex($i); $i++)
		{
			$anchors = 0;
			preg_match_all($regex, $html, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);

			foreach ($matches[0] as list($text, $pos))
			{
				if ($text[0] == '<')
				{
					if ($text[1] == '!')
					{
						if ($m1c && strncmp($text, $m1t, $m1c) === 0)
						{
							$parts[$pos] = [$pos, $pos + $m1c, ''];
							$pos += strlen($text) - $m1c + 1;
							$parts[$pos] = [$pos, $pos + $m1c - 1, ''];
						}
						elseif ($m2c && strncmp($text, $m2t, $m2c) === 0)
						{
							$parts[$pos] = [$pos, $pos + $m2c, ''];
							$pos += strlen($text) - $m2c - 1;
							$parts[$pos] = [$pos, $pos + $m2c + 1, ''];
						}
					}
					elseif ($text[1] == '/')
					{
						for ($u = 2, $tag = ''; empty($this->_tagNameEnd[$text[$u]]); $u++)
						{
							$tag .= $text[$u];
						}

						if (isset($this->_blackTags[$tag]) && $anchors)
						{
							$anchors--;
						}
					}
					else
					{
						for ($u = 1, $tag = ''; empty($this->_tagNameEnd[$text[$u]]); $u++)
						{
							$tag .= $text[$u];
						}

						if (isset($this->_blackTags[$tag]))
						{
							$anchors++;
						}
					}
				}
				elseif ($anchors === 0 && isset($links[$key = preg_replace($spaceless, ' ', $text)]))
				{
					$found[$key] = isset($found[$key]) ?( $found[$key] + 1): 1;

					if ($total !== 0 && (!$this->_uniqueLinkLimit || $found[$key] <= $this->_uniqueLinkLimit))
					{
						$parts[$pos] = [$pos, $pos + strlen($text), str_replace('%text%', $key, $links[$key])];
						$total--;
					}
				}
			}
		}

		krsort($parts, SORT_NUMERIC);
		$result = '';
		$pos = strlen($html);

		foreach ($parts as $part)
		{
			$result = $part[2] . substr($html, $part[1], $pos - $part[1]) . $result;
			$pos = $part[0];
		}

		return substr($html, 0, $pos) . $result;
	}
}