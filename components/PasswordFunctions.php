<?php
namespace app\components;


class PasswordFunctions
{


    /**
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private static function slowEquals($str1, $str2)
    {
        $diff = strlen($str1) ^ strlen($str2);
        for ($i = 0; $i < strlen($str1) && $i < strlen($str2); $i++) {
            $diff |= ord($str1[$i]) ^ ord($str2[$i]);
        }
        return $diff === 0;
    }


    /**
     * @static
     * @param string $password
     * @return string
     */
    public static function createHash($password)
    {
        // from: http://crackstation.net/hashing-security.htm
        // format: algorithm:iterations:salt:hash
        $salt = base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
        return "sha256:1000:" . $salt . ":" . base64_encode(static::pbkdf2("sha256", $password, $salt, 1000, 24, true));
    }


    /*
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     * $algorithm - The hash algorithm to use. Recommended: SHA256
     * $password - The password.
     * $salt - A salt that is unique to the password.
     * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
     * $key_length - The length of the derived key in bytes.
     * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
     * Returns: A $key_length-byte key derived from the password and salt.
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     *
     * This implementation of PBKDF2 was originally created by https://defuse.ca
     * With improvements by http://www.variations-of-shadow.com
     */
    /**
     * @param string $algorithm
     * @param string $password
     * @param string $salt
     * @param int $count
     * @param int $key_length
     * @param bool $raw_output
     * @return string
     */
    private static function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true)) {
            die('PBKDF2 ERROR: Invalid hash algorithm.');
        }
        if ($count <= 0 || $key_length <= 0) {
            die('PBKDF2 ERROR: Invalid parameters.');
        }

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if ($raw_output) {
            return substr($output, 0, $key_length);
        } else {
            return bin2hex(substr($output, 0, $key_length));
        }
    }


    /**
     * @param string $passwordToCheck
     * @param string $passwordEncoded
     * @return bool
     */
    public static function validatePassword($passwordToCheck, $passwordEncoded)
    {
        $params = explode(":", $passwordEncoded);
        if (count($params) < 4) {
            return false;
        }
        $pbkdf2 = base64_decode($params[3]);
        return static::slowEquals(
            $pbkdf2,
            static::pbkdf2(
                $params[0],
                $passwordToCheck,
                $params[2],
                (int)$params[1],
                strlen($pbkdf2),
                true
            )
        );
    }
}
