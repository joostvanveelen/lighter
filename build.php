<?php

//Check php requirements
if (version_compare(PHP_VERSION, '7.2.0') === -1) {
    echo 'Lighter requires PHP 7.2 or higher.' . PHP_EOL;
    exit(1);
}
if (!extension_loaded('xml')) {
    echo 'Lighter requires the XML extension.' . PHP_EOL;
    exit(1);
}
if (!extension_loaded('mbstring')) {
    echo 'Lighter requires the mbstring extension.' . PHP_EOL;
    exit(1);
}
if (((bool)ini_get('phar.readonly')) !== false) {
    echo 'Building lighter requires phar.readonly to be set to Off.' . PHP_EOL;
    exit(1);
}

chdir(__DIR__);
$pharFile = 'build/lighter.phar';
if (file_exists($pharFile)) {
    unlink($pharFile);
}

echo 'Creating temporary build folder...';
if (!mkdir('build/tmp') && !is_dir('build/tmp')) {
    throw new \RuntimeException(sprintf('Directory "%s" was not created', 'build/tmp'));
}
echo ' Done!' . PHP_EOL;

echo 'Copying source files into temporary build folder...';
// Copy the source files into a temporary directory. This prevents unnecessary files being added to the phar file.
copyRecursive('src', 'build/tmp');
copy('composer.json', 'build/tmp/composer.json');
copy('composer.lock', 'build/tmp/composer.lock');
exec('cd build/tmp && composer install --no-dev');
unlink('build/tmp/composer.json');
unlink('build/tmp/composer.lock');
copy('index.php', 'build/tmp/index.php');
copy('logo.txt', 'build/tmp/logo.txt');
echo ' Done!' . PHP_EOL;

echo 'Building Phar file in "' . $pharFile .'"...';
$phar = new Phar($pharFile);
$phar->startBuffering();
$stub = '#!/usr/bin/php' . PHP_EOL . Phar::createDefaultStub('index.php');
$phar->buildFromDirectory('build/tmp');
$phar->setStub($stub);
$phar->stopBuffering();
echo ' Done!' . PHP_EOL;

echo 'Cleaning up...';
rmdirRecursive('build/tmp');
echo ' Done!' . PHP_EOL;

echo 'Compressing Phar file "' . $pharFile . '"...';
$phar->compressFiles(Phar::GZ);
echo ' Done!' . PHP_EOL;

echo 'Setting execute bits...';
chmod($pharFile, 0770);
echo ' Done!' . PHP_EOL;

echo 'Build complete!' . PHP_EOL;

/**
 * Copy a directory recursively, keeping the directory structure intact.
 *
 * @param string $source The directory holding the source files
 * @param string $destination The directory where everything should be copied into
 */
function copyRecursive($source, $destination)
{
    /** @var SplFileInfo[] $iterator */
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        $destinationPath = $destination . DIRECTORY_SEPARATOR . $file->getPath();
        if (!file_exists($destinationPath)) {
            if (!mkdir($destinationPath, 0777, true) && !is_dir($destinationPath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $destinationPath));
            }
        }
        if ($file->getExtension() === 'php') {
            file_put_contents(
                $destination . DIRECTORY_SEPARATOR . $file->getPathname(),
                php_strip_whitespace($file->getPathname())
            );
        } else {
            copy($file->getPathname(), $destination . DIRECTORY_SEPARATOR . $file->getPathname());
        }
    }
}

/**
 * Recursively delete a directory.
 *
 * @param string $directory
 */
function rmdirRecursive($directory)
{
    /** @var SplFileInfo[] $iterator */
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if (is_dir($file->getPathname())) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    rmdir($directory);
}
