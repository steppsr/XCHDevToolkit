<?php
/**
 * Script: puzzle_hash_to_address.php
 * Purpose: Convert a Chia puzzle hash (32-byte hex) to a Bech32m address
 * Author: steppsr
 * Version: 1.0
 * Date: September 30, 2025
 *
 * Description:
 *   This script takes a 32-byte hexadecimal puzzle hash and converts it to
 *   a Bech32m address using the Bech32m encoding algorithm. The address prefix
 *   can be customized to support different address types (xch1, txch1, nft1, did1).
 *   This implementation can be used from command line or integrated into web applications.
 *
 * Usage (Command Line):
 *   php puzzle_hash_to_address.php <puzzle_hash> [prefix]
 *
 * Usage (In Code):
 *   require_once 'puzzle_hash_to_address.php';
 *   $address = puzzleHashToAddress($puzzleHash, $prefix);
 *
 * Parameters:
 *   puzzle_hash - 32-byte hexadecimal value (required)
 *   prefix      - Address prefix without the '1' separator (optional, default: xch)
 *
 * Examples:
 *   php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
 *   Output: xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z
 *
 *   php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e txch
 *   Output: txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld
 *
 *   php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e nft
 *   Output: nft1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqsaddk4
 */

/**
 * PSEUDOCODE:
 * 1. Validate input (check if puzzle hash is provided and is valid hex)
 * 2. Get address prefix from parameter (default to "xch" if not provided)
 * 3. Remove any "0x" prefix if present from puzzle hash
 * 4. Convert hex string to byte array
 * 5. Convert 8-bit bytes to 5-bit groups (required for Bech32m)
 * 6. Calculate Bech32m checksum for the data using the specified prefix
 * 7. Append checksum to the 5-bit data
 * 8. Encode 5-bit values to Bech32 character set
 * 9. Prepend prefix + "1" separator to create final address
 * 10. Return the address (e.g., xch1..., txch1..., nft1..., did1...)
 */

// Bech32 character set (32 characters, excludes 1, b, i, o to avoid confusion)
const BECH32_CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

// Bech32m constant (different from original Bech32)
const BECH32M_CONST = 0x2bc830a3;

/**
 * Convert a hexadecimal string to an array of bytes (integers 0-255)
 *
 * @param string $hex The hexadecimal string to convert
 * @return array Array of byte values (integers)
 */
function hexToBytes(string $hex): array {
    $bytes = [];
    $length = strlen($hex);
    
    // Process hex string two characters at a time
    for ($i = 0; $i < $length; $i += 2) {
        $bytes[] = hexdec(substr($hex, $i, 2));
    }
    
    return $bytes;
}

/**
 * Convert 8-bit bytes to 5-bit groups for Bech32 encoding
 *
 * @param array $data Array of 8-bit byte values
 * @return array Array of 5-bit values
 */
function convertBits(array $data): array {
    $result = [];
    $acc = 0;
    $bits = 0;
    
    // Process each byte
    foreach ($data as $byte) {
        // Add byte to accumulator
        $acc = (($acc << 8) | $byte);
        $bits += 8;
        
        // Extract 5-bit groups
        while ($bits >= 5) {
            $bits -= 5;
            $result[] = ($acc >> $bits) & 31;
        }
    }
    
    // Handle remaining bits with padding
    if ($bits > 0) {
        $result[] = ($acc << (5 - $bits)) & 31;
    }
    
    return $result;
}

/**
 * Calculate the Bech32 polymod checksum
 *
 * @param array $values Array of 5-bit values to checksum
 * @return int The polymod checksum value
 */
function bech32Polymod(array $values): int {
    $gen = [0x3b6a57b2, 0x26508e6d, 0x1ea119fa, 0x3d4233dd, 0x2a1462b3];
    $chk = 1;
    
    foreach ($values as $value) {
        $top = $chk >> 25;
        $chk = ((($chk & 0x1ffffff) << 5) ^ $value);
        
        for ($i = 0; $i < 5; $i++) {
            if (($top >> $i) & 1) {
                $chk ^= $gen[$i];
            }
        }
    }
    
    return $chk;
}

/**
 * Create a Bech32m checksum for the given data and human-readable part
 *
 * @param string $hrp The human-readable part (prefix)
 * @param array $data Array of 5-bit data values
 * @return array Array of 6 5-bit checksum values
 */
function createChecksum(string $hrp, array $data): array {
    // Expand HRP (human-readable part)
    $hrpExpanded = [];
    
    // Add high bits of each character
    for ($i = 0; $i < strlen($hrp); $i++) {
        $hrpExpanded[] = ord($hrp[$i]) >> 5;
    }
    
    // Add separator
    $hrpExpanded[] = 0;
    
    // Add low bits of each character
    for ($i = 0; $i < strlen($hrp); $i++) {
        $hrpExpanded[] = ord($hrp[$i]) & 31;
    }
    
    // Combine HRP and data with 6 zero bytes for checksum placeholder
    $values = array_merge($hrpExpanded, $data, [0, 0, 0, 0, 0, 0]);
    
    // Calculate polymod and XOR with Bech32m constant
    $polymod = bech32Polymod($values);
    $checksumValue = $polymod ^ BECH32M_CONST;
    
    // Extract 6 5-bit checksum values
    $checksum = [];
    for ($i = 0; $i < 6; $i++) {
        $checksum[] = ($checksumValue >> (5 * (5 - $i))) & 31;
    }
    
    return $checksum;
}

/**
 * Encode an array of 5-bit values to Bech32 characters
 *
 * @param array $values Array of 5-bit values
 * @return string Encoded Bech32 string
 */
function encodeBech32(array $values): string {
    $result = '';
    
    foreach ($values as $value) {
        $result .= BECH32_CHARSET[$value];
    }
    
    return $result;
}

/**
 * Convert a Chia puzzle hash to a Bech32m address
 *
 * @param string $puzzleHash 32-byte hexadecimal puzzle hash
 * @param string $prefix Address prefix (default: 'xch')
 * @return string|false The Bech32m address, or false on error
 */
function puzzleHashToAddress(string $puzzleHash, string $prefix = 'xch') {
    // Remove 0x prefix if present
    $puzzleHash = preg_replace('/^0x/i', '', $puzzleHash);
    
    // Validate hex string (should be 64 characters for 32 bytes)
    if (strlen($puzzleHash) !== 64) {
        trigger_error('Puzzle hash must be 32 bytes (64 hex characters)', E_USER_WARNING);
        return false;
    }
    
    // Validate that the string contains only hex characters
    if (!ctype_xdigit($puzzleHash)) {
        trigger_error('Puzzle hash must contain only hexadecimal characters', E_USER_WARNING);
        return false;
    }
    
    // Convert to lowercase for consistency
    $puzzleHash = strtolower($puzzleHash);
    
    // Convert hex to bytes
    $bytes = hexToBytes($puzzleHash);
    
    // Convert 8-bit bytes to 5-bit groups
    $fiveBit = convertBits($bytes);
    
    // Calculate checksum using the specified prefix
    $checksum = createChecksum($prefix, $fiveBit);
    
    // Combine data and checksum
    $combined = array_merge($fiveBit, $checksum);
    
    // Encode to Bech32 characters
    $encoded = encodeBech32($combined);
    
    // Return final address with prefix and separator
    return $prefix . '1' . $encoded;
}

/**
 * Display usage information for command-line usage
 */
function displayUsage(): void {
    echo "Usage: php puzzle_hash_to_address.php <puzzle_hash> [prefix]\n\n";
    echo "Parameters:\n";
    echo "  puzzle_hash - 32-byte hexadecimal value (required)\n";
    echo "  prefix      - Address prefix without the '1' separator (optional, default: xch)\n\n";
    echo "Examples:\n";
    echo "  php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e\n";
    echo "  php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e txch\n";
    echo "  php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e nft\n";
    echo "  php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e did\n";
}

// Command-line execution
if (php_sapi_name() === 'cli') {
    // Check if puzzle hash argument is provided
    if ($argc < 2) {
        displayUsage();
        exit(1);
    }
    
    // Get puzzle hash from command line
    $puzzleHash = $argv[1];
    
    // Get optional prefix (default to 'xch')
    $prefix = $argv[2] ?? 'xch';
    
    // Convert and display the address
    $address = puzzleHashToAddress($puzzleHash, $prefix);
    
    if ($address === false) {
        exit(1);
    }
    
    echo $address . "\n";
    exit(0);
}
