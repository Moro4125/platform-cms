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
	protected $_ignoreMarker = 'no-relink';

	/**
	 * @var string  Used for create local black list: <!--skip-relink:...-->
	 */
	protected $_skipMarker = 'skip-relink';

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
	 * @var bool  Use block marker
	 */
	protected $_useBlockMarker = true;

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
	 * @var string
	 */
	protected $_contentPrefix = '';

	/**
	 * @return array
	 */
	protected function _getLinks()
	{
		return (array)$this->_links;
	}

	/**
	 * @param array $links  Must be sorted by key.
	 * @return array
	 */
	protected function _links2tree(array $links)
	{
		$tree = [];

		foreach ($links as $name => $temp)
		{
			$cursor = &$tree;

			for ($index = 0, $length = $this->_utf8 ? mb_strlen($name, 'UTF-8') : strlen($name); $length--; $index++)
			{
				if ($this->_utf8)
				{
					$char = mb_substr($name, 0, 1, 'UTF-8');
					$name = mb_substr($name, 1, $length, 'UTF-8');
					$index || $char = mb_strtolower($char, 'UTF-8');
				}
				else
				{
					$char = substr($name, 0, 1);
					$name = substr($name, 1, $length);
					$index || $char = strtolower($char);
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
	 * @param null|int $level
	 * @return string
	 */
	protected function _tree2regex(array $tree, $level = null)
	{
		if (1 === count($tree))
		{
			$value = reset($tree);

			return (true === $value)
				? preg_quote(key($tree), '}')
				: preg_quote(key($tree), '}').$this->_tree2regex($value, $level + 1);
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

			if ($level)
			{
				$char = preg_quote($char, '}');
			}
			else
			{
				$up = $this->_utf8 ? mb_strtoupper($char, 'UTF-8') : strtoupper($char);
				$char = ($up == $char) ? preg_quote($char, '}') : '['.preg_quote($up, '}').preg_quote($char, '}').']';
			}

			$result .= (($result === '') ? '' : '|').$char;

			if (true !== $value)
			{
				$result .= $this->_tree2regex($value, $level + 1);
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
			$noLinkMarker = preg_quote((string)$this->_ignoreMarker, '}');
			$blockMarker = preg_quote((string)$this->_blockMarker, '}');
			$skipMarker = preg_quote((string)$this->_skipMarker, '}');

			$count = count($list);
			$limit = ceil($count / ceil($count / intval($this->_linksInRegex) ?: $count));

			$prefix = '{';
			$skipMarker   && $prefix .= '(?><!\-\-'.$skipMarker.':.+?\-\->)|';
			$blockMarker  && $prefix .= '(?><!\-\-/'.$blockMarker.'\-\->(?:[^<]*<)+?!\-\-'.$blockMarker.'\-\->)|';
			$noLinkMarker && $prefix .= '(?><!\-\-'.$noLinkMarker.'\-\->(?:[^<]*<)+?!\-\-/'.$noLinkMarker.'\-\->)|';
			$prefix.= '(?><!\-(?:[-][^-]*)+?\-\->)|(?></?[A-Za-z]+[^>]*>)|';
			$suffix = '(?=[“»\\x00-\\x2C\\x2E\\x2F\\x3A-\\x40\\x5B-\\x60\\x7B-\\x7E])}'.($this->_utf8 ? 'u' : '');

			for ($u = 0; $links = array_splice($list, 0, $limit); $u++)
			{
				foreach ($links as $key => $temp)
				{
					$offset = 0;

					while ($pos = strpos($key, ' ', $offset))
					{
						$subKey = substr($key, 0, $pos);
						$offset = $pos + 1;

						if (isset($links[$subKey]))
						{
							$list[$subKey] = $links[$subKey];
						}
					}
				}

				$links = array_diff_key($links, $list);

				ksort($links);

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
		$links && krsort($links);
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
	 * @param string $prefix
	 * @return $this
	 */
	public function setContentPrefix($prefix)
	{
		$this->_contentPrefix = (string)$prefix;
		return $this;
	}

	/**
	 * @param bool $flag
	 * @return $this
	 */
	public function setUseBlockMarker($flag)
	{
		if ($this->_useBlockMarker != (bool)$flag)
		{
			$this->_useBlockMarker = (bool)$flag;
			$this->_regex = null;
		}

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
	 * @return string
	 */
	public function getBlockMarker()
	{
		return $this->_blockMarker;
	}

	/**
	 * @param string $marker
	 * @return $this
	 */
	public function setIgnoreMarker($marker)
	{
		$this->_ignoreMarker = (string)$marker;
		$this->_regex = null;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIgnoreMarker()
	{
		return $this->_ignoreMarker;
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
	 * @return string
	 */
	public function getSkipMarker()
	{
		return $this->_skipMarker;
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
		$blackList = [];
		$spaceless = '{\\s+}'.($this->_utf8 ? 'u' : '');

		if ($this->_blockMarker && $this->_useBlockMarker)
		{
			$html = '<!--/'.$this->_blockMarker.'-->'.$html.'<!--'.$this->_blockMarker.'-->';
		}

		$this->_contentPrefix && $html = $this->_contentPrefix.$html;

		$m3t = '<!--'.$this->_skipMarker.':';
		$m3c = $this->_skipMarker ? strlen($this->_skipMarker) + 5 : 0;

		for ($i = 0; $regex = $this->_getRegex($i); $i++)
		{
			$anchors = 0;
			preg_match_all($regex, $html, $matches, PREG_PATTERN_ORDER);

			foreach ($matches[0] as $text)
			{
				if ($text[0] == '<')
				{
					if ($text[1] == '!')
					{
						if ($m3c && strncmp($text, $m3t, $m3c) === 0)
						{
							$words = preg_replace($spaceless, ' ', trim(substr($text, $m3c, -3)));

							if (isset($links[$words]))
							{
								$blackList[$links[$words]] = true;
							}
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
				elseif ($anchors === 0)
				{
					if (!isset($links[$key = $words = preg_replace($spaceless, ' ', $text)]))
					{
						$key = $this->_utf8
							? mb_strtoupper(mb_substr($words, 0, 1, 'UTF-8')).mb_substr($words, 1, null, 'UTF-8')
							: ucfirst($words);

						if (!isset($links[$key]))
						{
							$key = $this->_utf8
								? mb_strtolower(mb_substr($words, 0, 1, 'UTF-8')).mb_substr($words, 1, null, 'UTF-8')
								: ucfirst($words);
						}
					}

					if (empty($links[$key]) || isset($blackList[$links[$key]]))
					{
						continue;
					}

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

		if ($this->_blockMarker && $this->_useBlockMarker)
		{
			$html = '<!--/'.$this->_blockMarker.'-->'.$html.'<!--'.$this->_blockMarker.'-->';
		}

		$this->_contentPrefix && $html = $this->_contentPrefix.$html;

		$found = [];
		$total = $this->_totalLinksLimit ?: -1;
		$spaceless = '{\\s+}'.($this->_utf8 ? 'u' : '');

		$m1c = $this->_blockMarker ? strlen($this->_blockMarker) + 8 : 0;
		$m2c = $this->_ignoreMarker ? strlen($this->_ignoreMarker) + 7 : 0;
		$m3c = $this->_skipMarker ? strlen($this->_skipMarker) + 5 : 0;
		$m1t = '<!--/'.$this->_blockMarker.'-->';
		$m2t = '<!--'.$this->_ignoreMarker.'-->';
		$m3t = '<!--'.$this->_skipMarker.':';

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
						elseif ($m3c && strncmp($text, $m3t, $m3c) === 0)
						{
							$words = preg_replace($spaceless, ' ', trim(substr($text, $m3c, -3)));

							if (isset($links[$words]))
							{
								$parts[$pos] = [$pos, $pos + strlen($text), ''];
								$found[$links[$words]] = $this->_uniqueLinkLimit + 1;
							}
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
				elseif ($anchors === 0)
				{
					if (!isset($links[$key = $words = preg_replace($spaceless, ' ', $text)]))
					{
						$key = $this->_utf8
							? mb_strtoupper(mb_substr($words, 0, 1, 'UTF-8')).mb_substr($words, 1, null, 'UTF-8')
							: ucfirst($words);

						if (!isset($links[$key]))
						{
							$key = $this->_utf8
								? mb_strtolower(mb_substr($words, 0, 1, 'UTF-8')).mb_substr($words, 1, null, 'UTF-8')
								: ucfirst($words);
						}
					}

					if (empty($links[$key]))
					{
						continue;
					}

					$link = $links[$key];
					$found[$link] = empty($found[$link]) ?( $this->_uniqueLinkLimit ? 1 : 0 ): $found[$link] + 1;

					if ($total !== 0 && $found[$link] <= $this->_uniqueLinkLimit)
					{
						$parts[$pos] = [$pos, $pos + strlen($text), str_replace('%text%', $words, $link)];
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