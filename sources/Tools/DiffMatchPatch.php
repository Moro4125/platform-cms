<?php
/**
 * Diff Match and Patch
 *
 * Copyright 2006 Google Inc.
 * http://code.google.com/p/google-diff-match-patch/
 *
 * php port by Tobias Buschor shwups.ch
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Moro\Platform\Tools;
use \Exception;

/**
 * Class containing the diff, match and patch methods.
 *
 * @fileoverview Computes the difference between two texts to create a patch.
 * Applies the patch onto another text, allowing for errors.
 * @author fraser@google.com (Neil Fraser)
 */
class DiffMatchPatch
{
	const DIFF_DELETE = -1;
	const DIFF_INSERT = 1;
	const DIFF_EQUAL  = 0;

	/**
	 * @var int
	 */
	protected $MATCH_MAX_BITS = PHP_INT_SIZE;

	// Defaults.
	// Redefine these in your program to override the defaults.

	// Number of seconds to map a diff before giving up (0 for infinity).
	public $Diff_Timeout = 1.0;

	// Cost of an empty edit operation in terms of edit characters.
	public $Diff_EditCost = 4;

	// The size beyond which the double-ended diff activates.
	// Double-ending is twice as fast, but less accurate.
	public $Diff_DualThreshold = 32;

	// At what point is no match declared (0.0 = perfection, 1.0 = very loose).
	public $Match_Threshold = 0.5;

	// How far to search for a match (0 = exact location, 1000+ = broad match).
	// A match this many characters away from the expected location will add
	// 1.0 to the score (0.0 is a perfect match).
	public $Match_Distance = 1000;

	// When deleting a large block of text (over ~64 characters), how close does
	// the contents have to match the expected contents. (0.0 = perfection,
	// 1.0 = very loose).  Note that Match_Threshold controls how closely the
	// end points of a delete need to match.
	public $Patch_DeleteThreshold = 0.5;

	// Chunk size for context length.
	public $Patch_Margin = 4;

	/**
	 * DiffMatchPatch constructor.
	 */
	public function __construct()
	{
		$this->MATCH_MAX_BITS *= 8;
		mb_internal_encoding('UTF-8');
	}

	//  DIFF FUNCTIONS

	/**
	 * Find the differences between two texts.  Simplifies the problem by stripping
	 * any common prefix or suffix off the texts before diffing.
	 *
	 * @param string $text1 Old string to be diffed.
	 * @param string $text2 New string to be diffed.
	 * @param boolean $checkLines Optional speedup flag.  If present and false,
	 *     then don't run a line-level diff first to identify the changed areas.
	 *     Defaults to true, which does a faster, slightly less optimal diff
	 * @return array <number|string> Array of diff tuples.
	 */
	public function diffMain($text1, $text2, $checkLines = true)
	{
		// Check for equality (speedup)
		if ($text1 === $text2)
		{
			return array(array(self::DIFF_EQUAL, $text1));
		}

		// Trim off common prefix (speedup)
		$commonLength = $this->diffCommonPrefix($text1, $text2);
		$commonPrefix = mb_substr($text1, 0, $commonLength);
		$text1 = mb_substr($text1, $commonLength);
		$text2 = mb_substr($text2, $commonLength);

		// Trim off common suffix (speedup)
		$commonLength = $this->diffCommonSuffix($text1, $text2);
		$commonSuffix = mb_substr($text1, mb_strlen($text1) - $commonLength);
		$text1 = mb_substr($text1, 0, mb_strlen($text1) - $commonLength);
		$text2 = mb_substr($text2, 0, mb_strlen($text2) - $commonLength);

		// Compute the diff on the middle block
		$diffs = $this->diffCompute($text1, $text2, $checkLines);

		// Restore the prefix and suffix
		if ($commonPrefix !== '')
		{
			array_unshift($diffs, array(self::DIFF_EQUAL, $commonPrefix));
		}

		if ($commonSuffix !== '')
		{
			array_push($diffs, array(self::DIFF_EQUAL, $commonSuffix));
		}

		$this->diffCleanupMerge($diffs);
		return $diffs;
	}

	/**
	 * Find the differences between two texts.  Assumes that the texts do not
	 * have any common prefix or suffix.
	 *
	 * @param string $text1 Old string to be diffed.
	 * @param string $text2 New string to be diffed.
	 * @param boolean $checkLines Speedup flag.  If false, then don't run a
	 *     line-level diff first to identify the changed areas.
	 *     If true, then run a faster, slightly less optimal diff
	 * @return array <number|string> Array of diff tuples.
	 * @private
	 */
	protected function diffCompute($text1, $text2, $checkLines)
	{
		if ($text1 === '')
		{
			// Just add some text (speedup)
			return array(array(self::DIFF_INSERT, $text2));
		}

		if ($text2 === '')
		{
			// Just delete some text (speedup)
			return array(array(self::DIFF_DELETE, $text1));
		}

		$flag = mb_strlen($text1) > mb_strlen($text2);
		$longText  = $flag ? $text1 : $text2;
		$shortText = $flag ? $text2 : $text1;
		$i = mb_strpos($longText, $shortText);

		if ($i !== false)
		{
			// Shorter text is inside the longer text (speedup)
			$diffs = array(
				array(self::DIFF_INSERT, mb_substr($longText, 0, $i)),
				array(self::DIFF_EQUAL, $shortText),
				array(self::DIFF_INSERT, mb_substr($longText, $i + mb_strlen($shortText)))
			);

			// Swap insertions for deletions if diff is reversed.
			if ($flag)
			{
				$diffs[0][0] = $diffs[2][0] = self::DIFF_DELETE;
			}

			return $diffs;
		}

		$longText = $shortText = null; // Garbage collect
		unset($longText, $shortText);

		// Check to see if the problem can be split in two.
		if ($hm = $this->diffHalfMatch($text1, $text2))
		{
			// A half-match was found, sort out the return data.
			$text1_a = $hm[0];
			$text1_b = $hm[1];
			$text2_a = $hm[2];
			$text2_b = $hm[3];
			$mid_common = $hm[4];

			// Send both pairs off for separate processing.
			$diffs_a = $this->diffMain($text1_a, $text2_a, $checkLines);
			$diffs_b = $this->diffMain($text1_b, $text2_b, $checkLines);

			// Merge the results.
			return array_merge($diffs_a, array(
				array(self::DIFF_EQUAL, $mid_common),
			), $diffs_b);
		}

		// Perform a real diff.
		if ($checkLines && (mb_strlen($text1) < 100 || mb_strlen($text2) < 100))
		{
			// Too trivial for the overhead.
			$checkLines = false;
		}

		$lineArray = null;

		if ($checkLines)
		{
			// Scan the text on a line-by-line basis first.
			$a = $this->diffLinesToChars($text1, $text2);
			$text1 = $a[0];
			$text2 = $a[1];
			$lineArray = $a[2];
		}

		if (!$diffs = $this->diffMap($text1, $text2))
		{
			// No acceptable result.
			$diffs = array(
				array(self::DIFF_DELETE, $text1),
				array(self::DIFF_INSERT, $text2),
			);
		}

		if ($checkLines)
		{
			// Convert the diff back to original text.
			$this->diffCharsToLines($diffs, $lineArray);
			// Eliminate freak matches (e.g. blank lines)
			$this->diffCleanupSemantic($diffs);

			// Rediff any replacement blocks, this time character-by-character.
			// Add a dummy entry at the end.
			array_push($diffs, array(self::DIFF_EQUAL, ''));

			$pointer = 0;
			$count_delete = 0;
			$count_insert = 0;
			$text_delete = '';
			$text_insert = '';

			while ($pointer < count($diffs))
			{
				switch ($diffs[$pointer][0])
				{
					case self::DIFF_INSERT :
						$count_insert++;
						$text_insert .= $diffs[$pointer][1];
						break;
					case self::DIFF_DELETE :
						$count_delete++;
						$text_delete .= $diffs[$pointer][1];
						break;
					case self::DIFF_EQUAL :
						// Upon reaching an equality, check for prior redundancies.
						if ($count_delete >= 1 && $count_insert >= 1)
						{
							// Delete the offending records and add the merged ones.
							$a = $this->diffMain($text_delete, $text_insert, false);
							array_splice($diffs, $pointer - $count_delete - $count_insert, $count_delete + $count_insert);

							$pointer = $pointer - $count_delete - $count_insert;

							for ($j = count($a) - 1; $j >= 0; $j--)
							{
								array_splice($diffs, $pointer, 0, array($a[$j]));
							}

							$pointer = $pointer + count($a);
						}

						$count_insert = 0;
						$count_delete = 0;
						$text_delete = '';
						$text_insert = '';
						break;
				}

				$pointer++;
			}

			array_pop($diffs); // Remove the dummy entry at the end.
		}

		return $diffs;
	}

	/**
	 * Split two texts into an array of strings.  Reduce the texts to a string of
	 * hashes where each Unicode character represents one line.
	 *
	 * @param string $text1 First string.
	 * @param string $text2 Second string.
	 * @return array <string|Array.<string>> Three element Array, containing the
	 *     encoded text1, the encoded text2 and the array of unique strings.  The
	 *     zeroth element of the array of unique strings is intentionally blank.
	 */
	protected function diffLinesToChars($text1, $text2)
	{
		$lineArray = array(); // e.g. lineArray[4] == 'Hello\n'
		$lineHash = array(); // e.g. lineHash['Hello\n'] == 4

		// '\x00' is a valid character, but various debuggers don't like it.
		// So we'll insert a junk entry to avoid generating a null character.
		$lineArray[0] = '';

		$chars1 = $this->diffLinesToCharsMunge($text1, $lineArray, $lineHash);
		$chars2 = $this->diffLinesToCharsMunge($text2, $lineArray, $lineHash);

		return array($chars1, $chars2, $lineArray);
	}

	/**
	 * Split a text into an array of strings.  Reduce the texts to a string of
	 * hashes where each Unicode character represents one line.
	 * Modifies linearray and linehash through being a closure.
	 *
	 * @param string $text String to encode
	 * @param array $lineArray
	 * @param array $lineHash
	 * @return string Encoded string
	 */
	protected function diffLinesToCharsMunge($text, &$lineArray, &$lineHash)
	{
		// Walk the text, pulling out a mb_substring for each line.
		// text.split('\n') would would temporarily double our memory footprint.
		// Modifying text would create many large strings to garbage collect.
		$lineStart = 0;
		$lineEnd = -1;
		$chars = '';

		// Keeping our own length variable is faster than looking it up.
		$lineArrayLength = count($lineArray);
		$textLength = mb_strlen($text);

		while ($lineEnd < $textLength - 1)
		{
			$lineEnd = mb_strpos($text, "\n", $lineStart);

			if ($lineEnd === false)
			{
				$lineEnd = $textLength - 1;
			}

			$line = mb_substr($text, $lineStart, $lineEnd + 1 - $lineStart);
			$lineStart = $lineEnd + 1;

			if (isset($lineHash[$line]))
			{
				$chars .= mb_convert_encoding('&#' . intval($lineHash[$line]) . ';', 'UTF-8', 'HTML-ENTITIES');
			}
			else
			{
				$chars .= mb_convert_encoding('&#' . intval($lineArrayLength) . ';', 'UTF-8', 'HTML-ENTITIES');
				$lineHash[$line] = $lineArrayLength;
				$lineArray[$lineArrayLength++] = $line;
			}
		}

		return $chars;
	}

	/**
	 * Rehydrate the text in a diff from a string of line hashes to real lines of
	 * text.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 * @param array $lineArray {Array.<string>} Array of unique strings.
	 */
	protected function diffCharsToLines(&$diffs, $lineArray)
	{
		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			$chars = $diffs[$x][1];
			$text = array();

			for ($y = 0, $l2 = mb_strlen($chars); $y < $l2; $y++)
			{
				$v = mb_substr($chars, $y, 1);
				$k = mb_convert_encoding($v, 'UCS-2LE', 'UTF-8');
				$k1 = ord(substr($k, 0, 1));
				$k2 = ord(substr($k, 1, 1));
				$text[$y] = $lineArray[$k2 * 256 + $k1];
			}

			$diffs[$x][1] = implode('', $text);
		}
	}

	/**
	 * Explore the intersection points between the two texts.
	 *
	 * @param string $text1 Old string to be diffed.
	 * @param string $text2 New string to be diffed.
	 * @return array {Array.<Array.<number|string>>?} Array of diff tuples or null if no
	 *     diff available.
	 */
	protected function diffMap($text1, $text2)
	{
		// Don't run for too long.
		$ms_end = microtime(true) + $this->Diff_Timeout;

		// Cache the text lengths to prevent multiple calls.
		$text1length = mb_strlen($text1);
		$text2length = mb_strlen($text2);
		$max_d = $text1length + $text2length - 1;
		$doubleEnd = $this->Diff_DualThreshold * 2 < $max_d;
		$v_map1 = array();
		$v_map2 = array();
		$v1 = array();
		$v2 = array();
		$v1[1] = 0;
		$v2[1] = 0;
		$x = null;
		$y = null;
		$footstep  = null; // Used to track overlapping paths.
		$footsteps = array();
		$done = false;

		// Safari 1.x doesn't have hasOwnProperty
		//?    $hasOwnProperty = !!(footsteps.hasOwnProperty);
		// If the total number of characters is odd, then the front path will collide
		// with the reverse path.
		$front = ($text1length + $text2length) % 2;

		for ($d = 0; $d < $max_d; $d++)
		{
			// Bail out if timeout reached.
			if ($this->Diff_Timeout > 0 && microtime(true) > $ms_end)
			{
				return null; // zzz
			}

			// Walk the front path one step.
			$v_map1[$d] = array();

			for ($k = -$d; $k <= $d; $k += 2)
			{
				if ($k == -$d || $k != $d && $v1[$k - 1] < $v1[$k + 1])
				{
					$x = $v1[$k + 1];
				}
				else
				{
					$x = $v1[$k - 1] + 1;
				}

				$y = $x - $k;

				if ($doubleEnd)
				{
					$footstep = $x . ',' . $y;

					if ($front && isset ($footsteps[$footstep]))
					{
						$done = true;
					}

					if (!$front)
					{
						$footsteps[$footstep] = $d;
					}
				}

				while (!$done && ($x < $text1length) && ($y < $text2length) && (mb_substr($text1, $x, 1) == mb_substr($text2, $y, 1)))
				{
					$x++;
					$y++;

					if ($doubleEnd)
					{
						$footstep = $x . ',' . $y;

						if ($front && isset ($footsteps[$footstep]))
						{
							$done = true;
						}

						if (!$front)
						{
							$footsteps[$footstep] = $d;
						}
					}
				}

				$v1[$k] = $x;
				$v_map1[$d][$x . ',' . $y] = true;

				if ($x == $text1length && $y == $text2length)
				{
					// Reached the end in single-path mode.
					return $this->diffPath1($v_map1, $text1, $text2);
				}
				elseif ($done)
				{
					// Front path ran over reverse path.
					$v_map2 = array_slice($v_map2, 0, $footsteps[$footstep] + 1);
					$a = $this->diffPath1($v_map1, mb_substr($text1, 0, $x), mb_substr($text2, 0, $y));

					return array_merge($a, $this->diffPath2($v_map2, mb_substr($text1, $x), mb_substr($text2, $y)));
				}
			}

			if ($doubleEnd)
			{
				// Walk the reverse path one step.
				$v_map2[$d] = array();

				for ($k = -$d; $k <= $d; $k += 2)
				{
					if ($k == -$d || $k != $d && $v2[$k - 1] < $v2[$k + 1])
					{
						$x = $v2[$k + 1];
					}
					else
					{
						$x = $v2[$k - 1] + 1;
					}

					$y = $x - $k;
					$footstep = ($text1length - $x) . ',' . ($text2length - $y);

					if (!$front && isset ($footsteps[$footstep]))
					{
						$done = true;
					}

					if ($front)
					{
						$footsteps[$footstep] = $d;
					}

					while (!$done && $x < $text1length && $y < $text2length && mb_substr($text1, $text1length - $x - 1, 1) == mb_substr($text2, $text2length - $y - 1, 1))
					{
						$x++;
						$y++;
						$footstep = ($text1length - $x) . ',' . ($text2length - $y);

						if (!$front && isset ($footsteps[$footstep]))
						{
							$done = true;
						}

						if ($front)
						{
							$footsteps[$footstep] = $d;
						}
					}

					$v2[$k] = $x;
					$v_map2[$d][$x . ',' . $y] = true;

					if ($done)
					{
						// Reverse path ran over front path.
						$v_map1 = array_slice($v_map1, 0, $footsteps[$footstep] + 1);
						$a = $this->diffPath1($v_map1, mb_substr($text1, 0, $text1length - $x), mb_substr($text2, 0, $text2length - $y));

						return array_merge($a, $this->diffPath2($v_map2, mb_substr($text1, $text1length - $x), mb_substr($text2, $text2length - $y)));
					}
				}
			}
		}

		// Number of diffs equals number of characters, no commonality at all.
		return null;
	}

	/**
	 * Work from the middle back to the start to determine the path.
	 *
	 * @param array $v_map {Array.<Object>} Array of paths.ers
	 * @param string $text1 Old string fragment to be diffed.
	 * @param string $text2 New string fragment to be diffed.
	 * @return array {Array.<Array.<number|string>>} Array of diff tuples.
	 */
	protected function diffPath1($v_map, $text1, $text2)
	{
		$path = array();
		$x = mb_strlen($text1);
		$y = mb_strlen($text2);
		/** @type {number?} */
		$last_op = null;

		for ($d = count($v_map) - 2; $d >= 0; $d--)
		{
			while (TRUE)
			{
				if (isset ($v_map[$d][($x - 1) . ',' . $y]))
				{
					$x--;

					if ($last_op === self::DIFF_DELETE)
					{
						$path[0][1] = mb_substr($text1, $x, 1) . $path[0][1];
					}
					else
					{
						array_unshift($path, array(self::DIFF_DELETE, mb_substr($text1, $x, 1)));
					}

					$last_op = self::DIFF_DELETE;
					break;
				}
				elseif (isset ($v_map[$d][$x . ',' . ($y - 1)]))
				{
					$y--;

					if ($last_op === self::DIFF_INSERT)
					{
						$path[0][1] = mb_substr($text2, $y, 1) . $path[0][1];
					}
					else
					{
						array_unshift($path, array(self::DIFF_INSERT, mb_substr($text2, $y, 1)));
					}

					$last_op = self::DIFF_INSERT;
					break;
				}
				else
				{
					$x--;
					$y--;

					if ($last_op === self::DIFF_EQUAL)
					{
						$path[0][1] = mb_substr($text1, $x, 1) . $path[0][1];
					}
					else
					{
						array_unshift($path, array(self::DIFF_EQUAL, mb_substr($text1, $x, 1)));
					}

					$last_op = self::DIFF_EQUAL;
				}
			}
		}

		return $path;
	}

	/**
	 * Work from the middle back to the end to determine the path.
	 *
	 * @param array $v_map {Array.<Object>} Array of paths.
	 * @param string $text1 Old string fragment to be diffed.
	 * @param string $text2 New string fragment to be diffed.
	 * @return array {Array.<Array.<number|string>>} Array of diff tuples.
	 */
	protected function diffPath2($v_map, $text1, $text2)
	{
		$path = array();
		$pathLength = 0;
		$x = $length1 = mb_strlen($text1);
		$y = $length2 = mb_strlen($text2);
		/** @type {number?} */
		$last_op = null;

		for ($d = count($v_map) - 2; $d >= 0; $d--)
		{
			while (TRUE)
			{
				if (isset ($v_map[$d][($x - 1) . ',' . $y]))
				{
					$x--;

					if ($last_op === self::DIFF_DELETE)
					{
						$path[$pathLength - 1][1] .= mb_substr($text1, $length1 - $x - 1, 1);
					}
					else
					{
						$path[$pathLength++] = array(self::DIFF_DELETE, mb_substr($text1, $length1 - $x - 1, 1));
					}

					$last_op = self::DIFF_DELETE;
					break;
				}
				elseif (isset ($v_map[$d][$x . ',' . ($y - 1)]))
				{
					$y--;

					if ($last_op === self::DIFF_INSERT)
					{
						$path[$pathLength - 1][1] .= mb_substr($text2, $length2 - $y - 1, 1);
					}
					else
					{
						$path[$pathLength++] = array(self::DIFF_INSERT, mb_substr($text2, $length2 - $y - 1, 1));
					}

					$last_op = self::DIFF_INSERT;
					break;
				}
				else
				{
					$x--;
					$y--;

					if ($last_op === self::DIFF_EQUAL)
					{
						$path[$pathLength - 1][1] .= mb_substr($text1, $length1 - $x - 1, 1);
					}
					else
					{
						$path[$pathLength++] = array(self::DIFF_EQUAL, mb_substr($text1, $length1 - $x - 1, 1));
					}

					$last_op = self::DIFF_EQUAL;
				}
			}
		}

		return $path;
	}

	/**
	 * Determine the common prefix of two strings
	 *
	 * @param string $text1 First string.
	 * @param string $text2 Second string.
	 * @return integer The number of characters common to the start of each string.
	 */
	protected function diffCommonPrefix($text1, $text2)
	{
		for ($i = 0; 1; $i++)
		{
			$t1 = mb_substr($text1, $i, 1);
			$t2 = mb_substr($text2, $i, 1);

			if ($t1 === '' || $t2 === '' || $t1 !== $t2)
			{
				return $i;
			}
		}

		return 0;
	}

	/**
	 * Determine the common suffix of two strings
	 *
	 * @param string $text1 First string.
	 * @param string $text2 Second string.
	 * @return int The number of characters common to the end of each string.
	 */
	protected function diffCommonSuffix($text1, $text2)
	{
		$l = min(mb_strlen($text1), mb_strlen($text2));

		for ($i = -1; $l + $i + 1 > 0; $i--)
		{
			$t1 = mb_substr($text1, $i, 1);
			$t2 = mb_substr($text2, $i, 1);

			if ($t1 !== $t2)
			{
				return -1 * ($i + 1);
			}
		}

		return 0;
	}

	/**
	 * Do the two texts share a mb_substring which is at least half the length of the
	 * longer text?
	 *
	 * @param string $text1 First string.
	 * @param string $text2 Second string.
	 * @return array {Array.<string>?} Five element Array, containing the prefix of
	 *     text1, the suffix of text1, the prefix of text2, the suffix of
	 *     text2 and the common middle.  Or null if there was no match.
	 */
	protected function diffHalfMatch($text1, $text2)
	{
		$flag = mb_strlen($text1) > mb_strlen($text2);
		$longText = $flag ? $text1 : $text2;
		$shortText = $flag ? $text2 : $text1;

		if (mb_strlen($longText) < 10 || mb_strlen($shortText) < 1)
		{
			return null; // Pointless.
		}

		// First check if the second quarter is the seed for a half-match.
		$hm1 = $this->diffHalfMatchI($longText, $shortText, ceil(mb_strlen($longText) / 4));
		// Check again based on the third quarter.
		$hm2 = $this->diffHalfMatchI($longText, $shortText, ceil(mb_strlen($longText) / 2));

		if (!$hm1 && !$hm2)
		{
			return null;
		}
		elseif (!$hm2)
		{
			$hm = $hm1;
		}
		elseif (!$hm1)
		{
			$hm = $hm2;
		}
		else
		{
			// Both matched.  Select the longest.
			$hm = mb_strlen($hm1[4]) > mb_strlen($hm2[4]) ? $hm1 : $hm2;
		}

		// A half-match was found, sort out the return data.
		if ($flag)
		{
			$text1_a = $hm[0];
			$text1_b = $hm[1];
			$text2_a = $hm[2];
			$text2_b = $hm[3];
		}
		else
		{
			$text2_a = $hm[0];
			$text2_b = $hm[1];
			$text1_a = $hm[2];
			$text1_b = $hm[3];
		}

		$mid_common = $hm[4];

		return array($text1_a, $text1_b, $text2_a, $text2_b, $mid_common);
	}

	/**
	 * Does a mb_substring of shorttext exist within longtext such that the mb_substring
	 * is at least half the length of longtext?
	 * Closure, but does not reference any external variables.
	 *
	 * @param string $longText Longer string.
	 * @param string $shortText Shorter string.
	 * @param int $i Start index of quarter length mb_substring within longtext
	 * @return array {Array.<string>?} Five element Array, containing the prefix of
	 *     longtext, the suffix of longtext, the prefix of shorttext, the suffix
	 *     of shorttext and the common middle.  Or null if there was no match.
	 */
	protected function diffHalfMatchI($longText, $shortText, $i)
	{
		// Start with a 1/4 length mb_substring at position i as a seed.
		$seed = mb_substr($longText, $i, floor(mb_strlen($longText) / 4));

		$j = -1;
		$bestCommon = '';
		$bestLongTextA = null;
		$bestLongTextB = null;
		$bestShortTextA = null;
		$bestShortTextB = null;

		while (($j = mb_strpos($shortText, $seed, $j + 1)) !== false)
		{
			$prefixLength = $this->diffCommonPrefix(mb_substr($longText, $i), mb_substr($shortText, $j));
			$suffixLength = $this->diffCommonSuffix(mb_substr($longText, 0, $i), mb_substr($shortText, 0, $j));

			if (mb_strlen($bestCommon) < $suffixLength + $prefixLength)
			{
				$bestCommon = mb_substr($shortText, $j - $suffixLength, $suffixLength) . mb_substr($shortText, $j, $prefixLength);
				$bestLongTextA = mb_substr($longText, 0, $i - $suffixLength);
				$bestLongTextB = mb_substr($longText, $i + $prefixLength);
				$bestShortTextA = mb_substr($shortText, 0, $j - $suffixLength);
				$bestShortTextB = mb_substr($shortText, $j + $prefixLength);
			}
		}

		if (mb_strlen($bestCommon) >= mb_strlen($longText) / 2)
		{
			return array($bestLongTextA, $bestLongTextB, $bestShortTextA, $bestShortTextB, $bestCommon);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Reduce the number of edits by eliminating semantically trivial equalities.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 */
	protected function diffCleanupSemantic(&$diffs)
	{
		$changes = false;
		$equalities = array(); // Stack of indices where equalities are found.
		$equalitiesLength = 0; // Keeping our own length var is faster in JS.
		$lastEquality = null; // Always equal to equalities[equalitiesLength-1][1]
		$pointer = 0; // Index of current position.
		// Number of characters that changed prior to the equality.
		$lengthChanges1 = 0;
		// Number of characters that changed after the equality.
		$lengthChanges2 = 0;

		while ($pointer < count($diffs))
		{
			if ($diffs[$pointer][0] == self::DIFF_EQUAL)
			{
				// equality found
				$equalities[$equalitiesLength++] = $pointer;
				$lengthChanges1 = $lengthChanges2;
				$lengthChanges2 = 0;
				$lastEquality = $diffs[$pointer][1];
			}
			else
			{
				// an insertion or deletion
				$lengthChanges2 += mb_strlen($diffs[$pointer][1]);

				if ($lastEquality !== null && (mb_strlen($lastEquality) <= $lengthChanges1) && (mb_strlen($lastEquality) <= $lengthChanges2))
				{
					// Duplicate record
					array_splice($diffs, $equalities[$equalitiesLength - 1], 0, array(
						array(self::DIFF_DELETE, $lastEquality),
					));

					// Change second copy to insert.
					$diffs[$equalities[$equalitiesLength - 1] + 1][0] = self::DIFF_INSERT;

					// Throw away the equality we just deleted.
					$equalitiesLength--;

					// Throw away the previous equality (it needs to be reevaluated).
					$equalitiesLength--;
					$pointer = $equalitiesLength > 0 ? $equalities[$equalitiesLength - 1] : -1;
					$lengthChanges1 = 0; // Reset the counters.
					$lengthChanges2 = 0;
					$lastEquality = null;
					$changes = true;
				}
			}

			$pointer++;
		}

		if ($changes)
		{
			$this->diffCleanupMerge($diffs);
		}

		$this->diffCleanupSemanticLossless($diffs);
	}

	/**
	 * Look for single edits surrounded on both sides by equalities
	 * which can be shifted sideways to align the edit to a word boundary.
	 * e.g: The c<ins>at c</ins>ame. -> The <ins>cat </ins>came.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 */
	protected function diffCleanupSemanticLossless(&$diffs)
	{
		$pointer = 1;

		// Intentionally ignore the first and last element (don't need checking).
		while ($pointer < count($diffs) - 1)
		{
			if ($diffs[$pointer - 1][0] == self::DIFF_EQUAL && $diffs[$pointer + 1][0] == self::DIFF_EQUAL)
			{
				// This is a single edit surrounded by equalities.
				$equality1 = $diffs[$pointer - 1][1];
				$edit = $diffs[$pointer][1];
				$equality2 = $diffs[$pointer + 1][1];

				// First, shift the edit as far left as possible.
				$commonOffset = $this->diffCommonSuffix($equality1, $edit);

				if ($commonOffset !== '')
				{
					$commonString = mb_substr($edit, mb_strlen($edit) - $commonOffset);
					$equality1 = mb_substr($equality1, 0, mb_strlen($equality1) - $commonOffset);
					$edit = $commonString . mb_substr($edit, 0, mb_strlen($edit) - $commonOffset);
					$equality2 = $commonString . $equality2;
				}

				// Second, step character by character right, looking for the best fit.
				$bestEquality1 = $equality1;
				$bestEdit = $edit;
				$bestEquality2 = $equality2;
				$bestScore = $this->diffCleanupSemanticScore($equality1, $edit) + $this->diffCleanupSemanticScore($edit, $equality2);

				while (mb_strlen($equality2) && ($editChar = mb_substr($edit, 0, 1)) === ($equality2char = mb_substr($equality2[0], 0, 1)))
				{
					$equality1 .= $editChar;
					$edit = mb_substr($edit, 1) . $equality2char;
					$equality2 = mb_substr($equality2, 1);
					$score = $this->diffCleanupSemanticScore($equality1, $edit) + $this->diffCleanupSemanticScore($edit, $equality2);

					// The >= encourages trailing rather than leading whitespace on edits.
					if ($score >= $bestScore)
					{
						$bestScore = $score;
						$bestEquality1 = $equality1;
						$bestEdit = $edit;
						$bestEquality2 = $equality2;
					}
				}

				if ($diffs[$pointer - 1][1] != $bestEquality1)
				{
					// We have an improvement, save it back to the diff.
					if ($bestEquality1)
					{
						$diffs[$pointer - 1][1] = $bestEquality1;
					}
					else
					{
						array_splice($diffs, $pointer - 1, 1);
						$pointer--;
					}

					$diffs[$pointer][1] = $bestEdit;

					if ($bestEquality2)
					{
						$diffs[$pointer + 1][1] = $bestEquality2;
					}
					else
					{
						array_splice($diffs, $pointer + 1, 1);
						$pointer--;
					}
				}
			}

			$pointer++;
		}
	}

	/**
	 * Given two strings, compute a score representing whether the internal
	 * boundary falls on logical boundaries.
	 * Scores range from 5 (best) to 0 (worst).
	 * Closure, makes reference to regex patterns defined above.
	 *
	 * @param string $one First string
	 * @param string $two Second string
	 * @return int The score.
	 */
	protected function diffCleanupSemanticScore($one, $two)
	{
		// Define some regex patterns for matching boundaries.
		$punctuation    = '/[^a-zA-Z0-9]/';
		$whitespace     = '/\s/';
		$linebreak      = '/[\r\n]/';
		$blankLineEnd   = '/\n\r?\n$/';
		$blankLineStart = '/^\r?\n\r?\n/';

		if (!$one || !$two)
		{
			// Edges are the best.
			return 5;
		}

		// Each port of this function behaves slightly differently due to
		// subtle differences in each language's definition of things like
		// 'whitespace'.  Since this function's purpose is largely cosmetic,
		// the choice has been made to use each language's native features
		// rather than force total conformity.
		$score = 0;

		// One point for non-alphanumeric.
		$char = $one[mb_strlen($one) - 1];
		if (preg_match($punctuation, $char) || preg_match($punctuation, $two[0]))
		{
			$score++;

			// Two points for whitespace.
			if (preg_match($whitespace, $char) || preg_match($whitespace, $two[0]))
			{
				$score++;

				// Three points for line breaks.
				if (preg_match($linebreak, $char) || preg_match($linebreak, $two[0]))
				{
					$score++;

					// Four points for blank lines.
					if (preg_match($blankLineEnd, $one) || preg_match($blankLineStart, $two))
					{
						$score++;
					}
				}
			}
		}

		return $score;
	}

	/**
	 * Reduce the number of edits by eliminating operationally trivial equalities.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 */
	protected function diffCleanupEfficiency(&$diffs)
	{
		$changes = false;
		$equalities = array(); // Stack of indices where equalities are found.
		$equalitiesLength = 0; // Keeping our own length var is faster in JS.
		$lastQuality = ''; // Always equal to equalities[equalitiesLength-1][1]
		$pointer = 0; // Index of current position.
		// Is there an insertion operation before the last equality.
		$pre_ins = false;
		// Is there a deletion operation before the last equality.
		$pre_del = false;
		// Is there an insertion operation after the last equality.
		$post_ins = false;
		// Is there a deletion operation after the last equality.
		$post_del = false;

		while ($pointer < count($diffs))
		{
			if ($diffs[$pointer][0] == self::DIFF_EQUAL)
			{
				// equality found
				if (mb_strlen($diffs[$pointer][1]) < $this->Diff_EditCost && ($post_ins || $post_del))
				{
					// Candidate found.
					$equalities[$equalitiesLength++] = $pointer;
					$pre_ins = $post_ins;
					$pre_del = $post_del;
					$lastQuality = $diffs[$pointer][1];
				}
				else
				{
					// Not a candidate, and can never become one.
					$equalitiesLength = 0;
					$lastQuality = '';
				}

				$post_ins = $post_del = false;
			}
			else
			{
				// an insertion or deletion
				if ($diffs[$pointer][0] == self::DIFF_DELETE)
				{
					$post_del = true;
				}
				else
				{
					$post_ins = true;
				}

				/*
				 * Five types to be split:
				 * <ins>A</ins><del>B</del>XY<ins>C</ins><del>D</del>
				 * <ins>A</ins>X<ins>C</ins><del>D</del>
				 * <ins>A</ins><del>B</del>X<ins>C</ins>
				 * <ins>A</del>X<ins>C</ins><del>D</del>
				 * <ins>A</ins><del>B</del>X<del>C</del>
				 */
				if ($lastQuality && (($pre_ins && $pre_del && $post_ins && $post_del) || ((mb_strlen($lastQuality) < $this->Diff_EditCost / 2) && ($pre_ins + $pre_del + $post_ins + $post_del) == 3)))
				{
					// Duplicate record
					array_splice($diffs, $equalities[$equalitiesLength - 1], 0, array(
						array(self::DIFF_DELETE, $lastQuality),
					));

					// Change second copy to insert.
					$diffs[$equalities[$equalitiesLength - 1] + 1][0] = self::DIFF_INSERT;
					$equalitiesLength--; // Throw away the equality we just deleted;
					$lastQuality = '';

					if ($pre_ins && $pre_del)
					{
						// No changes made which could affect previous entry, keep going.
						$post_ins = $post_del = true;
						$equalitiesLength = 0;
					}
					else
					{
						$equalitiesLength--; // Throw away the previous equality;
						$pointer = $equalitiesLength > 0 ? $equalities[$equalitiesLength - 1] : -1;
						$post_ins = $post_del = false;
					}

					$changes = true;
				}
			}

			$pointer++;
		}

		if ($changes)
		{
			$this->diffCleanupMerge($diffs);
		}
	}

	/**
	 * Reorder and merge like edit sections.  Merge equalities.
	 * Any edit section can move as long as it doesn't cross an equality.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 */
	protected function diffCleanupMerge(&$diffs)
	{
		array_push($diffs, array(self::DIFF_EQUAL, '')); // Add a dummy entry at the end.
		$pointer = 0;
		$count_delete = 0;
		$count_insert = 0;
		$text_delete = '';
		$text_insert = '';
		$commonLength = null;

		while ($pointer < count($diffs))
		{
			switch ($diffs[$pointer][0])
			{
				case self::DIFF_INSERT :
					$count_insert++;
					$text_insert .= $diffs[$pointer][1];
					$pointer++;
					break;

				case self::DIFF_DELETE :
					$count_delete++;
					$text_delete .= $diffs[$pointer][1];
					$pointer++;
					break;

				case self::DIFF_EQUAL :
					// Upon reaching an equality, check for prior redundancies.
					if ($count_delete !== 0 || $count_insert !== 0)
					{
						if ($count_delete !== 0 && $count_insert !== 0)
						{
							// Factor out any common prefixies.
							$commonLength = $this->diffCommonPrefix($text_insert, $text_delete);

							if ($commonLength !== 0)
							{
								if (($pointer - $count_delete - $count_insert) > 0 && $diffs[$pointer - $count_delete - $count_insert - 1][0] == self::DIFF_EQUAL)
								{
									$diffs[$pointer - $count_delete - $count_insert - 1][1] .= mb_substr($text_insert, 0, $commonLength);
								}
								else
								{
									array_splice($diffs, 0, 0, array(
										array(self::DIFF_EQUAL, mb_substr($text_insert, 0, $commonLength)),
									));
									$pointer++;
								}

								$text_insert = mb_substr($text_insert, $commonLength);
								$text_delete = mb_substr($text_delete, $commonLength);
							}

							// Factor out any common suffixies.
							$commonLength = $this->diffCommonSuffix($text_insert, $text_delete);

							if ($commonLength !== 0)
							{
								$diffs[$pointer][1] = mb_substr($text_insert, mb_strlen($text_insert) - $commonLength) . $diffs[$pointer][1];
								$text_insert = mb_substr($text_insert, 0, mb_strlen($text_insert) - $commonLength);
								$text_delete = mb_substr($text_delete, 0, mb_strlen($text_delete) - $commonLength);
							}
						}

						// Delete the offending records and add the merged ones.
						if ($count_delete === 0)
						{
							array_splice($diffs, $pointer - $count_delete - $count_insert, $count_delete + $count_insert, array(
								array(self::DIFF_INSERT, $text_insert),
							));
						}
						elseif ($count_insert === 0)
						{
							array_splice($diffs, $pointer - $count_delete - $count_insert, $count_delete + $count_insert, array(
								array(self::DIFF_DELETE, $text_delete),
							));
						}
						else
						{
							array_splice($diffs, $pointer - $count_delete - $count_insert, $count_delete + $count_insert, array(
								array(self::DIFF_DELETE, $text_delete),
								array(self::DIFF_INSERT, $text_insert),
							));
						}

						$pointer = $pointer - $count_delete - $count_insert + ($count_delete ? 1 : 0) + ($count_insert ? 1 : 0) + 1;
					}
					elseif ($pointer !== 0 && $diffs[$pointer - 1][0] == self::DIFF_EQUAL)
					{
						// Merge this equality with the previous one.
						$diffs[$pointer - 1][1] .= $diffs[$pointer][1];
						array_splice($diffs, $pointer, 1);
					}
					else
					{
						$pointer++;
					}

					$count_insert = 0;
					$count_delete = 0;
					$text_delete = '';
					$text_insert = '';
					break;
			}
		}

		if ($diffs[count($diffs) - 1][1] === '')
		{
			array_pop($diffs); // Remove the dummy entry at the end.
		}

		// Second pass: look for single edits surrounded on both sides by equalities
		// which can be shifted sideways to eliminate an equality.
		// e.g: A<ins>BA</ins>C -> <ins>AB</ins>AC
		$changes = false;
		$pointer = 1;

		// Intentionally ignore the first and last element (don't need checking).
		while ($pointer < count($diffs) - 1)
		{
			if ($diffs[$pointer - 1][0] == self::DIFF_EQUAL && $diffs[$pointer + 1][0] == self::DIFF_EQUAL)
			{
				// This is a single edit surrounded by equalities.
				if (mb_substr($diffs[$pointer][1], mb_strlen($diffs[$pointer][1]) - mb_strlen($diffs[$pointer - 1][1])) == $diffs[$pointer - 1][1])
				{
					// Shift the edit over the previous equality.
					$diffs[$pointer][1] = $diffs[$pointer - 1][1] . mb_substr($diffs[$pointer][1], 0, mb_strlen($diffs[$pointer][1]) - mb_strlen($diffs[$pointer - 1][1]));
					$diffs[$pointer + 1][1] = $diffs[$pointer - 1][1] . $diffs[$pointer + 1][1];
					array_splice($diffs, $pointer - 1, 1);
					$changes = true;
				}
				elseif (mb_substr($diffs[$pointer][1], 0, mb_strlen($diffs[$pointer + 1][1])) == $diffs[$pointer + 1][1])
				{
					// Shift the edit over the next equality.
					$diffs[$pointer - 1][1] .= $diffs[$pointer + 1][1];

					$diffs[$pointer][1] = mb_substr($diffs[$pointer][1], mb_strlen($diffs[$pointer + 1][1])) . $diffs[$pointer + 1][1];
					array_splice($diffs, $pointer + 1, 1);
					$changes = true;
				}
			}

			$pointer++;
		}

		// If shifts were made, the diff needs reordering and another shift sweep.
		if ($changes)
		{
			$this->diffCleanupMerge($diffs);
		}
	}

	/**
	 * loc is a location in text1, compute and return the equivalent location in
	 * text2.
	 * e.g. 'The cat' vs 'The big cat', 1->1, 5->8
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 * @param int $loc Location within text1.
	 * @return int Location within text2.
	 */
	public function diffXIndex($diffs, $loc)
	{
		$chars1 = 0;
		$chars2 = 0;
		$lastChars1 = 0;
		$lastChars2 = 0;

		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			if ($diffs[$x][0] !== self::DIFF_INSERT)
			{ // Equality or deletion.
				$chars1 += mb_strlen($diffs[$x][1]);
			}

			if ($diffs[$x][0] !== self::DIFF_DELETE)
			{ // Equality or insertion.
				$chars2 += mb_strlen($diffs[$x][1]);
			}

			if ($chars1 > $loc)
			{ // Overshot the location.
				break;
			}

			$lastChars1 = $chars1;
			$lastChars2 = $chars2;
		}

		// Was the location was deleted?
		if (count($diffs) != $x && $diffs[$x][0] === self::DIFF_DELETE)
		{
			return $lastChars2;
		}

		// Add the remaining character length.
		return $lastChars2 + ($loc - $lastChars1);
	}

	/**
	 * Convert a diff array into a pretty HTML report.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 * @return string HTML representation.
	 */
	public function diffPrettyHtml($diffs)
	{
		$html = array();
		$i = 0;

		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			$op = $diffs[$x][0]; // Operation (insert, delete, equal)
			$data = $diffs[$x][1]; // Text of change.
			$text = preg_replace(
				array('/&/',   '/</',  '/>/',  "/\n/"),
				array('&amp;', '&lt;', '&gt;', '&para;<br>'),
				$data
			);

			switch ($op)
			{
				case self::DIFF_INSERT :
					$html[$x] = '<ins style="background:#e6ffe6;">' . $text . '</ins>';
					break;

				case self::DIFF_DELETE :
					$html[$x] = '<del style="background:#ffe6e6;">' . $text . '</del>';
					break;

				case self::DIFF_EQUAL :
					$html[$x] = '<span>' . $text . '</span>';
					break;
			}

			if ($op !== self::DIFF_DELETE)
			{
				$i += mb_strlen($data);
			}
		}

		return implode('', $html);
	}

	/**
	 * Compute and return the source text (all equalities and deletions).
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 * @return string Source text.
	 */
	public function diffText1($diffs)
	{
		$text = array();

		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			if ($diffs[$x][0] !== self::DIFF_INSERT)
			{
				$text[$x] = $diffs[$x][1];
			}
		}

		return implode('', $text);
	}

	/**
	 * Compute and return the destination text (all equalities and insertions).
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 * @return string Destination text.
	 */
	public function diffText2($diffs)
	{
		$text = array();

		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			if ($diffs[$x][0] !== self::DIFF_DELETE)
			{
				$text[$x] = $diffs[$x][1];
			}
		}

		return implode('', $text);
	}

	/**
	 * Compute the Levenshtein distance; the number of inserted, deleted or
	 * substituted characters.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 * @return int Number of changes.
	 */
	public function diffLevenshtein($diffs)
	{
		$levenshtein = 0;
		$insertions = 0;
		$deletions = 0;

		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			$op = $diffs[$x][0];
			$data = $diffs[$x][1];

			switch ($op)
			{
				case self::DIFF_INSERT :
					$insertions += mb_strlen($data);
					break;

				case self::DIFF_DELETE :
					$deletions += mb_strlen($data);
					break;

				case self::DIFF_EQUAL :
					// A deletion and an insertion is one substitution.
					$levenshtein += max($insertions, $deletions);
					$insertions = 0;
					$deletions = 0;
					break;
			}
		}

		$levenshtein += max($insertions, $deletions);

		return $levenshtein;
	}

	/**
	 * Crush the diff into an encoded string which describes the operations
	 * required to transform text1 into text2.
	 * E.g. =3\t-2\t+ing  -> Keep 3 chars, delete 2 chars, insert 'ing'.
	 * Operations are tab-separated.  Inserted text is escaped using %xx notation.
	 *
	 * @param array $diffs {Array.<Array.<number|string>>} Array of diff tuples.
	 * @return string Delta text.
	 */
	public function diffToDelta($diffs)
	{
		$text = array();

		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			switch ($diffs[$x][0])
			{
				case self::DIFF_INSERT :
					$text[$x] = '+' . self::encodeURI($diffs[$x][1]);
					break;

				case self::DIFF_DELETE :
					$text[$x] = '-' . mb_strlen($diffs[$x][1]);
					break;

				case self::DIFF_EQUAL :
					$text[$x] = '=' . mb_strlen($diffs[$x][1]);
					break;
			}
		}

		return str_replace('%20', ' ', implode("\t", $text));
	}

	/**
	 * Given the original text1, and an encoded string which describes the
	 * operations required to transform text1 into text2, compute the full diff.
	 *
	 * @param string $text1 Source string for the diff.
	 * @param string $delta Delta text.
	 * @return array {Array.<Array.<number|string>>} Array of diff tuples.
	 * @throws Exception If invalid input.
	 */
	public function diffFromDelta($text1, $delta)
	{
		$diffs = array();
		$diffsLength = 0; // Keeping our own length var is faster in JS.
		$pointer = 0; // Cursor in text1
		$tokens = preg_split("/\t/", $delta);

		for ($x = 0, $l = count($tokens); $x < $l; $x++)
		{
			// Each token begins with a one character parameter which specifies the
			// operation of this token (delete, insert, equality).
			$param = mb_substr($tokens[$x], 1);

			switch ($tokens[$x][0])
			{
				case '+' :
					try
					{
						$diffs[$diffsLength++] = array(self::DIFF_INSERT, self::decodeURI($param));
					}
					catch (Exception $ex)
					{
						throw new Exception('Illegal escape in diffFromDelta: ' . $param, 0, $ex);
						// Malformed URI sequence.
					}
					break;

				case '-' :
					// Fall through.

				case '=' :
					$n = (int)$param;

					if ($n < 0)
					{
						throw new Exception('Invalid number in diffFromDelta: ' . $param);
					}

					$text = mb_substr($text1, $pointer, $n);
					$pointer += $n;

					if ($tokens[$x][0] == '=')
					{
						$diffs[$diffsLength++] = array(self::DIFF_EQUAL, $text);
					}
					else
					{
						$diffs[$diffsLength++] = array(self::DIFF_DELETE, $text);
					}
					break;

				default :
					// Blank tokens are ok (from a trailing \t).
					// Anything else is an error.
					if ($tokens[$x])
					{
						throw new Exception('Invalid diff operation in diffFromDelta: ' . $tokens[$x]);
					}
			}
		}

		if ($pointer != mb_strlen($text1))
		{
			throw new Exception('Delta length (' . $pointer . ') does not equal source text length (' . mb_strlen($text1) . ').');
		}

		return $diffs;
	}

	//  MATCH FUNCTIONS

	/**
	 * Locate the best instance of 'pattern' in 'text' near 'loc'.
	 *
	 * @param string $text The text to search.
	 * @param string $pattern The pattern to search for.
	 * @param integer $loc The location to search around.
	 * @return int Best match index or -1.
	 */
	public function matchMain($text, $pattern, $loc)
	{
		$loc = max(0, min($loc, mb_strlen($text)));

		if ($text == $pattern)
		{
			// Shortcut (potentially not guaranteed by the algorithm)
			return 0;
		}
		elseif (!mb_strlen($text))
		{
			// Nothing to match.
			return -1;
		}
		elseif (mb_substr($text, $loc, mb_strlen($pattern)) == $pattern)
		{
			// Perfect match at the perfect spot!  (Includes case of null pattern)
			return $loc;
		}
		else
		{
			// Do a fuzzy compare.
			return $this->matchBitAp($text, $pattern, $loc);
		}
	}

	/**
	 * Locate the best instance of 'pattern' in 'text' near 'loc' using the
	 * Bitap algorithm.
	 *
	 * @param string $text The text to search.
	 * @param string $pattern The pattern to search for.
	 * @param integer $loc The location to search around.
	 * @return int Best match index or -1.
	 * @throws Exception
	 */
	protected function matchBitAp($text, $pattern, $loc)
	{
		if (mb_strlen($pattern) > $this->MATCH_MAX_BITS)
		{
			throw new Exception('Pattern too long for this system.');
		}

		// Initialise the alphabet.
		$s = $this->matchAlphabet($pattern);

		// Highest score beyond which we give up.
		$score_threshold = $this->Match_Threshold;

		// Is there a nearby exact match? (speedup)
		$best_loc = mb_strpos($text, $pattern, $loc);

		if ($best_loc !== false)
		{
			$score_threshold = min($this->matchBitApScore(0, $best_loc, $pattern, $loc), $score_threshold);
		}

		// What about in the other direction? (speedup)
		$textLength = mb_strlen($text);
		$patternLength = mb_strlen($pattern);
		$best_loc = mb_strrpos($text, $pattern, min($loc + $patternLength, $textLength));

		if ($best_loc !== false)
		{
			$score_threshold = min($this->matchBitApScore(0, $best_loc, $pattern, $loc), $score_threshold);
		}

		// Initialise the bit arrays.
		$matchMask = 1 << ($patternLength - 1);
		$best_loc = -1;

		$bin_min = null;
		$bin_mid = null;
		$bin_max = $patternLength + $textLength;
		$last_rd = null;

		for ($d = 0, $l = $patternLength; $d < $l; $d++)
		{
			// Scan for the best match; each iteration allows for one more error.
			// Run a binary search to determine how far from 'loc' we can stray at this
			// error level.
			$bin_min = 0;
			$bin_mid = $bin_max;

			while ($bin_min < $bin_mid)
			{
				if ($this->matchBitApScore($d, $loc + $bin_mid, $pattern, $loc) <= $score_threshold)
				{
					$bin_min = $bin_mid;
				}
				else
				{
					$bin_max = $bin_mid;
				}

				$bin_mid = floor(($bin_max - $bin_min) / 2 + $bin_min);
			}

			// Use the result from this iteration as the maximum for the next.
			$bin_max = $bin_mid;
			$start = max(1, $loc - $bin_mid + 1);
			$finish = min($loc + $bin_mid, $textLength) + $patternLength;

			$rd = Array($finish + 2);
			$rd[$finish + 1] = (1 << $d) - 1;

			for ($j = $finish; $j >= $start; $j--)
			{
				// The alphabet (s) is a sparse hash, so the following line generates
				// warnings.
				$c = mb_substr($text, $j - 1, 1);
				$charMatch = isset($s[$c]) ? $s[$c] : null;

				if ($d === 0)
				{ // First pass: exact match.
					$rd[$j] = (($rd[$j + 1] << 1) | 1) & $charMatch;
				}
				else
				{ // Subsequent passes: fuzzy match.
					$rd[$j] = (($rd[$j + 1] << 1) | 1) & $charMatch | ((($last_rd[$j + 1] | $last_rd[$j]) << 1) | 1) | $last_rd[$j + 1];
				}

				if ($rd[$j] & $matchMask)
				{
					$score = $this->matchBitApScore($d, $j - 1, $pattern, $loc);
					// This match will almost certainly be better than any existing match.
					// But check anyway.
					if ($score <= $score_threshold)
					{
						// Told you so.
						$score_threshold = $score;
						$best_loc = $j - 1;
						if ($best_loc > $loc)
						{
							// When passing loc, don't exceed our current distance from loc.
							$start = max(1, 2 * $loc - $best_loc);
						}
						else
						{
							// Already passed loc, downhill from here on in.
							break;
						}
					}
				}
			}

			// No hope for a (better) match at greater error levels.
			if ($this->matchBitApScore($d + 1, $loc, $pattern, $loc) > $score_threshold)
			{
				break;
			}

			$last_rd = $rd;
		}

		return (int)$best_loc;
	}

	/**
	 * Compute and return the score for a match with e errors and x location.
	 * Accesses loc and pattern through being a closure.
	 *
	 * @param integer $e Number of errors in match.
	 * @param integer $x Location of match.
	 * @param string $pattern
	 * @param integer $loc
	 * @return integer Overall score for match (0.0 = good, 1.0 = bad).
	 */
	protected function matchBitApScore($e, $x, $pattern, $loc)
	{
		$accuracy = $e / mb_strlen($pattern);
		$proximity = abs($loc - $x);

		if (!$this->Match_Distance)
		{
			// Dodge divide by zero error.
			return $proximity ? 1.0 : $accuracy;
		}

		return $accuracy + ($proximity / $this->Match_Distance);
	}

	/**
	 * Initialise the alphabet for the BitAp algorithm.
	 *
	 * @param string $pattern The text to encode.
	 * @return array Hash of character locations.
	 */
	protected function matchAlphabet($pattern)
	{
		$s = array();
		$a = preg_split('/(?<!^)(?!$)/u', $pattern);
		$l = count($a);

		for ($i = 0; $i < $l; $i++)
		{
			$s[$a[$i]] = 0;
		}

		for ($i = 0; $i < $l; $i++)
		{
			$s[$a[$i]] |= 1 << ($l - $i - 1);
		}

		return $s;
	}

	//  PATCH FUNCTIONS

	/**
	 * Increase the context until it is unique,
	 * but don't let the pattern expand beyond Match_MaxBits.
	 *
	 * @param PatchObj $patch The patch to grow.
	 * @param string $text Source text.
	 * @private
	 */
	protected function patchAddContext($patch, $text)
	{
		$pattern = mb_substr($text, $patch->start2, $patch->length1);
		$previousPattern = null;
		$padding = 0;

		while ((mb_strlen($pattern) === 0 // Javascript's indexOf/lastIndexOd return 0/strlen respectively if pattern = ''
			|| mb_strpos($text, $pattern) !== mb_strrpos($text, $pattern)) && $pattern !== $previousPattern // avoid infinte loop
			&& mb_strlen($pattern) < $this->MATCH_MAX_BITS - $this->Patch_Margin - $this->Patch_Margin)
		{
			$padding += $this->Patch_Margin;
			$previousPattern = $pattern;
			$pattern = mb_substr($text, max($patch->start2 - $padding, 0), ($patch->start2 + $patch->length1 + $padding) - max($patch->start2 - $padding, 0));
		}

		// Add one chunk for good luck.
		$padding += $this->Patch_Margin;
		// Add the prefix.
		$prefix = mb_substr($text, max($patch->start2 - $padding, 0), $patch->start2 - max($patch->start2 - $padding, 0));

		if ($prefix !== '')
		{
			array_unshift($patch->diffs, array(self::DIFF_EQUAL, $prefix));
		}

		// Add the suffix.
		$suffix = mb_substr($text, $patch->start2 + $patch->length1, ($patch->start2 + $patch->length1 + $padding) - ($patch->start2 + $patch->length1));

		if ($suffix !== '')
		{
			array_push($patch->diffs, array(self::DIFF_EQUAL, $suffix));
		}

		$prefixLength = mb_strlen($prefix);

		// Roll back the start points.
		$patch->start1 -= $prefixLength;
		$patch->start2 -= $prefixLength;

		// Extend the lengths.
		$patch->length1 += $prefixLength + mb_strlen($suffix);
		$patch->length2 += $prefixLength + mb_strlen($suffix);
	}

	/**
	 * Compute a list of patches to turn text1 into text2.
	 * Use diffs if provided, otherwise compute it ourselves.
	 * There are four ways to call this function, depending on what data is
	 * available to the caller:
	 * Method 1:
	 * a = text1, b = text2
	 * Method 2:
	 * a = diffs
	 * Method 3 (optimal):
	 * a = text1, b = diffs
	 * Method 4 (deprecated, use method 3):
	 * a = text1, b = text2, c = diffs
	 *
	 * @param string|array $a {string|Array.<Array.<number|string>>} text1 (methods 1,3,4) or
	 * Array of diff tuples for text1 to text2 (method 2).
	 * @param string|array $opt_b {string|Array.<Array.<number|string>>} text2 (methods 1,4) or
	 * Array of diff tuples for text1 to text2 (method 3) or undefined (method 2).
	 * @param string|array $opt_c {string|Array.<Array.<number|string>>} Array of diff tuples for
	 * text1 to text2 (method 4) or undefined (methods 1,2,3).
	 * @return PatchObj[] {Array.<patch_obj>} Array of patch objects.
	 * Array of patch objects.
	 * @throws Exception
	 */
	public function patchMake($a, $opt_b = null, $opt_c = null)
	{
		if (is_string($a) && is_string($opt_b) && $opt_c === null)
		{
			// Method 1: text1, text2
			// Compute diffs from text1 and text2.
			$text1 = $a;
			$diffs = $this->diffMain($text1, $opt_b, true);

			if (count($diffs) > 2)
			{
				$this->diffCleanupSemantic($diffs);
				$this->diffCleanupEfficiency($diffs);
			}
		}
		elseif (is_array($a) && $opt_b === null && $opt_c === null)
		{
			// Method 2: diffs
			// Compute text1 from diffs.
			$diffs = $a;
			$text1 = $this->diffText1($diffs);
		}
		elseif (is_string($a) && is_array($opt_b) && $opt_c === null)
		{
			// Method 3: text1, diffs
			$text1 = $a;
			$diffs = $opt_b;
		}
		elseif (is_string($a) && is_string($opt_b) && is_array($opt_c))
		{
			// Method 4: text1, text2, diffs
			// text2 is not used.
			$text1 = $a;
			$diffs = $opt_c;
		}
		else
		{
			throw new Exception('Unknown call format to patchMake.');
		}

		if (count($diffs) === 0)
		{
			return array(); // Get rid of the null case.
		}

		$patches = array();
		$patch = new PatchObj();
		$patchDiffLength = 0; // Keeping our own length var is faster in JS.
		$charCount1 = 0; // Number of characters into the text1 string.
		$charCount2 = 0; // Number of characters into the text2 string.
		// Start with text1 (prepatch_text) and apply the diffs until we arrive at
		// text2 (postpatch_text).  We recreate the patches one by one to determine
		// context info.
		$prePatchText = $text1;
		$postPatchText = $text1;

		for ($x = 0, $l = count($diffs); $x < $l; $x++)
		{
			$diffType = $diffs[$x][0];
			$diffText = $diffs[$x][1];

			$diffTextLength = mb_strlen($diffText);

			if (!$patchDiffLength && $diffType !== self::DIFF_EQUAL)
			{
				// A new patch starts here.
				$patch->start1 = $charCount1;
				$patch->start2 = $charCount2;
			}

			switch ($diffType)
			{
				case self::DIFF_INSERT :
					$patch->diffs[$patchDiffLength++] = $diffs[$x];

					$patch->length2 += $diffTextLength;
					$postPatchText = mb_substr($postPatchText, 0, $charCount2) . $diffText . mb_substr($postPatchText, $charCount2);
					break;

				case self::DIFF_DELETE :
					$patch->length1 += $diffTextLength;
					$patch->diffs[$patchDiffLength++] = $diffs[$x];
					$postPatchText = mb_substr($postPatchText, 0, $charCount2) . mb_substr($postPatchText, $charCount2 + $diffTextLength);
					break;

				case self::DIFF_EQUAL :
					if ($diffTextLength <= 2 * $this->Patch_Margin && $patchDiffLength && count($diffs) != $x + 1)
					{
						// Small equality inside a patch.
						$patch->diffs[$patchDiffLength++] = $diffs[$x];
						$patch->length1 += $diffTextLength;
						$patch->length2 += $diffTextLength;
					}
					// Time for a new patch.
					elseif ($diffTextLength >= 2 * $this->Patch_Margin && $patchDiffLength)
					{
						$this->patchAddContext($patch, $prePatchText);
						array_push($patches, $patch);

						$patch = new PatchObj();
						$patchDiffLength = 0;
						// Unlike Unidiff, our patch lists have a rolling context.
						// http://code.google.com/p/google-diff-match-patch/wiki/Unidiff
						// Update prepatch text & pos to reflect the application of the
						// just completed patch.
						$prePatchText = $postPatchText;
						$charCount1 = $charCount2;
					}
					break;
			}

			// Update the current character count.
			if ($diffType !== self::DIFF_INSERT)
			{
				$charCount1 += $diffTextLength;
			}

			if ($diffType !== self::DIFF_DELETE)
			{
				$charCount2 += $diffTextLength;
			}
		}

		// Pick up the leftover patch if not empty.
		if ($patchDiffLength)
		{
			$this->patchAddContext($patch, $prePatchText);
			array_push($patches, $patch);
		}

		return $patches;
	}

	/**
	 * Given an array of patches, return another array that is identical.
	 *
	 * @param PatchObj[] $patches {Array.<patch_obj>} Array of patch objects.
	 * @return PatchObj[] {Array.<patch_obj>} Array of patch objects.
	 */
	protected function patchDeepCopy($patches)
	{
		// Making deep copies is hard in JavaScript.
		$patchesCopy = array();

		for ($x = 0, $l = count($patches); $x < $l; $x++)
		{
			$patch = $patches[$x];
			$patchCopy = new PatchObj();

			for ($y = 0, $l2 = count($patch->diffs); $y < $l2; $y++)
			{
				$patchCopy->diffs[$y] = $patch->diffs[$y]; // ?? . slice();
			}

			$patchCopy->start1 = $patch->start1;
			$patchCopy->start2 = $patch->start2;
			$patchCopy->length1 = $patch->length1;
			$patchCopy->length2 = $patch->length2;
			$patchesCopy[$x] = $patchCopy;
		}

		return $patchesCopy;
	}

	/**
	 * Merge a set of patches onto the text.  Return a patched text, as well
	 * as a list of true/false values indicating which patches were applied.
	 *
	 * @param PatchObj[] $patches {Array.<patch_obj>} Array of patch objects.
	 * @param string $text Old text.
	 * @return PatchObj[] {Array.<string|Array.<boolean>>} Two element Array, containing the
	 *      new text and an array of boolean values.
	 */
	public function patchApply($patches, $text)
	{
		if (count($patches) == 0)
		{
			return array($text, array());
		}

		// Deep copy the patches so that no changes are made to originals.
		$patches = $this->patchDeepCopy($patches);

		$nullPadding = $this->patchAddPadding($patches);
		$text = $nullPadding . $text . $nullPadding;

		$this->patchSplitMax($patches);
		// delta keeps track of the offset between the expected and actual location
		// of the previous patch.  If there are patches expected at positions 10 and
		// 20, but the first patch was found at 12, delta is 2 and the second patch
		// has an effective expected position of 22.
		$delta = 0;
		$results = array();

		for ($x = 0; $x < count($patches); $x++)
		{
			$expectedLoc = $patches[$x]->start2 + $delta;
			$text1 = $this->diffText1($patches[$x]->diffs);
			$text1length = mb_strlen($text1);
			$startLoc = null;
			$endLoc = -1;

			if ($text1length > $this->MATCH_MAX_BITS)
			{
				// patchSplitMax will only provide an oversized pattern in the case of
				// a monster delete.
				$startLoc = $this->matchMain($text, mb_substr($text1, 0, $this->MATCH_MAX_BITS), $expectedLoc);

				if ($startLoc != -1)
				{
					$endLoc = $this->matchMain($text, mb_substr($text1, $text1length - $this->MATCH_MAX_BITS), $expectedLoc + $text1length - $this->MATCH_MAX_BITS);

					if ($endLoc == -1 || $startLoc >= $endLoc)
					{
						// Can't find valid trailing context.  Drop this patch.
						$startLoc = -1;
					}
				}
			}
			else
			{
				$startLoc = $this->matchMain($text, $text1, $expectedLoc);
			}

			if ($startLoc == -1)
			{
				// No match found.  :(
				$results[$x] = false;
				// Subtract the delta for this failed patch from subsequent patches.
				$delta -= $patches[$x]->length2 - $patches[$x]->length1;
			}
			else
			{
				// Found a match.  :)
				$results[$x] = true;
				$delta = $startLoc - $expectedLoc;
				$text2 = null;

				if ($endLoc == -1)
				{
					$text2 = mb_substr($text, $startLoc, $text1length);
				}
				else
				{
					$text2 = mb_substr($text, $startLoc, $endLoc + $this->MATCH_MAX_BITS - $startLoc);
				}

				if ($text1 == $text2)
				{
					// Perfect match, just shove the replacement text in.
					$text = mb_substr($text, 0, $startLoc) . $this->diffText2($patches[$x]->diffs) . mb_substr($text, $startLoc + $text1length);
				}
				else
				{
					// Imperfect match.  Run a diff to get a framework of equivalent
					// indices.
					$diffs = $this->diffMain($text1, $text2, false);

					if ($text1length > $this->MATCH_MAX_BITS && $this->diffLevenshtein($diffs) / $text1length > $this->Patch_DeleteThreshold)
					{
						// The end points match, but the content is unacceptably bad.
						$results[$x] = false;
					}
					else
					{
						$this->diffCleanupSemanticLossless($diffs);
						$index1 = 0;
						$index2 = NULL;

						for ($y = 0; $y < count($patches[$x]->diffs); $y++)
						{
							$mod = $patches[$x]->diffs[$y];

							if ($mod[0] !== self::DIFF_EQUAL)
							{
								$index2 = $this->diffXIndex($diffs, $index1);
							}

							if ($mod[0] === self::DIFF_INSERT)
							{
								// Insertion
								$text = mb_substr($text, 0, $startLoc + $index2) . $mod[1] . mb_substr($text, $startLoc + $index2);
							}
							elseif ($mod[0] === self::DIFF_DELETE)
							{
								// Deletion
								$text = mb_substr($text, 0, $startLoc + $index2) . mb_substr($text, $startLoc + $this->diffXIndex($diffs, $index1 + mb_strlen($mod[1])));
							}

							if ($mod[0] !== self::DIFF_DELETE)
							{
								$index1 += mb_strlen($mod[1]);
							}
						}
					}
				}
			}
		}

		// Strip the padding off.
		$text = mb_substr($text, mb_strlen($nullPadding), mb_strlen($text) - 2 * mb_strlen($nullPadding));

		return array($text, $results);
	}

	/**
	 * Add some padding on text start and end so that edges can match something.
	 * Intended to be called only from within patchApply.
	 *
	 * @param PatchObj[] $patches {Array.<patch_obj>} Array of patch objects.
	 * @return string The padding string added to each side.
	 */
	protected function patchAddPadding(&$patches)
	{
		$paddingLength = $this->Patch_Margin;
		$nullPadding = '';

		for ($x = 1; $x <= $paddingLength; $x++)
		{
			$nullPadding .= mb_convert_encoding('&#' . intval($x) . ';', 'UTF-8', 'HTML-ENTITIES');
		}

		// Bump all the patches forward.
		for ($x = 0, $l = count($patches); $x < $l; $x++)
		{
			$patches[$x]->start1 += $paddingLength;
			$patches[$x]->start2 += $paddingLength;
		}

		// Add some padding on start of first diff.
		$patch = &$patches[0];
		$diffs = &$patch->diffs;

		if (count($diffs) == 0 || $diffs[0][0] != self::DIFF_EQUAL)
		{
			// Add nullPadding equality.
			array_unshift($diffs, array(self::DIFF_EQUAL, $nullPadding));
			$patch->start1 -= $paddingLength; // Should be 0.
			$patch->start2 -= $paddingLength; // Should be 0.
			$patch->length1 += $paddingLength;
			$patch->length2 += $paddingLength;
		}
		elseif ($paddingLength > $diffs01length = mb_strlen($diffs[0][1]))
		{
			// Grow first equality.
			$extraLength = $paddingLength - $diffs01length;
			$diffs[0][1] = mb_substr($nullPadding, $diffs01length) . $diffs[0][1];
			$patch->start1 -= $extraLength;
			$patch->start2 -= $extraLength;
			$patch->length1 += $extraLength;
			$patch->length2 += $extraLength;
		}

		// Add some padding on end of last diff.
		$patch = &$patches[count($patches) - 1];
		$diffs = &$patch->diffs;

		if (count($diffs) == 0 || $diffs[count($diffs) - 1][0] != self::DIFF_EQUAL)
		{
			// Add nullPadding equality.
			array_push($diffs, array(self::DIFF_EQUAL, $nullPadding));
			$patch->length1 += $paddingLength;
			$patch->length2 += $paddingLength;
		}
		elseif ($paddingLength > $diffs11length = mb_strlen($diffs[count($diffs) - 1][1]))
		{
			// Grow last equality.
			$extraLength = $paddingLength - $diffs11length;
			$diffs[count($diffs) - 1][1] .= mb_substr($nullPadding, 0, $extraLength);
			$patch->length1 += $extraLength;
			$patch->length2 += $extraLength;
		}

		return $nullPadding;
	}

	/**
	 * Look through the patches and break up any which are longer than the maximum
	 * limit of the match algorithm.
	 *
	 * @param PatchObj[] $patches {Array.<patch_obj>} Array of patch objects.
	 */
	protected function patchSplitMax(&$patches)
	{
		for ($x = 0; $x < count($patches); $x++)
		{
			if ($patches[$x]->length1 > $this->MATCH_MAX_BITS)
			{
				$bigPatch = $patches[$x];
				// Remove the big old patch.
				array_splice($patches, $x--, 1);
				$patch_size = $this->MATCH_MAX_BITS;
				$start1 = $bigPatch->start1;
				$start2 = $bigPatch->start2;
				$preContext = '';

				while (count($bigPatch->diffs) !== 0)
				{
					// Create one of several smaller patches.
					$patch = new PatchObj();
					$empty = true;
					$preContextLength = mb_strlen($preContext);
					$patch->start1 = $start1 - $preContextLength;
					$patch->start2 = $start2 - $preContextLength;

					if ($preContext !== '')
					{
						$patch->length1 = $patch->length2 = $preContextLength;
						array_push($patch->diffs, array(self::DIFF_EQUAL, $preContext));
					}
					while (count($bigPatch->diffs) !== 0 && $patch->length1 < $patch_size - $this->Patch_Margin)
					{
						$diff_type = $bigPatch->diffs[0][0];
						$diff_text = $bigPatch->diffs[0][1];

						if ($diff_type === self::DIFF_INSERT)
						{
							// Insertions are harmless.
							$patch->length2 += mb_strlen($diff_text);
							$start2 += mb_strlen($diff_text);
							array_push($patch->diffs, array_shift($bigPatch->diffs));
							$empty = false;
						}
						elseif ($diff_type === self::DIFF_DELETE && count($patch->diffs) == 1 && $patch->diffs[0][0] == self::DIFF_EQUAL && (mb_strlen($diff_text) > 2 * $patch_size))
						{
							// This is a large deletion.  Let it pass in one chunk.
							$patch->length1 += mb_strlen($diff_text);
							$start1 += mb_strlen($diff_text);
							$empty = false;
							array_push($patch->diffs, array($diff_type, $diff_text));
							array_shift($bigPatch->diffs);
						}
						else
						{
							// Deletion or equality.  Only take as much as we can stomach.
							$diff_text = mb_substr($diff_text, 0, $patch_size - $patch->length1 - $this->Patch_Margin);
							$diffTextLength = mb_strlen($diff_text);
							$patch->length1 += $diffTextLength;
							$start1 += $diffTextLength;

							if ($diff_type === self::DIFF_EQUAL)
							{
								$patch->length2 += $diffTextLength;
								$start2 += $diffTextLength;
							}
							else
							{
								$empty = false;
							}

							array_push($patch->diffs, array($diff_type, $diff_text));

							if ($diff_text == $bigPatch->diffs[0][1])
							{
								array_shift($bigPatch->diffs);
							}
							else
							{
								$bigPatch->diffs[0][1] = mb_substr($bigPatch->diffs[0][1], $diffTextLength);
							}
						}
					}

					// Compute the head context for the next patch.
					$preContext = $this->diffText2($patch->diffs);
					$preContext = mb_substr($preContext, mb_strlen($preContext) - $this->Patch_Margin);

					// Append the end context for this patch.
					$postContext = mb_substr($this->diffText1($bigPatch->diffs), 0, $this->Patch_Margin);

					if ($postContext !== '')
					{
						$patch->length1 += mb_strlen($postContext);
						$patch->length2 += mb_strlen($postContext);

						if (count($patch->diffs) !== 0 && $patch->diffs[count($patch->diffs) - 1][0] === self::DIFF_EQUAL)
						{
							$patch->diffs[count($patch->diffs) - 1][1] .= $postContext;
						}
						else
						{
							array_push($patch->diffs, array(self::DIFF_EQUAL, $postContext));
						}
					}

					if (!$empty)
					{
						array_splice($patches, ++$x, 0, array($patch));
					}
				}
			}
		}
	}

	/**
	 * Take a list of patches and return a textual representation.
	 *
	 * @param PatchObj[] $patches {Array.<patch_obj>} Array of patch objects.
	 * @return string Text representation of patches.
	 */
	public function patchToText($patches)
	{
		$text = array();

		for ($x = 0, $l = count($patches); $x < $l; $x++)
		{
			$text[$x] = $patches[$x];
		}

		return implode('', $text);
	}

	/**
	 * Parse a textual representation of patches and return a list of patch objects.
	 *
	 * @param string $textLine Text representation of patches.
	 * @return PatchObj[] {Array.<patch_obj>} Array of patch objects.
	 * @throws Exception If invalid input.
	 */
	public function patchFromText($textLine)
	{
		$patches = array();

		if ($textLine === '')
		{
			return $patches;
		}

		$text = explode("\n", $textLine);

		foreach ($text as $i => $t)
		{
			if ($t === '')
			{
				unset($text[$i]);
			}
		}

		$textPointer = 0;

		while ($textPointer < count($text))
		{
			$m = null;
			preg_match('/^@@ -(\d+),?(\d*) \+(\d+),?(\d*) @@$/', $text[$textPointer], $m);

			if (!$m)
			{
				throw new Exception('Invalid patch string: ' . $text[$textPointer]);
			}

			$patch = new PatchObj();
			array_push($patches, $patch);
			@$patch->start1 = (int)$m[1];

			if (@$m[2] === '')
			{
				$patch->start1--;
				$patch->length1 = 1;
			}
			elseif (@$m[2] == '0')
			{
				$patch->length1 = 0;
			}
			else
			{
				$patch->start1--;
				@$patch->length1 = (int)$m[2];
			}

			@$patch->start2 = (int)$m[3];

			if (@$m[4] === '')
			{
				$patch->start2--;
				$patch->length2 = 1;
			}
			elseif (@$m[4] == '0')
			{
				$patch->length2 = 0;
			}
			else
			{
				$patch->start2--;
				@$patch->length2 = (int)$m[4];
			}

			$textPointer++;

			while ($textPointer < count($text))
			{
				$sign = $text[$textPointer][0];

				try
				{
					$line = self::decodeURI(mb_substr($text[$textPointer], 1));
				}
				catch (Exception $ex)
				{
					// Malformed URI sequence.
					throw new Exception('Illegal escape in patchFromText: ' . (isset($line) ? $line : 'NULL'));
				}

				if ($sign == '-')
				{
					// Deletion.
					array_push($patch->diffs, array(self::DIFF_DELETE, $line));
				}
				elseif ($sign == '+')
				{
					// Insertion.
					array_push($patch->diffs, array(self::DIFF_INSERT, $line));
				}
				elseif ($sign == ' ')
				{
					// Minor equality.
					array_push($patch->diffs, array(self::DIFF_EQUAL, $line));
				}
				elseif ($sign == '@')
				{
					// Start of next patch.
					break;
				}
				elseif ($sign === '')
				{
					// Blank line?  Whatever.
				}
				else
				{
					// WTF?
					throw new Exception('Invalid patch mode "' . $sign . '" in: ' . $line);
				}

				$textPointer++;
			}
		}

		return $patches;
	}

	/**
	 * @var array
	 */
	protected static $_table = array(
		'%21' => '!',
		'%23' => '#',
		'%24' => '$',
		'%26' => '&',
		'%27' => '\'',
		'%28' => '(',
		'%29' => ')',
		'%2A' => '*',
		'%2B' => '+',
		'%2C' => ',',
		// '%2D' => '-', // unescaped
		// '%2E' => '.', // unescaped
		'%2F' => '/',
		'%3A' => ':',
		'%3B' => ';',
		'%3D' => '=',
		'%3F' => '?',
		'%40' => '@',
		// '%5F' => '_', // unescaped
		// '%7E' => '~', // unescaped
	);

	/**
	 * @var array
	 */
	protected static $_doNotDecode;

	/**
	 * as in javascript encodeURI() following the MDN description
	 *
	 * @link https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/encodeURI
	 * @param $url
	 * @return string
	 */
	public static function encodeURI($url)
	{
		return strtr(rawurlencode($url), self::$_table);
	}

	/**
	 * @param string $encoded
	 * @return string
	 */
	public static function decodeURI($encoded)
	{
		if (self::$_doNotDecode === null)
		{
			self::$_doNotDecode = array();

			foreach (self::$_table as $k => $v)
			{
				self::$_doNotDecode[$k] = self::encodeURI($k);
			}
		}

		return rawurldecode(strtr($encoded, self::$_doNotDecode));
	}
}

/**
 * Class representing one patch operation.
 * @constructor
 */
class PatchObj
{
	/** @type array {Array.<Array.<number|string>>} */
	public $diffs = array();

	/** @type integer */
	public $start1 = null;

	/** @type integer */
	public $start2 = null;

	/** @type integer */
	public $length1 = 0;

	/** @type integer */
	public $length2 = 0;

	/**
	 * Emmulate GNU diff's format.
	 * Header: @@ -382,8 +481,9 @@
	 * Indicies are printed as 1-based, not 0-based.
	 *
	 * @return string The GNU diff string.
	 * @throws Exception
	 */
	function toString()
	{
		if ($this->length1 === 0)
		{
			$coords1 = $this->start1 . ',0';
		}
		elseif ($this->length1 == 1)
		{
			$coords1 = $this->start1 + 1;
		}
		else
		{
			$coords1 = ($this->start1 + 1) . ',' . $this->length1;
		}

		if ($this->length2 === 0)
		{
			$coords2 = $this->start2 . ',0';
		}
		elseif ($this->length2 == 1)
		{
			$coords2 = $this->start2 + 1;
		}
		else
		{
			$coords2 = ($this->start2 + 1) . ',' . $this->length2;
		}

		$text = array('@@ -' . $coords1 . ' +' . $coords2 . " @@\n");

		// Escape the body of the patch with %xx notation.
		for ($x = 0, $l = count($this->diffs); $x < $l; $x++)
		{
			switch ($this->diffs[$x][0])
			{
				case DiffMatchPatch::DIFF_INSERT :
					$op = '+';
					break;

				case DiffMatchPatch::DIFF_DELETE :
					$op = '-';
					break;

				case DiffMatchPatch::DIFF_EQUAL :
					$op = ' ';
					break;

				default:
					throw new Exception('Unknown mode ' . var_export($this->diffs[$x][0], true));
			}

			$text[$x + 1] = $op . DiffMatchPatch::encodeURI($this->diffs[$x][1]) . "\n";
		}

		return str_replace('%20', ' ', implode('', $text));
	}

	function __toString()
	{
		return $this->toString();
	}
}