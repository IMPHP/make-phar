#!/usr/bin/env php
<?php

if (!isset($argv)) {
    echo "E: This must be run from CLI", "\n"; exit(1);
}

$archive = $argv[1] ?? null;
$file = $argv[2] ?? null;

if (isset($file)) {
    if (str_starts_with($file, "./") || $file == ".") {
        $file = substr($file, 1);
    }

    $file = trim($file, "/");
}

if (!isset($archive) || !is_file($archive)) {
    echo sprintf("Usage: %s <archive> [<file/dir>]", basename($argv[0])), "\n";
    echo "------\n";
    echo "Specify a file to dump it's content", "\n\n";

    exit(1);

} else if (isset($file) && is_file("phar://{$archive}/{$file}")) {
    echo "Dump: {$file}\n";
    echo "------\n";
    echo file_get_contents("phar://{$archive}/{$file}");

} else if (isset($file) && !is_dir("phar://{$archive}/{$file}")) {
    echo "E: The path {$file} cannot be found", "\n"; exit(1);

} else {
    $phar = new Phar($archive);
    $itt = new RecursiveIteratorIterator($phar);

    if ($phar->hasMetadata() && !isset($file)) {
        echo sprintf("Usage: %s <archive> [<file/dir>]", basename($argv[0])), "\n";
        echo "------\n";
        echo "Specify a file to dump it's content", "\n\n";

        echo "Metadata\n";
        echo "------\n";

        print_r($phar->getMetadata());

        echo "\n";

    } else if (!empty($file)) {
        $file .= "/";
    }

    echo "Files\n";
    echo "------\n";

    foreach ($itt as $path) {
        if (!isset($file) || str_starts_with($path, "phar://{$archive}/{$file}")) {
            echo substr($path, strlen("phar://{$archive}")+1) . "\n";
        }
    }
}
