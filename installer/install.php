<?php
declare(strict_types=1);

/**
 * Perkora PHP SDK - standalone installer.
 *
 * Usage:
 * php install.php owner/repository [branch-or-tag] [project-directory]
 */

function fail(string $message): never
{
    fwrite(STDERR, "Perkora installer error: {$message}\n");
    exit(1);
}

function removeDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir()
            ? rmdir($item->getPathname())
            : unlink($item->getPathname());
    }

    rmdir($directory);
}

if (PHP_SAPI !== 'cli') {
    fail('This installer must be run from the command line.');
}

if (!extension_loaded('curl') || !class_exists(ZipArchive::class)) {
    fail('PHP cURL and ZIP extensions are required.');
}

$repository = $argv[1] ?? '';
$ref = $argv[2] ?? 'main';
$projectDirectory = $argv[3] ?? getcwd();

if (!preg_match(
    '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#',
    $repository
)) {
    fail('Provide a GitHub repository in owner/repository format.');
}

if (
    $ref === '' ||
    str_contains($ref, '..') ||
    str_starts_with($ref, '-') ||
    !preg_match('#^[A-Za-z0-9._/-]+$#', $ref)
) {
    fail('The branch or tag name is invalid.');
}

if (!is_dir($projectDirectory)) {
    fail('The target project directory does not exist.');
}

$projectDirectory = realpath($projectDirectory);

if ($projectDirectory === false) {
    fail('Could not resolve the target project directory.');
}

$installDirectory =
    $projectDirectory . '/vendor/perkora/php-sdk';

if (file_exists($installDirectory)) {
    fail(
        'An SDK installation already exists at ' .
        $installDirectory .
        '. Remove or back it up before installing again.'
    );
}

$tempFile = tempnam(sys_get_temp_dir(), 'perkora-');

if ($tempFile === false) {
    fail('Could not create a temporary download file.');
}

$zip = null;
$installationStarted = false;

try {
    $url = 'https://api.github.com/repos/' .
        $repository .
        '/zipball/' .
        rawurlencode($ref);

    $fileHandle = fopen($tempFile, 'wb');

    if ($fileHandle === false) {
        throw new RuntimeException('Could not open temporary archive.');
    }

    $curl = curl_init($url);

    curl_setopt_array($curl, [
        CURLOPT_FILE => $fileHandle,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'Perkora-PHP-SDK-Installer',
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
        ],
    ]);

    $downloaded = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);
    fclose($fileHandle);

    if ($downloaded === false || $httpStatus !== 200) {
        throw new RuntimeException(
            'GitHub download failed. HTTP status: ' .
            $httpStatus .
            ($curlError !== '' ? '. ' . $curlError : '')
        );
    }

    $zip = new ZipArchive();

    if ($zip->open($tempFile) !== true) {
        throw new RuntimeException('Downloaded file is not a valid ZIP archive.');
    }

    // GitHub archives contain a single top-level directory.
    $prefix = null;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);

        if (
            is_string($name) &&
            preg_match('#^[^/]+/src/PerkoraClient\.php$#', $name)
        ) {
            $prefix = substr($name, 0, strpos($name, 'src/'));
            break;
        }
    }

    if ($prefix === null) {
        throw new RuntimeException(
            'The archive does not contain the expected Perkora SDK source.'
        );
    }

    $parentDirectory = dirname($installDirectory);

    if (
        !is_dir($parentDirectory) &&
        !mkdir($parentDirectory, 0755, true) &&
        !is_dir($parentDirectory)
    ) {
        throw new RuntimeException('Could not create the SDK installation directory.');
    }

    if (!mkdir($installDirectory, 0755, true)) {
        throw new RuntimeException('Could not create the SDK directory.');
    }

    $installationStarted = true;
    $written = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);

        if (
            !is_string($entry) ||
            !str_starts_with($entry, $prefix . 'src/')
        ) {
            continue;
        }

        $relative = substr($entry, strlen($prefix . 'src/'));

        if (
            $relative === '' ||
            str_ends_with($relative, '/') ||
            str_contains($relative, '\\') ||
            str_contains($relative, "\0")
        ) {
            continue;
        }

        $segments = explode('/', $relative);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new RuntimeException('Unsafe archive path detected.');
            }
        }

        $content = $zip->getFromIndex($i);

        if ($content === false) {
            throw new RuntimeException('Could not read an SDK source file.');
        }

        $destination = $installDirectory . '/src/' . $relative;
        $destinationDirectory = dirname($destination);

        if (
            !is_dir($destinationDirectory) &&
            !mkdir($destinationDirectory, 0755, true) &&
            !is_dir($destinationDirectory)
        ) {
            throw new RuntimeException('Could not create an SDK source directory.');
        }

        if (file_put_contents($destination, $content) === false) {
            throw new RuntimeException('Could not write an SDK source file.');
        }

        $written++;
    }

    if ($written === 0) {
        throw new RuntimeException('No SDK source files were installed.');
    }

    $autoload = <<<'AUTOLOAD'
<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Perkora\\SDK\\';
    $baseDirectory = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDirectory .
        str_replace('\\', '/', $relativeClass) .
        '.php';

    if (is_file($file)) {
        require $file;
    }
});
AUTOLOAD;

    if (
        file_put_contents(
            $installDirectory . '/autoload.php',
            $autoload
        ) === false
    ) {
        throw new RuntimeException('Could not create the SDK autoloader.');
    }

    $zip->close();
    $zip = null;

    echo "Perkora PHP SDK installed successfully.\n";
    echo "Files installed: {$written}\n";
    echo "Installation: {$installDirectory}\n";
    echo "\nLoad the SDK in your PHP application with:\n";
    echo "require_once __DIR__ . '/vendor/perkora/php-sdk/autoload.php';\n";

} catch (Throwable $exception) {
    if ($zip instanceof ZipArchive) {
        $zip->close();
    }

    if ($installationStarted) {
        removeDirectory($installDirectory);
    }

    fail($exception->getMessage());

} finally {
    if (is_file($tempFile)) {
        unlink($tempFile);
    }
}
