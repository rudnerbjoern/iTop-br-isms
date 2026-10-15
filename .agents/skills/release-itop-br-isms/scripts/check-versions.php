<?php

/** Check bundle module versions without loading iTop or changing files. */

declare(strict_types=1);

if (($argv[1] ?? '') === '--module') {
    class ModuleInstallerAPI
    {
    }

    class SetupWebPage
    {
        public static array $ids = [];

        public static function AddModule(string $file, string $id, array $metadata): void
        {
            self::$ids[] = $id;
        }
    }

    require $argv[2];
    if (count(SetupWebPage::$ids) !== 1) {
        throw new RuntimeException('Expected exactly one module registration.');
    }
    echo json_encode(SetupWebPage::$ids[0], JSON_THROW_ON_ERROR);
    exit(0);
}

$root = realpath($argv[1] ?? getcwd());
if ($root === false) {
    throw new RuntimeException('Repository root does not exist.');
}
$xml = new DOMDocument();
if (!$xml->load($root . '/extension.xml', LIBXML_NONET)) {
    throw new RuntimeException('Cannot read extension.xml.');
}
$versions = (new DOMXPath($xml))->query('/extension/version');
if ($versions->length !== 1) {
    throw new RuntimeException('Expected exactly one extension version.');
}
$version = trim($versions->item(0)->textContent);
if (!preg_match('/^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D', $version)) {
    throw new RuntimeException('Expected a stable MAJOR.MINOR.PATCH extension version.');
}
$files = glob($root . '/*/module.*.php');
$required = ['br-isms' => false, 'br-isms-catalog' => false];
$errors = [];
foreach ($files as $file) {
    $process = proc_open(
        [PHP_BINARY, __FILE__, '--module', $file],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException("Cannot inspect $file");
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $diagnostic = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException("Cannot inspect $file: $diagnostic $output");
    }
    $id = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    $expectedName = substr(basename($file), strlen('module.'), -strlen('.php'));
    if ($id !== "$expectedName/$version") {
        $errors[] = "$file: registered $id; expected $expectedName/$version";
    }
    if (array_key_exists($expectedName, $required)) {
        $required[$expectedName] = true;
    }
    echo "$file: $id", PHP_EOL;
}
foreach ($required as $name => $found) {
    if (!$found) {
        $errors[] = "Missing required bundle module: $name";
    }
}
if ($errors) {
    throw new RuntimeException(implode(PHP_EOL, $errors));
}
echo "All bundle module versions match extension.xml: $version", PHP_EOL;
