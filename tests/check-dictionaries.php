<?php

/**
 * Check English/German dictionary keys without loading iTop.
 * Run: php tests/check-dictionaries.php [repository-or-fixture-root].
 */

declare(strict_types=1);

$errors = [];

function reportError(string $message): void
{
    global $errors;
    $errors[] = $message;
}

class Dict
{
    public static string $file = '';
    public static string $locale = '';
    public static array $entries = [];
    public static int $calls = 0;

    public static function Add(string $locale, string $name, string $nativeName, array $entries): void
    {
        if ($locale !== self::$locale) {
            throw new RuntimeException(self::$file . ": expected locale " . self::$locale . ", got $locale");
        }
        foreach ($entries as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new RuntimeException(self::$file . ': dictionary keys and values must be strings');
            }
            if (array_key_exists($key, self::$entries)) {
                throw new RuntimeException(self::$file . ": duplicate key across registrations: $key");
            }
            self::$entries[$key] = $value;
        }
        self::$calls++;
    }
}

function readDictionary(string $file, string $locale): array
{
    // PHP overwrites duplicate keys inside an array before Dict::Add sees them.
    // Inspect literal keys first, ignoring comments and whitespace.
    $tokens = array_values(array_filter(
        token_get_all(file_get_contents($file), TOKEN_PARSE),
        static fn ($token): bool => !is_array($token)
            || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
    ));
    $seen = [];
    foreach ($tokens as $index => $token) {
        if (!is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING
            || ($tokens[$index + 1][0] ?? null) !== T_DOUBLE_ARROW) {
            continue;
        }
        // Only a PHP tokenizer-confirmed string literal is evaluated here.
        $key = eval('return ' . $token[1] . ';');
        if (isset($seen[$key])) {
            reportError("$file:{$token[2]}: duplicate key: $key (first line {$seen[$key]})");
        }
        $seen[$key] = $token[2];
    }
    Dict::$file = $file;
    Dict::$locale = $locale;
    Dict::$entries = [];
    Dict::$calls = 0;
    require $file;
    if (Dict::$calls === 0 || Dict::$entries === []) {
        throw new RuntimeException("$file: no dictionary entries registered");
    }
    if (count($seen) !== count(Dict::$entries)) {
        throw new RuntimeException("$file: use literal dictionary keys so duplicates can be checked");
    }
    return Dict::$entries;
}

$root = $argv[1] ?? dirname(__DIR__);
$modules = glob($root . '/*/module.*.php');
if (!$modules) {
    throw new RuntimeException('No module definitions discovered.');
}
$global = ['en' => [], 'de' => []];
$total = 0;
foreach ($modules as $module) {
    $directory = dirname($module);
    $translations = [];
    foreach (['en' => 'EN US', 'de' => 'DE DE'] as $language => $locale) {
        $files = glob($directory . "/dictionaries/$language.dict.*.php");
        if (!$files) {
            throw new RuntimeException("$directory: missing $language dictionary");
        }
        $translations[$language] = [];
        foreach ($files as $file) {
            foreach (readDictionary($file, $locale) as $key => $value) {
                if (isset($global[$language][$key])) {
                    throw new RuntimeException("$file: duplicate $language key $key; already in {$global[$language][$key]}");
                }
                $global[$language][$key] = $file;
                $translations[$language][$key] = $value;
                $total++;
            }
        }
    }
    foreach (['en' => 'de', 'de' => 'en'] as $source => $target) {
        $missing = array_diff_key($translations[$source], $translations[$target]);
        if ($missing) {
            reportError("$directory: missing $target translations: " . implode(', ', array_keys($missing)));
        }
    }
    echo basename($directory), ': ', count($translations['en']), ' English keys, ', count($translations['de']), ' German keys', PHP_EOL;
}
if ($errors) {
    foreach ($errors as $error) {
        fwrite(STDERR, $error . PHP_EOL);
    }
    exit(1);
}
echo "Dictionary checks passed: $total entries across ", count($modules), ' modules.', PHP_EOL;
