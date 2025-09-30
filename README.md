# XCH Dev Toolkit

A comprehensive collection of development tools for working with the Chia blockchain. This toolkit provides utilities for address conversion, puzzle hash manipulation, and other essential Chia development tasks.

## Overview

The XCH Dev Toolkit is designed to be a modular, well-documented resource for Chia developers. Each tool is implemented with clean code, comprehensive comments, and can serve as both a practical utility and a teaching aid for understanding Chia's technical implementation.

## Project Goals

- **Educational**: Well-commented code suitable for learning Chia blockchain concepts
- **Practical**: Production-ready tools for everyday development tasks
- **Multi-language**: Implementations across multiple programming languages
- **Modular**: Each tool is standalone and can be used independently

## Available Tools

### Bash Scripts

#### 1. Puzzle Hash to Address Converter
**File**: `puzzle_hash_to_address.sh` (v1.1)

Converts a 32-byte hexadecimal puzzle hash to a Bech32m encoded Chia address.

**Features**:
- Supports custom address prefixes (xch, txch, nft, did, etc.)
- Implements Bech32m encoding algorithm
- Validates input format
- Comprehensive error handling

**Usage**:
```bash
./puzzle_hash_to_address.sh <puzzle_hash> [prefix]
```

**Examples**:
```bash
# Mainnet address (default)
./puzzle_hash_to_address.sh 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
# Output: xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z

# Testnet address
./puzzle_hash_to_address.sh 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e txch
# Output: txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld

# NFT address
./puzzle_hash_to_address.sh 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e nft
# Output: nft1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqsaddk4
```

#### 2. Address to Puzzle Hash Converter
**File**: `address_to_puzzle_hash.sh` (v1.1)

Decodes a Bech32m encoded Chia address back to its original 32-byte hexadecimal puzzle hash.

**Features**:
- Supports all Chia address types (xch1, txch1, nft1, did1, etc.)
- Validates Bech32m checksum
- Optional output without "0x" prefix
- Comprehensive error handling

**Usage**:
```bash
./address_to_puzzle_hash.sh <address> [--no-prefix]
```

**Examples**:
```bash
# With 0x prefix (default)
./address_to_puzzle_hash.sh xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z
# Output: 0x0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e

# Without 0x prefix
./address_to_puzzle_hash.sh xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z --no-prefix
# Output: 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
```

### PHP Scripts

#### 1. Puzzle Hash to Address Converter
**File**: `puzzle_hash_to_address.php` (v1.0)

Converts a 32-byte hexadecimal puzzle hash to a Bech32m encoded Chia address. Can be used from command line or integrated into web applications.

**Features**:
- Dual-purpose design (CLI and web integration)
- Supports custom address prefixes (xch, txch, nft, did, etc.)
- Implements complete Bech32m encoding algorithm
- Type-safe with PHP type hints
- Comprehensive error handling

**Usage (Command Line)**:
```bash
php puzzle_hash_to_address.php <puzzle_hash> [prefix]
```

**Usage (In Code)**:
```php
require_once 'puzzle_hash_to_address.php';
$address = puzzleHashToAddress($puzzleHash, 'xch');
echo $address;
```

**Examples**:
```bash
# Mainnet address (default)
php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
# Output: xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z

# Testnet address
php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e txch
# Output: txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld

# NFT address
php puzzle_hash_to_address.php 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e nft
# Output: nft1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqsaddk4
```

#### 2. Address to Puzzle Hash Converter
**File**: `address_to_puzzle_hash.php` (v1.0)

Decodes a Bech32m encoded Chia address back to its original 32-byte hexadecimal puzzle hash. Can be used from command line or integrated into web applications.

**Features**:
- Dual-purpose design (CLI and web integration)
- Supports all Chia address types (xch1, txch1, nft1, did1, etc.)
- Validates Bech32m checksum
- Optional output without "0x" prefix
- Type-safe with PHP type hints
- Comprehensive error handling

**Usage (Command Line)**:
```bash
php address_to_puzzle_hash.php <address> [--no-prefix]
```

**Usage (In Code)**:
```php
require_once 'address_to_puzzle_hash.php';
$puzzleHash = addressToPuzzleHash($address, true);  // with 0x prefix
$puzzleHash = addressToPuzzleHash($address, false); // without prefix
echo $puzzleHash;
```

**Examples**:
```bash
# With 0x prefix (default)
php address_to_puzzle_hash.php xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z
# Output: 0x0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e

# Without 0x prefix
php address_to_puzzle_hash.php xch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lql4hz3z --no-prefix
# Output: 0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e

# Testnet address
php address_to_puzzle_hash.php txch1pwrzyy35qxk0rz76jl0648fvt6ql905vwd7zs0scjqant5sf25lqay7sld
# Output: 0x0b8622123401acf18bda97dfaa9d2c5e81f2be8c737c283e18903b35d209553e
```

## Installation

### Bash Scripts

1. Clone the repository:
```bash
git clone https://github.com/steppsr/XCHDevToolkit.git
cd XCHDevToolkit
```

2. Make scripts executable:
```bash
chmod +x *.sh
```

3. Run any tool:
```bash
./puzzle_hash_to_address.sh <your_puzzle_hash>
```

### PHP Scripts

1. Ensure PHP 7.4 or higher is installed:
```bash
php --version
```

2. Clone the repository (if not already done):
```bash
git clone https://github.com/steppsr/XCHDevToolkit.git
cd XCHDevToolkit
```

3. Run any PHP tool:
```bash
php puzzle_hash_to_address.php <your_puzzle_hash>
```

4. For web integration, simply `require_once` the script and call the functions directly.

## Roadmap

### Planned Tools (under consideration)
- Key pair generation utilities
- Transaction builders
- Coin management tools
- CLVM puzzle analysis tools
- Merkle tree utilities
- Signature verification tools

### Planned Language Implementations (under consideration)
- **Python**: Native Chia ecosystem integration
- **JavaScript/TypeScript**: Browser and Node.js support
- **Go**: High-performance implementations

## Technical Details

### Bech32m Encoding
All address conversion tools implement the Bech32m encoding specification as used by Chia. This includes:
- Conversion between 8-bit and 5-bit groups
- Polymod checksum calculation with Bech32m constant (0x2bc830a3)
- Custom character set handling (qpzry9x8gf2tvdw0s3jn54khce6mua7l)
- Address prefix management

### Address Types Supported
- **xch1**: Mainnet standard addresses
- **txch1**: Testnet addresses
- **nft1**: NFT ownership addresses
- **did1**: Decentralized Identity addresses
- Custom prefixes as needed

## Contributing

Contributions are welcome! Please feel free to submit pull requests, report bugs, or suggest new tools.

### Code Style Guidelines
- Include comprehensive header comments with metadata
- Provide pseudocode outline for complex algorithms
- Use clear variable names and add inline comments
- Include usage examples in help text
- Handle errors gracefully with informative messages
- Follow language-specific best practices (PSR standards for PHP, etc.)

## License

Apache License 2.0

## Author

**steppsr**

## Version History

### v0.2.0 (September 30, 2025)
- Added puzzle_hash_to_address.php (v1.0)
- Added address_to_puzzle_hash.php (v1.0)
- PHP implementations support both CLI and web integration

### v0.1.0 (September 30, 2025)
- Initial release
- Added puzzle_hash_to_address.sh (v1.1)
- Added address_to_puzzle_hash.sh (v1.1)

## Resources

- [Chia Network Official Documentation](https://docs.chia.net/)
- [Chia Blockchain GitHub](https://github.com/Chia-Network/chia-blockchain)
- [Bech32m Specification (BIP-350)](https://github.com/bitcoin/bips/blob/master/bip-0350.mediawiki)
- [Chialisp Documentation](https://chialisp.com/)

## Support

For issues, questions, or suggestions, please open an issue on the GitHub repository.

---

*This toolkit is an independent project and is not officially affiliated with Chia Network Inc.*
