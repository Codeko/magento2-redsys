<?php

namespace Codeko\Redsys\Model\Api;

use Codeko\Redsys\Model\Api\HashData;

class SHA256Data extends HashData {

    // buffer
    var $buf = array();
    // padded data
    var $chunks = null;

    function __construct($str) {
        $M = strlen($str);  // number of bytes
        $L1 = ($M >> 28) & 0x0000000F;  // top order bits
        $L2 = $M << 3;  // number of bits
        $l = pack('N*', $L1, $L2);

        // 64 = 64 bits needed for the size mark. 1 = the 1 bit added to the
        // end. 511 = 511 bits to get the number to be at least large enough
        // to require one block. 512 is the block size.
        $k = $L2 + 64 + 1 + 511;
        $k -= $k % 512 + $L2 + 64 + 1;
        $k >>= 3;  // convert to byte count

        $str .= chr(0x80) . str_repeat(chr(0), $k) . $l;

        assert('strlen($str) % 64 == 0');

        // break the binary string into 512-bit blocks
        preg_match_all('#.{64}#', $str, $this->chunks);
        $this->chunks = $this->chunks[0];

        // H(0)
        $this->hash = array(
            1779033703, -1150833019,
            1013904242, -1521486534,
            1359893119, -1694144372,
            528734635, 1541459225,
        );
    }
}