<?php

/**
 * Check extension sources without loading an installed iTop instance.
 * Run with PHP 8.3 or later: php tests/check-source.php.
 */

declare(strict_types=1);

// Module registrations are evaluated in a separate process for each module.
if (($argv[1] ?? '') === '--module') {
    class ModuleInstallerAPI
    {
    }

    class SetupWebPage
    {
        public static int $registrations = 0;

        public static function AddModule(string $file, string $id, array $metadata): void
        {
            $required = [
                'label', 'category', 'dependencies', 'mandatory', 'visible',
                'datamodel', 'webservice', 'data.struct', 'data.sample',
                'doc.manual_setup', 'doc.more_information', 'settings',
            ];
            foreach ($required as $key) {
                if (!array_key_exists($key, $metadata)) {
                    throw new RuntimeException("$id: missing metadata key $key");
                }
            }
            foreach (['doc.manual_setup', 'doc.more_information'] as $key) {
                if (!is_string($metadata[$key])) {
                    throw new RuntimeException("$id: $key must be a string");
                }
            }
            self::$registrations++;
        }
    }

    require $argv[2];
    if (SetupWebPage::$registrations !== 1) {
        throw new RuntimeException('Expected one module registration per definition.');
    }
    exit(0);
}

$root = dirname(__DIR__);
$excluded = ['.git', '.tmp', 'node_modules', 'vendor', 'build', 'dist'];
$iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    static fn (SplFileInfo $file): bool => !$file->isDir()
        || !in_array($file->getFilename(), $excluded, true)
));
$files = [];
foreach ($iterator as $file) {
    if ($file->isFile() && !$file->isLink()) {
        $files[] = $file->getPathname();
    }
}
sort($files);
$counts = ['PHP' => 0, 'XML' => 0, 'modules' => 0];

function runCheck(array $command): void
{
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);
    if (!is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Source check failed.');
    }
}

foreach ($files as $file) {
    if (str_ends_with($file, '.php')) {
        runCheck([PHP_BINARY, '-l', $file]);
        $counts['PHP']++;
        if (preg_match('/^module\..+\.php$/', basename($file))) {
            runCheck([PHP_BINARY, __FILE__, '--module', $file]);
            $counts['modules']++;
        }
    }
    if (str_ends_with($file, '.xml')) {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = new DOMDocument();
        if (!$xml->load($file, LIBXML_NONET)) {
            foreach (libxml_get_errors() as $error) {
                fwrite(STDERR, "$file:$error->line: " . trim($error->message) . PHP_EOL);
            }
            throw new RuntimeException('XML syntax check failed.');
        }
        $counts['XML']++;
    }
}
foreach ($counts as $type => $count) {
    if ($count === 0) {
        throw new RuntimeException("No $type files checked.");
    }
    echo "$type: $count checked", PHP_EOL;
}
echo 'Source checks passed. XML schemas, iTop compilation, and runtime behavior were not validated.', PHP_EOL;
