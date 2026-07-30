# CLI PHP Builds

Pre-built PHP binaries for the [Upsun CLI](https://github.com/upsun/cli).

## Overview

This repository provides static PHP binaries for all platforms supported by the Upsun CLI. Binaries are built using [static-php-cli](https://github.com/crazywhalecc/static-php-cli) and published as GitHub releases.

## Supported Platforms

| Platform | Architecture | Binary Name |
|----------|--------------|-------------|
| Linux | x86_64 | `php-{VERSION}-linux-amd64` |
| Linux | ARM64 | `php-{VERSION}-linux-arm64` |
| macOS | Intel (x86_64) | `php-{VERSION}-darwin-amd64` |
| macOS | Apple Silicon | `php-{VERSION}-darwin-arm64` |
| Windows | x64 | `php-{VERSION}-windows-amd64.exe` |

## PHP Configuration

**Extensions included:**
- curl
- filter
- openssl
- pcntl (Unix only)
- phar
- posix (Unix only)
- zlib

These are the minimum extensions required by the "legacy" PHP code in the Upsun CLI.

## Usage

### Download a Binary

```bash
# Example: Download PHP 8.4.3 for Linux x86_64
curl -fSL https://github.com/upsun/cli-php-builds/releases/download/php-8.4.3/php-8.4.3-linux-amd64 -o php
chmod +x php
./php -v
```

### Verify Checksums

Each release includes a `SHA256SUMS.txt` file:

```bash
curl -fSL https://github.com/upsun/cli-php-builds/releases/download/php-8.4.3/SHA256SUMS.txt -o SHA256SUMS.txt
sha256sum -c SHA256SUMS.txt
```

## Building New Versions

1. Go to **Actions** > **Build PHP**
2. Click **Run workflow**
3. Enter the PHP version (e.g., `8.4.3`)
4. Optionally modify extensions or disable release creation
5. Click **Run workflow**

The workflow will:
1. Build PHP for all 5 platforms in parallel
2. Create a GitHub release with all binaries
3. Generate checksums

## SSL Certificate Handling

- **Linux/macOS**: static-php-cli automatically uses the system CA bundle
- **Windows**: Requires explicit `cacert.pem` configuration (handled by the CLI)

### Windows SSL backend

static-php-cli builds curl with Schannel on Windows. These builds change that to
OpenSSL, which is already built for the `openssl` extension, using
`scripts/patch-spc-windows-curl.php`.

Schannel verifies against a CA file *instead of* the Windows certificate store,
and refuses a file larger than 1 MiB. The CLI has to pass a CA file, because the
`openssl` extension cannot read the store at all, so with Schannel a root
certificate installed by an organization is never trusted. curl built against
OpenSSL loads a CA file and the Windows stores together, and has no size limit.

The patch fails the build if static-php-cli changes in a way it does not expect,
rather than quietly producing a Schannel build. See
[upsun/cli#110](https://github.com/upsun/cli/issues/110).

## License

The build scripts in this repository are MIT licensed. PHP binaries are subject to the [PHP License](https://www.php.net/license/).
