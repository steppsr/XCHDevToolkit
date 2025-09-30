<?php
/**
 * Script: address_to_puzzle_hash.php
 * Purpose: Decode a Bech32m Chia address to a 32-byte hexadecimal puzzle hash
 * Author: steppsr
 * Version: 1.0
 * Date: September 30, 2025
 *
 * Description:
 *   This script takes a Bech32m Chia address and converts it back to its
 *   32-byte hexadecimal puzzle hash using the Bech32m decoding algorithm.
 *   It validates the checksum and supports all Chia address types.
 *   This implementation can be used from command line or integrated into web applications.
 *
 * Usage (Command Line):
 *   php address_to_puzzle_hash.php <address> [--no-prefix]
 *
 * Usage (In Code):
 *   require_once 'address_to_puzzle_hash.php';
 *   $puzzleHash = addressToPuzzleHash($address, $includePrefix);
 *
 * Parameters:
 *   address     - Bech32m Chia address (required)
 *   --no-prefix - Optional flag to exclude '0x' prefix from output
 *
 * Examples:
 *   php address_to_puzzle_hash.php xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z
 *   Output: 0x0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
 *
 *   php address_to_puzzle_hash.php xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z --no-prefix
 *   Output: 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
 *
 *   php address_to_puzzle_hash.php txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld
 *   Output: 0x0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
 */

/**
 * PSEUDOCODE:
 * 1. Validate input (check if address is provided)
 * 2. Convert address to lowercase for consistency
 * 3. Find the '1' separator and split address into prefix and data
 * 4. Validate that separator exists and data is present
 * 5. Decode Bech32 characters to 5-bit values
 * 6. Verify Bech32m checksum is valid
 * 7. Remove the 6 checksum values from the end of the data
 * 8. Convert 5-bit groups back to 8-bit bytes
 * 9. Convert bytes to hexadecimal string
 * 10. Return puzzle hash with or without '0x' prefix based on flag
 */

// Bech32 character set (32 characters, excludes 1, b, i, o to avoid confusion)
const BECH32_CHARSET = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

// Bech32m constant (different from original Bech32)
const BECH32M_CONST = 0x2bc830a3;

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
 * Decode a Bech32 character to its 5-bit value
 *
 * @param string $char The Bech32 character to decode
 * @return int|false The 5-bit value (0-31), or false if invalid
 */
function decodeBech32Char(string $char) {
    $pos = strpos(BECH32_CHARSET, $char);
    return $pos !== false ? $pos : false;
}

/**
 * Decode a Bech32 string to an array of 5-bit values
 *
 * @param string $data The Bech32 encoded string
 * @return array|false Array of 5-bit values, or false if invalid characters found
 */
function decodeBech32(string $data) {
    $result = [];
    
    for ($i = 0; $i < strlen($data); $i++) {
        $value = decodeBech32Char($data[$i]);
        
        if ($value === false) {
            trigger_error("Invalid Bech32 character: {$data[$i]}", E_USER_WARNING);
            return false;
        }
        
        $result[] = $value;
    }
    
    return $result;
}

/**
 * Verify the Bech32m checksum for the given address
 *
 * @param string $hrp The human-readable part (prefix)
 * @param array $data Array of 5-bit data values (including checksum)
 * @return bool True if checksum is valid, false otherwise
 */
function verifyChecksum(string $hrp, array $data): bool {
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
    
    // Combine HRP and data
    $values = array_merge($hrpExpanded, $data);
    
    // Calculate polymod - should equal Bech32m constant if valid
    $polymod = bech32Polymod($values);
    
    return $polymod === BECH32M_CONST;
}

/**
 * Convert 5-bit groups back to 8-bit bytes
 *
 * @param array $data Array of 5-bit values
 * @return array|false Array of 8-bit byte values, or false on error
 */
function convertBitsTo8(array $data) {
    $result = [];
    $acc = 0;
    $bits = 0;
    
    // Process each 5-bit value
    foreach ($data as $value) {
        // Add value to accumulator
        $acc = (($acc << 5) | $value);
        $bits += 5;
        
        // Extract 8-bit bytes
        while ($bits >= 8) {
            $bits -= 8;
            $result[] = ($acc >> $bits) & 255;
        }
    }
    
    // Check if there are leftover bits (padding should be zero)
    if ($bits >= 5 || (($acc << (8 - $bits)) & 255) !== 0) {
        trigger_error('Invalid padding in address data', E_USER_WARNING);
        return false;
    }
    
    return $result;
}

/**
 * Convert an array of bytes to a hexadecimal string
 *
 * @param array $bytes Array of byte values (0-255)
 * @return string Hexadecimal string representation
 */
function bytesToHex(array $bytes): string {
    $hex = '';
    
    foreach ($bytes as $byte) {
        $hex .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
    }
    
    return $hex;
}

/**
 * Convert a Bech32m Chia address to a puzzle hash
 *
 * @param string $address The Bech32m address to decode
 * @param bool $includePrefix Whether to include '0x' prefix (default: true)
 * @return string|false The puzzle hash as hex string, or false on error
 */
function addressToPuzzleHash(string $address, bool $includePrefix = true) {
    // Convert to lowercase for consistency
    $address = strtolower($address);
    
    // Find the separator '1'
    $separatorPos = strrpos($address, '1');
    
    if ($separatorPos === false) {
        trigger_error('Invalid address format: missing separator', E_USER_WARNING);
        return false;
    }
    
    if ($separatorPos === 0) {
        trigger_error('Invalid address format: empty prefix', E_USER_WARNING);
        return false;
    }
    
    if ($separatorPos === strlen($address) - 1) {
        trigger_error('Invalid address format: empty data', E_USER_WARNING);
        return false;
    }
    
    // Split into prefix and data
    $prefix = substr($address, 0, $separatorPos);
    $dataString = substr($address, $separatorPos + 1);
    
    // Decode Bech32 characters to 5-bit values
    $data = decodeBech32($dataString);
    
    if ($data === false) {
        return false;
    }
    
    // Verify checksum
    if (!verifyChecksum($prefix, $data)) {
        trigger_error('Invalid address: checksum verification failed', E_USER_WARNING);
        return false;
    }
    
    // Remove the 6 checksum values from the end
    $dataWithoutChecksum = array_slice($data, 0, count($data) - 6);
    
    // Convert 5-bit groups to 8-bit bytes
    $bytes = convertBitsTo8($dataWithoutChecksum);
    
    if ($bytes === false) {
        return false;
    }
    
    // Validate length (should be 32 bytes for a puzzle hash)
    if (count($bytes) !== 32) {
        trigger_error("Invalid puzzle hash length: expected 32 bytes, got " . count($bytes), E_USER_WARNING);
        return false;
    }
    
    // Convert bytes to hex string
    $puzzleHash = bytesToHex($bytes);
    
    // Add 0x prefix if requested
    if ($includePrefix) {
        $puzzleHash = '0x' . $puzzleHash;
    }
    
    return $puzzleHash;
}

/**
 * Display usage information for command-line usage
 */
function displayUsage(): void {
    echo "Usage: php address_to_puzzle_hash.php <address> [--no-prefix]\n\n";
    echo "Parameters:\n";
    echo "  address     - Bech32m Chia address (required)\n";
    echo "  --no-prefix - Optional flag to exclude '0x' prefix from output\n\n";
    echo "Examples:\n";
    echo "  php address_to_puzzle_hash.php xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z\n";
    echo "  php address_to_puzzle_hash.php xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z --no-prefix\n";
    echo "  php address_to_puzzle_hash.php txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld\n";
    echo "  php address_to_puzzle_hash.php nft1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqsaddk4\n";
}

// Command-line execution
if (php_sapi_name() === 'cli') {
    // Check if address argument is provided
    if ($argc < 2) {
        displayUsage();
        exit(1);
    }
    
    // Get address from command line
    $address = $argv[1];
    
    // Check for --no-prefix flag
    $includePrefix = true;
    if ($argc > 2 && ($argv[2] === '--no-prefix' || $argv[2] === '-n')) {
        $includePrefix = false;
    }
    
    // Convert and display the puzzle hash
    $puzzleHash = addressToPuzzleHash($address, $includePrefix);
    
    if ($puzzleHash === false) {
        exit(1);
    }
    
    echo $puzzleHash . "\n";
    exit(0);
}
