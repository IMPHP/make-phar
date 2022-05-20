#!/usr/bin/env php
<?php declare(strict_types=1);
/*
 * This file is part of the imphp Project: https://github.com/IMPHP
 *
 * Copyright (c) 2022 Daniel Bergløv, License: MIT
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without restriction,
 * including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO
 * THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

if (!isset($argv) || !is_array($argv)) {
    echo "E: This script should only be run on CLI mode", "\n";
}

function help() {
    global $argv;

    echo sprintf("Usage: %s [options]", basename($argv[0])),           "\n\n";
    echo "--incl-file <file>:   - Include additional file",            "\n";
    echo "--incl-key <key>:     - Include key from composer.json",     "\n";
    echo "--stub <file>:        - Add stub file",                      "\n";
    echo "--metadata <json>:    - Add additional metadata",            "\n";
    echo "--metafile <file>:    - Add additional metadata",            "\n";
    echo "--vendor <dir>:       - Define vendor dir",                  "\n";
    echo "--in <dir>:           - Define source dir",                  "\n";
    echo "--out <dir>:          - Define destination dir",             "\n";
    echo "--version <version>:  - Define version",                     "\n";
    echo "--composer <file>:    - Use composer.json",                  "\n";
    echo "--debug:              - Don't strip PHP files",              "\n";

    exit(1);
}

$cfgWorkingDir = rtrim(realpath(getcwd()), DIRECTORY_SEPARATOR);
$cfgStubFile = sprintf("%s%s%s", $cfgWorkingDir, DIRECTORY_SEPARATOR, "stub.php");
$cfgInDir = sprintf("%s%s%s", $cfgWorkingDir, DIRECTORY_SEPARATOR, "src");
$cfgOutDir = sprintf("%s%s%s", $cfgWorkingDir, DIRECTORY_SEPARATOR, "releases");
$cfgComposer = sprintf("%s%s%s", $cfgWorkingDir, DIRECTORY_SEPARATOR, "composer.json");
$cfgVendor = null;
$cfgMetadata = null;
$cfgVersion = date("0.0.1+YmdHis");
$cfgPKG = basename($cfgWorkingDir);
$cfgDebug = false;
$cfgKeys = [];
$cfgFiles = [];

$meta = new stdClass();

/*
 *
 */

$argc = count($argv);
for ($i=1; $i < $argc; $i++) {
    $cur = $argv[$i];

    switch ($cur) {
        case "--incl-file":
            $file = $argv[++$i] ?? null;

            if ($file == null || !is_file($file)) {
                echo "E: The include file does not exist", "\n"; exit(1);
            }

            $cfgFiles[] = $file;

            break;

        case "--incl-key":
            $key = $argv[++$i] ?? null;

            if ($key == null) {
                echo "E: Missing key for --incl-key", "\n"; exit(1);
            }

            $cfgKeys[] = $key;

            break;

        case "--stub":
            $file = $argv[++$i] ?? null;

            if ($file == null || !is_file($file)) {
                echo "E: The stub file does not exist", "\n"; exit(1);
            }

            $cfgStubFile = $file;

            break;

        case "--composer":
            $file = $argv[++$i] ?? null;

            if ($file == null || !is_file($file)) {
                echo "E: The composer file does not exist", "\n"; exit(1);
            }

            $cfgComposer = $file;

            break;

        case "--metafile":
            $file = $argv[++$i] ?? null;

            if ($file == null || !is_file($file)) {
                echo "E: The metadata file does not exist", "\n"; exit(1);
            }

            $cfgMetadata = $file;

            break;

        case "--metadata":
            $data = $argv[++$i] ?? null;

            if ($key == null) {
                echo "E: Missing metadata", "\n"; exit(1);
            }

            $data = json_decode($data, false, 3, JSON_THROW_ON_ERROR);

            foreach ($data as $name => $value) {
                $meta->$name = $value;
            }

            break;

        case "--vendor":
            $dir = $argv[++$i] ?? null;

            if ($dir == null || !is_dir($dir) || !is_readable($dir)) {
                echo "E: Vendor directory does not exist or is not readable", "\n"; exit(1);
            }

            $cfgVendor = rtrim(realpath($dir), DIRECTORY_SEPARATOR);

            break;

        case "--in":
            $dir = $argv[++$i] ?? null;

            if ($dir == null || !is_dir($dir) || !is_readable($dir)) {
                echo "E: Source directory does not exist or is not readable", "\n"; exit(1);
            }

            $cfgInDir = rtrim(realpath($dir), DIRECTORY_SEPARATOR);

            break;

        case "--out":
            $dir = $argv[++$i] ?? null;

            if ($dir == null || (!is_dir($dir) && !mkdir($dir)) || !is_writable($dir)) {
                echo "E: Destination directory does not exist or is not writable", "\n"; exit(1);
            }

            $cfgOutDir = rtrim(realpath($dir), DIRECTORY_SEPARATOR);

            break;

        case "--version":
            $argVersion = $argv[++$i] ?? null;

            if ($argVersion == null) {
                echo "E: No version was provided", "\n"; exit(1);
            }

            break;

        case "--name":
            $argPKG = $argv[++$i] ?? null;

            if ($argPKG == null) {
                echo "E: No name was provided", "\n"; exit(1);
            }

            break;

        case "--debug":
            $cfgDebug = true;

            break;

        default:
            help();
    }
}


/*
 *
 */

if (!is_dir($cfgInDir) || !is_readable($cfgInDir)) {
    echo "E: Source directory does not exist or is not readable", "\n"; exit(1);

} else if ((!is_dir($cfgOutDir) && !mkdir($cfgOutDir)) || !is_writable($cfgOutDir)) {
    echo "E: Destination directory does not exist or is not writable", "\n"; exit(1);

} else if (is_file($cfgComposer)) {
    $json = json_decode(file_get_contents($cfgComposer), false);

    if (!isset($argPKG) && !empty($json->name)) {
        $argPKG = $json->name;
    }

    if (!isset($argVersion) && !empty($json->version)) {
        $argVersion = $json->version;
    }

    foreach ($cfgKeys as $key) {
        echo "Including Composer key $key", "\n";

        $value = $json;
        $name = null;
        $keys = explode(".", $key);

        foreach ($keys as $key) {
            if (($pos = strpos($key, ":")) !== false) {
                $name = substr($key, $pos+1);
                $key = substr($key, 0, $pos);

            } else {
                $name = $key;
            }

            if (is_numeric($key)) {
                if (!is_array($value) || !isset($value[$key])) {
                    continue 2;
                }

                $value =& $value[$key];

            } else {
                if (!is_object($value) || !isset($value->$key)) {
                    continue 2;
                }

                $value =& $value->$key;
            }
        }

        $meta->$name = $value;
    }
}

$cfgPKG = isset($argPKG) ? $argPKG : $cfgPKG;
$cfgVersion = isset($argVersion) ? $argVersion : $cfgVersion;
$cfgFile = sprintf("%s%s%s%s.phar", $cfgOutDir, DIRECTORY_SEPARATOR, str_replace("/", "-", $argPKG), $cfgDebug ? "@debug" : "");

if (is_file($cfgFile) && !unlink($cfgFile)) {
    echo "E: Failed to remove old ", $cfgDebug ? "debug" : "release", " build", "\n"; exit(1);
}


/*
 *
 */

$archiveDirs = [$cfgInDir];
if ($cfgVendor != null) {
    $archiveDirs[] = $cfgVendor;
}

$phar = new Phar($cfgFile, 0, basename($cfgFile));

foreach ($archiveDirs as $index => $dir) {
    echo "Compiling ", $dir, " into ", $cfgFile, "\n";

    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($dir, FilesystemIterator::FOLLOW_SYMLINKS|FilesystemIterator::SKIP_DOTS|FilesystemIterator::CURRENT_AS_PATHNAME),
                            RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $file) {
        if (is_file($file)) {
            if ($index > 0) {
                $local_file = "vendor/" . substr($file, strlen($dir)+1);

            } else {
                $local_file = substr($file, strlen($dir)+1);
            }

            echo "Adding ", $local_file, "\n";

            if (!$cfgDebug && str_ends_with($local_file, ".php")) {
                $phar->addFromString($local_file, php_strip_whitespace($file));

            } else {
                $phar->addFile($file, $local_file);
            }
        }
    }
}

foreach ($cfgFiles as $file) {
    echo "Adding additional file: ", basename($file), "\n";
    $phar->addFile($file, basename($file));
}

$bootstrap = <<<EOF
<?php
if (in_array('phar', stream_get_wrappers()) && class_exists('Phar', 0)) {
    Phar::interceptFileFuncs();
    Phar::mapPhar();
    %stub%

} else {
    throw new Exception(sprintf("Failed to utilize phar://%s. Missing Phar extension!", __FILE__));
}
__HALT_COMPILER();
EOF;

if (is_file($cfgStubFile)) {
    echo "Adding stub file", "\n";

    $phar->addFromString("stub.php", php_strip_whitespace($cfgStubFile));
    /*$phar->setStub(
        $phar->createDefaultStub("stub.php")
    );*/

    $phar->setStub(
        str_replace("%stub%", "include 'phar://' . __FILE__ . '/' . 'stub.php';", $bootstrap)
    );

} else {
    $phar->setStub(
        str_replace("%stub%", "", $bootstrap)
    );
}

echo "Configuring Metadata", "\n";
$meta->type = $cfgDebug ? "debug" : "release";
$meta->name = $cfgPKG;
$meta->version = $cfgVersion;

if ($cfgMetadata != null) {
    echo "Adding Metadata from $cfgMetadata", "\n";

    $metaFile = json_decode(file_get_contents($cfgMetadata), false, 3, JSON_THROW_ON_ERROR);

    foreach ($metaFile as $fname => $fvalue) {
        if (!isset($meta->$fname)) {
            $meta->$fname = $fvalue;
        }
    }
}

$phar->addFromString("metadata", json_encode($meta, 0, 3));
$phar->setMetadata([
    "name" => $cfgPKG,
    "version" => $cfgVersion,
    "type" => $cfgDebug ? "debug" : "release"
]);

echo "Successfully created $cfgFile", "\n";
