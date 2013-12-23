<?php
/*
 * Transparent SHA-256 Implementation for PHP 4 and PHP 5
 *
 * Author: Perry McGee (pmcgee@nanolink.ca)
 * Website: http://www.nanolink.ca/pub/sha256
 *
 * Copyright (C) 2006,2007,2008,2009 Nanolink Solutions
 *
 * Created: Feb 11, 2006
 *
 *    This library is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU Lesser General Public
 *    License as published by the Free Software Foundation; either
 *    version 2.1 of the License, or (at your option) any later version.
 *
 *    This library is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Lesser General Public License for more details.

 *    You should have received a copy of the GNU Lesser General Public
 *    License along with this library; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 *    or see <http://www.gnu.org/licenses/>.
 *
 *  Include:
 *
 *   require_once("[path/]sha256.inc.php");
 *
 *  Usage Options:
 *
 *   1) $shaStr = hash('sha256', $string_to_hash);
 *
 *   2) $shaStr = sha256($string_to_hash[, bool ignore_php5_hash = false]);
 *
 *   3) $obj = new nanoSha2([bool $upper_case_output = false]);
 *      $shaStr = $obj->hash($string_to_hash[, bool $ignore_php5_hash = false]);
 *
 * Reference: http://csrc.nist.gov/groups/ST/toolkit/secure_hashing.html
 *
 * 2007-12-13: Cleaned up for initial public release
 * 2008-05-10: Moved all helper functions into a class.  API access unchanged.
 * 2009-06-23: Created abstraction of hash() routine
 * 2009-07-23: Added detection of 32 vs 64bit platform, and patches.
 *             Ability to define "_NANO_SHA2_UPPER" to yeild upper case hashes.
 * 2009-08-01: Added ability to attempt to use mhash() prior to running pure
 *             php code.
 *
 * NOTE: Some sporadic versions of PHP do not handle integer overflows the
 *       same as the majority of builds.  If you get hash results of:
 *        7fffffff7fffffff7fffffff7fffffff7fffffff7fffffff7fffffff7fffffff
 *
 *       If you do not have permissions to change PHP versions (if you did
 *       you'd probably upgrade to PHP 5 anyway) it is advised you install a
 *       module that will allow you to use their hashing routines, examples are:
 *       - mhash module : http://ca3.php.net/mhash
 *       - Suhosin : http://www.hardened-php.net/suhosin/
 *
 *       If you install the Suhosin module, this script will transparently
 *       use their routine and define the PHP routine as _nano_sha256().
 *
 *       If the mhash module is present, and $ignore_php5_hash = false the
 *       script will attempt to use the output from mhash prior to running
 *       the PHP code.
 */
    class nanoSha2
    {
        // php 4 - 5 compatable class properties
        var     $toUpper;
        var     $platform;

        // Php 4 - 6 compatable constructor
        function nanoSha2($toUpper = false) {
            // Determine if the caller wants upper case or not.
            $this->toUpper = is_bool($toUpper)
                           ? $toUpper
                           : ((defined('_NANO_SHA2_UPPER')) ? true : false);

            // Deteremine if the system is 32 or 64 bit.
            $tmpInt = (int)4294967295;
            $this->platform = ($tmpInt > 0) ? 64 : 32;
        }

        // Do the SHA-256 Padding routine (make input a multiple of 512 bits)
        function char_pad($str)
        {
            $tmpStr = $str;

            $l = strlen($tmpStr)*8;     // # of bits from input string

            $tmpStr .= "\x80";          // append the "1" bit followed by 7 0's

            $k = (512 - (($l + 8 + 64) % 512)) / 8;   // # of 0 bytes to append
            $k += 4;    // PHP Strings will never exceed (2^31)-1, 1st 32bits of
                        // the 64-bit value representing $l can be all 0's

            for ($x = 0; $x < $k; $x++) {
                $tmpStr .= "\0";
            }

            // append the 32-bits representing # of bits from input string ($l)
            $tmpStr .= chr((($l>>24) & 0xFF));
            $tmpStr .= chr((($l>>16) & 0xFF));
            $tmpStr .= chr((($l>>8) & 0xFF));
            $tmpStr .= chr(($l & 0xFF));

            return $tmpStr;
        }

        // Here are the bitwise and functions as defined in FIPS180-2 Standard
        function addmod2n($x, $y, $n = 4294967296)      // Z = (X + Y) mod 2^32
        {
            $mask = 0x80000000;

            if ($x < 0) {
                $x &= 0x7FFFFFFF;
                $x = (float)$x + $mask;
            }

            if ($y < 0) {
                $y &= 0x7FFFFFFF;
                $y = (float)$y + $mask;
            }

            $r = $x + $y;

            if ($r >= $n) {
                while ($r >= $n) {
                    $r -= $n;
                }
            }

            return (int)$r;
        }

        // Logical bitwise right shift (PHP default is arithmetic shift)
        function SHR($x, $n)        // x >> n
        {
            if ($n >= 32) {      // impose some limits to keep it 32-bit
                return (int)0;
            }

            if ($n <= 0) {
                return (int)$x;
            }

            $mask = 0x40000000;

            if ($x < 0) {
                $x &= 0x7FFFFFFF;
                $mask = $mask >> ($n-1);
                return ($x >> $n) | $mask;
            }

            return (int)$x >> (int)$n;
        }

        function ROTR($x, $n) { return (int)(($this->SHR($x, $n) | ($x << (32-$n)) & 0xFFFFFFFF)); }
        function Ch($x, $y, $z) { return ($x & $y) ^ ((~$x) & $z); }
        function Maj($x, $y, $z) { return ($x & $y) ^ ($x & $z) ^ ($y & $z); }
        function Sigma0($x) { return (int) ($this->ROTR($x, 2)^$this->ROTR($x, 13)^$this->ROTR($x, 22)); }
        function Sigma1($x) { return (int) ($this->ROTR($x, 6)^$this->ROTR($x, 11)^$this->ROTR($x, 25)); }
        function sigma_0($x) { return (int) ($this->ROTR($x, 7)^$this->ROTR($x, 18)^$this->SHR($x, 3)); }
        function sigma_1($x) { return (int) ($this->ROTR($x, 17)^$this->ROTR($x, 19)^$this->SHR($x, 10)); }

        /*
         * Custom functions to provide PHP support
         */
        // split a byte-string into integer array values
        function int_split($input)
        {
            $l = strlen($input);

            if ($l <= 0) {
                return (int)0;
            }

            if (($l % 4) != 0) { // invalid input
                return false;
            }

            for ($i = 0; $i < $l; $i += 4)
            {
                $int_build  = (ord($input[$i]) << 24);
                $int_build += (ord($input[$i+1]) << 16);
                $int_build += (ord($input[$i+2]) << 8);
                $int_build += (ord($input[$i+3]));

                $result[] = $int_build;
            }

            return $result;
        }

        /**
         * Process and return the hash.
         *
         * @param $str Input string to hash
         * @param $ig_func Option param to ignore checking for php > 5.1.2
         * @return string Hexadecimal representation of the message digest
         */
        function hash($str, $ig_func = false)
        {
            unset($binStr);     // binary representation of input string
            unset($hexStr);     // 256-bit message digest in readable hex format

            // check for php's internal sha256 function, ignore if ig_func==true
            if ($ig_func == false) {
                if (version_compare(PHP_VERSION,'5.1.2','>=')) {
                    return hash("sha256", $str, false);
                } else if (function_exists('mhash') && defined('MHASH_SHA256')) {
                    return base64_encode(bin2hex(mhash(MHASH_SHA256, $str)));
                }
            }

            /*
             * SHA-256 Constants
             *  Sequence of sixty-four constant 32-bit words representing the
             *  first thirty-two bits of the fractional parts of the cube roots
             *  of the first sixtyfour prime numbers.
             */
            $K = array((int)0x428a2f98, (int)0x71374491, (int)0xb5c0fbcf,
                       (int)0xe9b5dba5, (int)0x3956c25b, (int)0x59f111f1,
                       (int)0x923f82a4, (int)0xab1c5ed5, (int)0xd807aa98,
                       (int)0x12835b01, (int)0x243185be, (int)0x550c7dc3,
                       (int)0x72be5d74, (int)0x80deb1fe, (int)0x9bdc06a7,
                       (int)0xc19bf174, (int)0xe49b69c1, (int)0xefbe4786,
                       (int)0x0fc19dc6, (int)0x240ca1cc, (int)0x2de92c6f,
                       (int)0x4a7484aa, (int)0x5cb0a9dc, (int)0x76f988da,
                       (int)0x983e5152, (int)0xa831c66d, (int)0xb00327c8,
                       (int)0xbf597fc7, (int)0xc6e00bf3, (int)0xd5a79147,
                       (int)0x06ca6351, (int)0x14292967, (int)0x27b70a85,
                       (int)0x2e1b2138, (int)0x4d2c6dfc, (int)0x53380d13,
                       (int)0x650a7354, (int)0x766a0abb, (int)0x81c2c92e,
                       (int)0x92722c85, (int)0xa2bfe8a1, (int)0xa81a664b,
                       (int)0xc24b8b70, (int)0xc76c51a3, (int)0xd192e819,
                       (int)0xd6990624, (int)0xf40e3585, (int)0x106aa070,
                       (int)0x19a4c116, (int)0x1e376c08, (int)0x2748774c,
                       (int)0x34b0bcb5, (int)0x391c0cb3, (int)0x4ed8aa4a,
                       (int)0x5b9cca4f, (int)0x682e6ff3, (int)0x748f82ee,
                       (int)0x78a5636f, (int)0x84c87814, (int)0x8cc70208,
                       (int)0x90befffa, (int)0xa4506ceb, (int)0xbef9a3f7,
                       (int)0xc67178f2);

            // Pre-processing: Padding the string
            $binStr = $this->char_pad($str);

            // Parsing the Padded Message (Break into N 512-bit blocks)
            $M = str_split($binStr, 64);

            // Set the initial hash values
            $h[0] = (int)0x6a09e667;
            $h[1] = (int)0xbb67ae85;
            $h[2] = (int)0x3c6ef372;
            $h[3] = (int)0xa54ff53a;
            $h[4] = (int)0x510e527f;
            $h[5] = (int)0x9b05688c;
            $h[6] = (int)0x1f83d9ab;
            $h[7] = (int)0x5be0cd19;

            // loop through message blocks and compute hash. ( For i=1 to N : )
            $N = count($M);
            for ($i = 0; $i < $N; $i++)
            {
                // Break input block into 16 32bit words (message schedule prep)
                $MI = $this->int_split($M[$i]);

                // Initialize working variables
                $_a = (int)$h[0];
                $_b = (int)$h[1];
                $_c = (int)$h[2];
                $_d = (int)$h[3];
                $_e = (int)$h[4];
                $_f = (int)$h[5];
                $_g = (int)$h[6];
                $_h = (int)$h[7];
                unset($_s0);
                unset($_s1);
                unset($_T1);
                unset($_T2);
                $W = array();

                // Compute the hash and update
                for ($t = 0; $t < 16; $t++)
                {
                    // Prepare the first 16 message schedule values as we loop
                    $W[$t] = $MI[$t];

                    // Compute hash
                    $_T1 = $this->addmod2n($this->addmod2n($this->addmod2n($this->addmod2n($_h, $this->Sigma1($_e)), $this->Ch($_e, $_f, $_g)), $K[$t]), $W[$t]);
                    $_T2 = $this->addmod2n($this->Sigma0($_a), $this->Maj($_a, $_b, $_c));

                    // Update working variables
                    $_h = $_g; $_g = $_f; $_f = $_e; $_e = $this->addmod2n($_d, $_T1);
                    $_d = $_c; $_c = $_b; $_b = $_a; $_a = $this->addmod2n($_T1, $_T2);
                }

                for (; $t < 64; $t++)
                {
                    // Continue building the message schedule as we loop
                    $_s0 = $W[($t+1)&0x0F];
                    $_s0 = $this->sigma_0($_s0);
                    $_s1 = $W[($t+14)&0x0F];
                    $_s1 = $this->sigma_1($_s1);

                    $W[$t&0xF] = $this->addmod2n($this->addmod2n($this->addmod2n($W[$t&0xF], $_s0), $_s1), $W[($t+9)&0x0F]);

                    // Compute hash
                    $_T1 = $this->addmod2n($this->addmod2n($this->addmod2n($this->addmod2n($_h, $this->Sigma1($_e)), $this->Ch($_e, $_f, $_g)), $K[$t]), $W[$t&0xF]);
                    $_T2 = $this->addmod2n($this->Sigma0($_a), $this->Maj($_a, $_b, $_c));

                    // Update working variables
                    $_h = $_g; $_g = $_f; $_f = $_e; $_e = $this->addmod2n($_d, $_T1);
                    $_d = $_c; $_c = $_b; $_b = $_a; $_a = $this->addmod2n($_T1, $_T2);
                }

                $h[0] = $this->addmod2n($h[0], $_a);
                $h[1] = $this->addmod2n($h[1], $_b);
                $h[2] = $this->addmod2n($h[2], $_c);
                $h[3] = $this->addmod2n($h[3], $_d);
                $h[4] = $this->addmod2n($h[4], $_e);
                $h[5] = $this->addmod2n($h[5], $_f);
                $h[6] = $this->addmod2n($h[6], $_g);
                $h[7] = $this->addmod2n($h[7], $_h);
            }

            // Convert the 32-bit words into human readable hexadecimal format.
            $hexStr = sprintf("%08x%08x%08x%08x%08x%08x%08x%08x", $h[0], $h[1], $h[2], $h[3], $h[4], $h[5], $h[6], $h[7]);

            return ($this->toUpper) ? strtoupper($hexStr) : $hexStr;
        }

    }
