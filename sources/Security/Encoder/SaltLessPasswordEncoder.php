<?php
/**
 * Class Encoder
 */
namespace Moro\Platform\Security\Encoder;

/**
 * Class Encoder
 * @package Moro\Platform\Security\Encoder
 */
class SaltLessPasswordEncoder implements SaltLessPasswordEncoderInterface
{
	/**
	 * Encodes the raw password.
	 *
	 * @param string $raw  The password to encode
	 *
	 * @return string The encoded password
	 */
	public function encodePassword($raw)
	{
		return $raw;
	}

	/**
	 * Checks a raw password against an encoded password.
	 *
	 * @param string $encoded An encoded password
	 * @param string $raw     A raw password
	 *
	 * @return Boolean true if the password is valid, false otherwise
	 */
	public function isPasswordValid($encoded, $raw)
	{
		return $encoded === $raw;
	}
}