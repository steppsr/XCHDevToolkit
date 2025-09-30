#!/bin/bash

################################################################################
# Script: address_to_puzzle_hash.sh
# Purpose: Convert a Chia Bech32m address back to its puzzle hash (32-byte hex)
# Author: steppsr
# Version: 1.1
# Date: September 30, 2025
#
# Description:
#   This script takes a Chia Bech32m address (xch1, txch1, nft1, did1, etc.)
#   and decodes it back to the original 32-byte hexadecimal puzzle hash.
#   It validates the address format and verifies the Bech32m checksum.
#   Optionally outputs with or without the "0x" prefix.
#
# Usage:
#   ./address_to_puzzle_hash.sh <address> [--no-prefix]
#
# Parameters:
#   address     - Bech32m encoded Chia address (required)
#   --no-prefix - Optional flag to exclude the "0x" hex prefix from output
#
# Examples:
#   ./address_to_puzzle_hash.sh xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z
#   Output: 0x0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
#
#   ./address_to_puzzle_hash.sh xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z --no-prefix
#   Output: 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
#
#   ./address_to_puzzle_hash.sh txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld --no-prefix
#   Output: 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
################################################################################

################################################################################
# PSEUDOCODE:
# 1. Validate input (check if address is provided)
# 2. Check for --no-prefix flag to determine output format
# 3. Convert address to lowercase for consistency
# 4. Split address at '1' separator to get prefix and data portions
# 5. Validate that the address contains the '1' separator
# 6. Decode Bech32 characters to 5-bit values
# 7. Verify Bech32m checksum is valid
# 8. Remove the 6-byte checksum from the end of the data
# 9. Convert 5-bit groups back to 8-bit bytes
# 10. Convert bytes to hexadecimal string
# 11. Output the puzzle hash with or without "0x" prefix based on flag
################################################################################

# Bech32 character set (32 characters, excludes 1, b, i, o to avoid confusion)
CHARSET="qpzry9x8gf2tvdw0s3jn54khce6mua7l"

# Decode a Bech32 character to its 5-bit value
decode_char() {
    local char=$1
    local pos=$(expr index "$CHARSET" "$char")
    
    if [ $pos -eq 0 ]; then
        echo -1
        return 1
    fi
    
    echo $((pos - 1))
}

# Decode a Bech32 string to array of 5-bit values
decode_bech32() {
    local encoded=$1
    local values=()
    
    for ((i=0; i<${#encoded}; i++)); do
        local char="${encoded:$i:1}"
        local value=$(decode_char "$char")
        
        if [ $value -eq -1 ]; then
            echo "Error: Invalid character '$char' in address" >&2
            return 1
        fi
        
        values+=($value)
    done
    
    echo "${values[@]}"
}

# Calculate Bech32m polymod for checksum verification
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

# Verify Bech32m checksum
verify_checksum() {
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
    
    # Combine HRP and data
    local values=("${hrp_expanded[@]}" "${data[@]}")
    
    # Calculate polymod - should equal Bech32m constant if valid
    local polymod=$(bech32_polymod "${values[@]}")
    local expected=$((0x2bc830a3))
    
    if [ $polymod -eq $expected ]; then
        return 0
    else
        return 1
    fi
}

# Convert 5-bit groups back to 8-bit bytes
convert_bits_back() {
    local data=($@)
    local result=()
    local acc=0
    local bits=0
    
    # Process each 5-bit value
    for value in "${data[@]}"; do
        # Add value to accumulator
        acc=$(( (acc << 5) | value ))
        bits=$((bits + 5))
        
        # Extract 8-bit bytes
        while [ $bits -ge 8 ]; do
            bits=$((bits - 8))
            result+=($(( (acc >> bits) & 255 )))
        done
    done
    
    # Check for invalid padding (remaining bits should be zero)
    if [ $bits -gt 0 ]; then
        local padding=$(( acc & ((1 << bits) - 1) ))
        if [ $padding -ne 0 ]; then
            echo "Error: Invalid padding in address data" >&2
            return 1
        fi
    fi
    
    echo "${result[@]}"
}

# Convert byte array to hexadecimal string
bytes_to_hex() {
    local bytes=($@)
    local hex=""
    
    for byte in "${bytes[@]}"; do
        printf -v hex_byte "%02x" $byte
        hex+="$hex_byte"
    done
    
    echo "$hex"
}

# Main decode function
address_to_puzzle_hash() {
    local address=$1
    local no_prefix=$2
    
    # Convert to lowercase for consistency
    address=$(echo "$address" | tr '[:upper:]' '[:lower:]')
    
    # Check if address contains the '1' separator
    if [[ ! "$address" =~ "1" ]]; then
        echo "Error: Invalid address format (missing '1' separator)" >&2
        return 1
    fi
    
    # Split address at the last '1' to get prefix and data
    local prefix="${address%1*}"
    local data_part="${address##*1}"
    
    # Validate prefix exists
    if [ -z "$prefix" ] || [ -z "$data_part" ]; then
        echo "Error: Invalid address format" >&2
        return 1
    fi
    
    # Decode Bech32 data to 5-bit values
    local decoded=($(decode_bech32 "$data_part"))
    if [ $? -ne 0 ]; then
        return 1
    fi
    
    # Verify checksum
    if ! verify_checksum "$prefix" "${decoded[@]}"; then
        echo "Error: Invalid checksum - address may be corrupted" >&2
        return 1
    fi
    
    # Remove the 6-byte checksum from the end
    local data_length=$((${#decoded[@]} - 6))
    local payload=("${decoded[@]:0:$data_length}")
    
    # Convert 5-bit values back to 8-bit bytes
    local bytes=($(convert_bits_back "${payload[@]}"))
    if [ $? -ne 0 ]; then
        return 1
    fi
    
    # Validate we got exactly 32 bytes
    if [ ${#bytes[@]} -ne 32 ]; then
        echo "Error: Decoded data is not 32 bytes (got ${#bytes[@]} bytes)" >&2
        return 1
    fi
    
    # Convert bytes to hex
    local hex=$(bytes_to_hex "${bytes[@]}")
    
    # Return puzzle hash with or without 0x prefix based on flag
    if [ "$no_prefix" = true ]; then
        echo "${hex}"
    else
        echo "0x${hex}"
    fi
}

################################################################################
# MAIN SCRIPT EXECUTION
################################################################################

# Check if address argument is provided
if [ $# -eq 0 ]; then
    echo "Usage: $0 <address> [--no-prefix]"
    echo ""
    echo "Parameters:"
    echo "  address     - Bech32m encoded Chia address (required)"
    echo "  --no-prefix - Optional flag to exclude the '0x' hex prefix from output"
    echo ""
    echo "Examples:"
    echo "  $0 xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z"
    echo "  $0 xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z --no-prefix"
    echo "  $0 txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld --no-prefix"
    echo "  $0 nft1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqsaddk4"
    exit 1
fi

# Parse arguments
ADDRESS=$1
NO_PREFIX=false

# Check for --no-prefix flag
if [ "$2" = "--no-prefix" ] || [ "$2" = "-n" ]; then
    NO_PREFIX=true
fi

# Decode and display the puzzle hash
address_to_puzzle_hash "$ADDRESS" "$NO_PREFIX"
