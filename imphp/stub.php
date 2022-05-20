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

 /*
  * This file is the default stub.php being used in all the IMPHP PHAR Archives.
  * This stub file is specific to the `imphp/*` packages and is not designed for general use.
  */

$meta = json_decode(file_get_contents("metadata"), false, 3, JSON_THROW_ON_ERROR);

if (!empty($meta->require)) {
    if (!class_exists("\\im\\Version", true)) {
        if (!is_file("Version.php") && defined("\\im\\IMPHP_BASE")) {
            throw new Exception("Cannot locate semantic version validator. Try updating imphp/base.");

        } else if (!is_file("Version.php")) {
            throw new Exception("Could not find package imphp/base");
        }

        require "Version.php";
    }

    foreach ($meta->require as $modName => $modVersion) {
        if (str_starts_with($modName, "imphp/")) {
            if (!defined(sprintf("\\im\\%s", str_replace("/", "_", strtoupper($modName))))) {
                throw new Exception("Could not find package $modName");

            } else if ($modVersion != null && $modVersion != "*" && !\im\Version::validate(constant(sprintf("\\im\\%s", str_replace("/", "_", strtoupper($modName)))), $modVersion)) {
                throw new Exception("The package $modName is to old. At least $modVersion is requried");
            }

        } else if (($ext = str_starts_with($modName, "ext-")) || $modName == "php") {
            if ($ext) {
                $modName = substr($modName, 4);
            }

            if ($modName == "php" || phpversion($modName)) {
                if ($modVersion != null && $modVersion != "*" && !\im\Version::validate(phpversion($modName == "php" ? null : $modName), $modVersion)) {
                    throw new Exception("The version of $modName is to old. At least version $modVersion is required");
                }

            } else {
                throw new Exception("The required PHP module $modName could not be found");
            }
        }
    }
}

if (!empty($meta->name) && !empty($meta->version)) {
    eval('namespace im; const ' . str_replace("/", "_", strtoupper($meta->name)) . ' = "' . $meta->version . '";');

} else {
    throw new Exception("Failed to set bootstrap constants");
}

if (!class_exists("\\im\\ImClassLoader", true)) {
    if (!is_file("ImClassLoader.php")) {
        throw new Exception("Cannot locate classloader");
    }

    require "ImClassLoader.php";
    \im\ImClassLoader::load();

} else {
    $loader = \im\ImClassLoader::load();
    $loader->addBasePath(__DIR__, "im");
}

if (!empty($meta->include)) {
    foreach ($meta->include as $file) {
        require $file;
    }
}
