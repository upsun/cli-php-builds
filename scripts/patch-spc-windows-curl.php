<?php

/**
 * Builds curl against OpenSSL instead of Schannel, on Windows.
 *
 * static-php-cli builds curl with Schannel. Schannel cannot combine a CA file
 * with the Windows certificate store: given a file it verifies against that
 * file alone, so an organization's own root certificate, which is installed in
 * the store, is not seen. It also refuses a CA file larger than 1 MiB, which a
 * bundle including the store's certificates can exceed. The CLI needs a CA
 * file, because the openssl extension cannot read the store at all, so with
 * Schannel there is no way to trust both sets of certificates.
 *
 * curl built against OpenSSL loads a CA file and the Windows stores together,
 * and has no size limit. OpenSSL is already built here for the openssl
 * extension.
 *
 * See https://github.com/upsun/cli/issues/110, and
 * https://github.com/crazywhalecc/static-php-cli/pull/674 which chose Schannel.
 *
 * Usage: php scripts/patch-spc-windows-curl.php [path to static-php-cli]
 */

declare(strict_types=1);

$spcDir = $argv[1] ?? 'spc';

/**
 * Stops with a message, so that a build never quietly uses the wrong backend.
 */
function fail(string $message): never
{
    fwrite(STDERR, 'patch-spc-windows-curl: ' . $message . PHP_EOL);
    exit(1);
}

function readFileOrFail(string $path): string
{
    if (!is_file($path)) {
        fail("$path does not exist: check the static-php-cli version");
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        fail("could not read $path");
    }
    return $contents;
}

function writeFileOrFail(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) === false) {
        fail("could not write $path");
    }
}

// curl needs the OpenSSL library on Windows. static-php-cli removed it from
// curl's dependencies when it switched to Schannel, and without it the build
// order does not guarantee OpenSSL is there first.
$libFile = $spcDir . '/config/lib.json';
$libs = json_decode(readFileOrFail($libFile), true);
if (!is_array($libs) || !isset($libs['curl']['lib-depends-windows']) || !is_array($libs['curl']['lib-depends-windows'])) {
    fail("$libFile does not list curl's Windows dependencies: check the static-php-cli version");
}
if (in_array('openssl', $libs['curl']['lib-depends-windows'], true)) {
    echo "curl already depends on openssl on Windows\n";
} else {
    array_unshift($libs['curl']['lib-depends-windows'], 'openssl');
    writeFileOrFail($libFile, json_encode($libs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    echo "added openssl to curl's Windows dependencies\n";
}

// Choose the backend in the cmake options curl is built with.
$curlFile = $spcDir . '/src/SPC/builder/windows/library/curl.php';
$source = readFileOrFail($curlFile);
$options = [
    "'-DUSE_WINDOWS_SSPI=ON '" => "'-DUSE_WINDOWS_SSPI=OFF '",
    "'-DCURL_USE_SCHANNEL=ON '" => "'-DCURL_USE_SCHANNEL=OFF '",
    "'-DCURL_USE_OPENSSL=OFF '" => "'-DCURL_USE_OPENSSL=ON '",
];
foreach ($options as $from => $to) {
    if (substr_count($source, $to) === 1 && !str_contains($source, $from)) {
        echo "already set: $to\n";
        continue;
    }
    if (substr_count($source, $from) !== 1) {
        fail("expected exactly one $from in $curlFile: check the static-php-cli version");
    }
    $source = str_replace($from, $to, $source);
    echo "set $to\n";
}
writeFileOrFail($curlFile, $source);
