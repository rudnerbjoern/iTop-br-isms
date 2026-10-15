<?php

/** Verify dictionary checker failures using isolated synthetic dictionaries. */

declare(strict_types=1);

$fixture = dirname(__DIR__) . '/.tmp/dictionary-check-' . bin2hex(random_bytes(6));
$directory = $fixture . '/sample/dictionaries';
mkdir($directory, 0777, true);
file_put_contents(dirname($directory) . '/module.sample.php', '<?php');
$checker = __DIR__ . '/check-dictionaries.php';

function dictionary(string $locale, string $entries): string
{
    return "<?php Dict::Add('$locale', 'Language', 'Language', array($entries));";
}

$validEn = dictionary('EN US', "'Key' => 'Value', 'Help+' => ''");
$validDe = dictionary('DE DE', "'Key' => 'Wert', 'Help+' => ''");
$cases = [
    ['matching keys and optional empty values', $validEn, $validDe, null],
    ['duplicate array key', $validEn, dictionary('DE DE', "'Key' => 'A', 'Key' => 'B', 'Help+' => ''"), 'duplicate key'],
    ['duplicate registration', $validEn, $validDe . " Dict::Add('DE DE', '', '', array('Key' => 'Again'));", 'duplicate key across registrations'],
    ['missing German key', $validEn, dictionary('DE DE', "'Key' => 'Wert'"), 'missing de translations'],
    ['missing English key', dictionary('EN US', "'Key' => 'Value'"), $validDe, 'missing en translations'],
    ['wrong locale', $validEn, dictionary('EN US', "'Key' => 'Value', 'Help+' => ''"), 'expected locale DE DE'],
    ['non-string value', $validEn, dictionary('DE DE', "'Key' => 12, 'Help+' => ''"), 'must be strings'],
    ['missing dictionary', $validEn, null, 'missing de dictionary'],
];

try {
    foreach ($cases as [$name, $english, $german, $expected]) {
        file_put_contents($directory . '/en.dict.sample.php', $english);
        $deFile = $directory . '/de.dict.sample.php';
        if ($german === null) {
            if (is_file($deFile)) {
                unlink($deFile);
            }
        } else {
            file_put_contents($deFile, $german);
        }
        $process = proc_open(
            [PHP_BINARY, $checker, $fixture],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Could not launch checker.');
        }
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if (($expected === null && $exit !== 0)
            || ($expected !== null && ($exit === 0 || !str_contains($output, $expected)))) {
            throw new RuntimeException("$name failed: $output");
        }
        echo "Passed: $name", PHP_EOL;
    }
} finally {
    foreach (glob($directory . '/*.php') as $file) {
        unlink($file);
    }
    rmdir($directory);
    unlink($fixture . '/sample/module.sample.php');
    rmdir($fixture . '/sample');
    rmdir($fixture);
}
