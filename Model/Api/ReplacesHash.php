<?php

namespace Codeko\Redsys\Model\Api;

class ReplacesHash {

    /**
     * Replace hash_hmac()
     *
     * @category    PHP
     * @package     PHP_Compat
     * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
     * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
     * @link        http://php.net/function.hash_hmac
     * @author      revulo <revulon@gmail.com>
     * @since       PHP 5.1.2
     * @require     PHP 4.0.1 (str_pad)
     */
    static function php_compat_hash_hmac($algo, $data, $key, $raw_output = false) {
        // Block size (byte) for MD5, SHA-1 and SHA-256.
        $blocksize = 64;

        $ipad = str_repeat("\x36", $blocksize);
        $opad = str_repeat("\x5c", $blocksize);

        if (strlen($key) > $blocksize) {
            $key = hash($algo, $key, true);
        } else {
            $key = str_pad($key, $blocksize, "\x00");
        }

        $ipad ^= $key;
        $opad ^= $key;

        return hash($algo, $opad . hash($algo, $ipad . $data, true), $raw_output);
    }

    /**
     * Replace hash()
     *
     * @category    PHP
     * @package     PHP_Compat
     * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
     * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
     * @link        http://php.net/function.hash
     * @author      revulo <revulon@gmail.com>
     * @since       PHP 5.1.2
     * @require     PHP 4.0.0 (user_error)
     */
    static function php_compat_hash($algo, $data, $raw_output = false) {
        $algo = strtolower($algo);
        switch ($algo) {
            case 'md5':
                $hash = md5($data);
                break;

            case 'sha1':
                if (!function_exists('sha1')) {
                    require dirname(__FILE__) . '/sha1.php';
                }
                $hash = sha1($data);
                break;

            case 'sha256':
                require_once dirname(__FILE__) . '/sha256.php';
                $hash = SHA256::hash($data);
                break;

            default:
                user_error('hash(): Unknown hashing algorithm: ' . $algo, E_USER_WARNING);
                return false;
        }

        if ($raw_output) {
            return pack('H*', $hash);
        } else {
            return $hash;
        }
    }

    static function hash_hmac4($algo, $data, $key, $raw_output = false) {
        return $this->php_compat_hash_hmac($algo, $data, $key, $raw_output);
    }
    
    static function hash($algo, $data, $raw_output = false)
    {
        return $this->php_compat_hash($algo, $data, $raw_output);
    }

}
