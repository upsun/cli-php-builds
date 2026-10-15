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

Releases are tagged `php-{VERSION}-{BUILD}`, where `BUILD` counts the builds of
a PHP version. Older releases are tagged `php-{VERSION}`.

```bash
# Example: Download build 1 of PHP 8.5.11 for Linux x86_64
curl -fSL https://github.com/upsun/cli-php-builds/releases/download/php-8.5.11-1/php-8.5.11-linux-amd64 -o php
chmod +x php
./php -v
```

### Verify Checksums

Each release includes a `SHA256SUMS.txt` file:

```bash
curl -fSL https://github.com/upsun/cli-php-builds/releases/download/php-8.5.11-1/SHA256SUMS.txt -o SHA256SUMS.txt
sha256sum -c SHA256SUMS.txt
```

## Building New Versions

### Automatic rebuilds

The **Check for updates** workflow runs daily. It compares the sources of the
latest release (`sources.json`) with the latest versions that static-php-cli
would download, including the latest patch release of PHP. If any have changed,
it builds and releases the same PHP minor version again.

If the check or the build fails, it opens an issue (or comments on an open one).

To move to a new PHP minor version, run a build manually.

### Manual builds

1. Go to **Actions** > **Build PHP**
2. Click **Run workflow**
3. Enter the PHP version (e.g., `8.5.11`, or `8.5` for the latest patch), or
   leave it empty for the minor version of the latest release
4. Click **Run workflow**

The workflow will:
1. Build PHP for all 5 platforms in parallel
2. Create a GitHub release with all binaries, `sources.json` (the source
   archives used on each platform) and `SHA256SUMS.txt`

Pull requests build all platforms without creating a release.

All libraries are built from source (static-php-cli's pre-built libraries are
not used), so `sources.json` records exactly what is in each binary.

### Upgrading static-php-cli

Update `SPC_VERSION` and the SHA-256 checksums in both `build-php.yml` and
`check-updates.yml`.

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
