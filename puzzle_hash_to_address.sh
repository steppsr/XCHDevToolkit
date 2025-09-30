#!/bin/bash

################################################################################
# Script: puzzle_hash_to_address.sh
# Purpose: Convert a Chia puzzle hash (32-byte hex) to a Bech32m address
# Author: steppsr
# Version: 1.1
# Date: September 30, 2025
#
# Description:
#   This script takes a 32-byte hexadecimal puzzle hash and converts it to
#   a Bech32m address using the Bech32m encoding algorithm. The address prefix
#   can be customized to support different address types (xch1, txch1, nft1, did1).
#
# Usage:
#   ./puzzle_hash_to_address.sh <puzzle_hash> [prefix]
#
# Parameters:
#   puzzle_hash - 32-byte hexadecimal value (required)
#   prefix      - Address prefix without the '1' separator (optional, default: xch)
#
# Examples:
#   ./puzzle_hash_to_address.sh 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
#   Output: xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z
#
#   ./puzzle_hash_to_address.sh 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e txch
#   Output: txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld
#
#   ./puzzle_hash_to_address.sh 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e nft
#   Output: nft1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqsaddk4
################################################################################

################################################################################
# PSEUDOCODE:
# 1. Validate input (check if puzzle hash is provided and is valid hex)
# 2. Get address prefix from second parameter (default to "xch" if not provided)
# 3. Remove any "0x" prefix if present from puzzle hash
# 4. Convert hex string to byte array
# 5. Convert 8-bit bytes to 5-bit groups (required for Bech32m)
# 6. Calculate Bech32m checksum for the data using the specified prefix
# 7. Append checksum to the 5-bit data
# 8. Encode 5-bit values to Bech32 character set
# 9. Prepend prefix + "1" separator to create final address
# 10. Output the address (e.g., xch1..., txch1..., nft1..., did1...)
################################################################################

# Bech32 character set (32 characters, excludes 1, b, i, o to avoid confusion)
CHARSET="qpzry9x8gf2tvdw0s3jn54khce6mua7l"

# Convert a hex string to an array of bytes (decimal values)
hex_to_bytes() {
    local hex=$1
    local bytes=()
    
    # Process hex string two characters at a time
    for ((i=0; i<${#hex}; i+=2)); do
        bytes+=($((16#${hex:$i:2})))
    done
    
    echo "${bytes[@]}"
}

# Convert 8-bit bytes to 5-bit groups
convert_bits() {
    local data=($@)
    local result=()
    local acc=0
    local bits=0
    
    # Process each byte
    for byte in "${data[@]}"; do
        # Add byte to accumulator
        acc=$(( (acc << 8) | byte ))
        bits=$((bits + 8))
        
        # Extract 5-bit groups
        while [ $bits -ge 5 ]; do
            bits=$((bits - 5))
            result+=($(( (acc >> bits) & 31 )))
        done
    done
    
    # Handle remaining bits with padding
    if [ $bits -gt 0 ]; then
        result+=($(( (acc << (5 - bits)) & 31 )))
    fi
    
    echo "${result[@]}"
}

# Calculate Bech32m checksum
bech32_polymod() {
    local values=($@)
    local gen=(0x3b6a57b2 0x26508e6d 0x1ea119fa 0x3d4233dd 0x2a1462b3)
    local chk=1
    
    for value in "${values[@]}"; do
        local top=$(( chk >> 25 ))
        chk=$(( ((chk & 0x1ffffff) << 5) ^ value ))
        
        for i in {0..4}; do
            if [ $(( (top >> i) & 1 )) -eq 1 ]; then
                chk=$(( chk ^ gen[i] ))
            fi
        done
    done
    
    echo $chk
}

# Create checksum for Bech32m encoding
create_checksum() {
    local hrp=$1
    shift
    local data=($@)
    
    # Expand HRP (human-readable part)
    local hrp_expanded=()
    for ((i=0; i<${#hrp}; i++)); do
        local char="${hrp:$i:1}"
        printf -v ascii "%d" "'$char"
        hrp_expanded+=($(( ascii >> 5 )))
    done
    hrp_expanded+=(0)
    for ((i=0; i<${#hrp}; i++)); do
        local char="${hrp:$i:1}"
        printf -v ascii "%d" "'$char"
        hrp_expanded+=($(( ascii & 31 )))
    done
    
    # Combine HRP and data with 6 zero bytes for checksum placeholder
    local values=("${hrp_expanded[@]}" "${data[@]}" 0 0 0 0 0 0)
    
    # Calculate polymod and XOR with Bech32m constant
    local polymod=$(bech32_polymod "${values[@]}")
    local checksum_value=$(( polymod ^ 0x2bc830a3 ))
    
    # Extract 6 5-bit checksum values
    local checksum=()
    for i in {0..5}; do
        checksum+=($(( (checksum_value >> (5 * (5 - i))) & 31 )))
    done
    
    echo "${checksum[@]}"
}

# Encode 5-bit values to Bech32 characters
encode_bech32() {
    local values=($@)
    local result=""
    
    for value in "${values[@]}"; do
        result+="${CHARSET:$value:1}"
    done
    
    echo "$result"
}

# Main conversion function
puzzle_hash_to_address() {
    local puzzle_hash=$1
    local prefix=${2:-xch}  # Default to "xch" if no prefix provided
    
    # Remove 0x prefix if present
    puzzle_hash=${puzzle_hash#0x}
    puzzle_hash=${puzzle_hash#0X}
    
    # Validate hex string (should be 64 characters for 32 bytes)
    if [ ${#puzzle_hash} -ne 64 ]; then
        echo "Error: Puzzle hash must be 32 bytes (64 hex characters)" >&2
        return 1
    fi
    
    # Convert to lowercase for consistency
    puzzle_hash=$(echo "$puzzle_hash" | tr '[:upper:]' '[:lower:]')
    
    # Convert hex to bytes
    local bytes=($(hex_to_bytes "$puzzle_hash"))
    
    # Convert 8-bit bytes to 5-bit groups
    local five_bit=($(convert_bits "${bytes[@]}"))
    
    # Calculate checksum using the specified prefix
    local checksum=($(create_checksum "$prefix" "${five_bit[@]}"))
    
    # Combine data and checksum
    local combined=("${five_bit[@]}" "${checksum[@]}")
    
    # Encode to Bech32 characters
    local encoded=$(encode_bech32 "${combined[@]}")
    
    # Return final address with prefix and separator
    echo "${prefix}1${encoded}"
}

################################################################################
# MAIN SCRIPT EXECUTION
################################################################################

# Check if puzzle hash argument is provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <puzzle_hash> [prefix]"
    echo ""
    echo "Parameters:"
    echo "  puzzle_hash - 32-byte hexadecimal value (required)"
    echo "  prefix      - Address prefix without the '1' separator (optional, default: xch)"
    echo ""
    echo "Examples:"
    echo "  $0 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e"
    echo "  $0 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e txch"
    echo "  $0 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e nft"
    echo "  $0 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e did"
    exit 1
fi

# Convert and display the address (pass both puzzle hash and optional prefix)
puzzle_hash_to_address "$1" "$2"

