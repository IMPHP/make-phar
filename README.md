# IMPHP - Make .phar
___

This is a build script that compiles a PHP project into a single `phar` archive.

### Usage

```php
./make-phar.php [options]
```

__Options__

| Option                | Default       | Description                                                               |
| --------------------- | ------------- | ------------------------------------------------------------------------- |
| --incl-file <file>    |               | Include an additional file from outside the --in directory                |
| --incl-key <key>      |               | Include an additional key from the composer.json file into the metadata   |
| --stub <file>         | stub.php      | Include a stub file                                                       |
| --metadata <json>     |               | Include additional metadata from a json string                            |
| --metafile <file>     |               | Include additional metadata from a file                                   |
| --vendor <dir>        |               | Include a vendor directory with project dependencies                      |
| --in <dir>            | src/          | Define the src directory to use                                           |
| --out <dir>           | releases/     | Define the dest directory to use                                          |
| --version <version>   |               | Define the project version                                                |
| --name <name>         |               | Define the project name                                                   |
| --composer <file>     | composer.json | Use a composer file containing version and name                           |
| --debug               |               | Enable debug e.g. don't strip PHP files                                   |

> Always run this script from inside the project root directory. This script uses `CWD` and not the script location as base directory for the build process. 

__Example 1__

```sh
$ ls -p
src/  vendor/  stub.php

$ make-phar --name imphp/test --verson 0.0.1 --vendor vendor/
```

This will create the archive `releases/imphp-test.phar`.

> Note that src/ is automatically included as it's the default source directory in the build script.

__Example 2__

```sh
$ ls -p
src/  vendor/  stub.php  composer.json

$ cat composer.json
{
    "name": "imphp/test",
    "version": "0.0.1",
}

$ make-phar --vendor vendor/
```

### Metadata

By default the build script creates 3 metadata variables. These are added as metadata to the archive itself but they are also added as `json` to a file named `metadata` within the archive, making them accessable to the code within.

You can add additional data to the `metadata` file, though not to the archive metadata. One way is to simply include a complete `json` metadata file to the build. This will include everything from that file. The other way is to add a `json` string to the build line and the 3'rd way is to extract additional information from the `composer.json` file and have that be included.

__Example: Include metadata file__

```sh
$ ls -p
src/  vendor/  stub.php  metadata.json

$ cat metadata.json
{
    "key": "val"
}

$ make-phar --name imphp/test --verson 0.0.1 --vendor vendor/ --metafile metadata.json
```

__Example: Include metadata string__

```sh
$ ls -p
src/  vendor/  stub.php

$ make-phar --name imphp/test --verson 0.0.1 --vendor vendor/ --metadata '{"key": "val"}'
```

__Example: Include metadata from composer.json__

```sh
$ ls -p
src/  vendor/  stub.php  composer.json

$ cat composer.json
{
    "name": "imphp/test",
    "version": "0.0.1",

    "authors": [
        {
            "email": "test@imphp.myorg"
        }
    ]
}

$ make-phar --vendor vendor/ --incl-key authors.0.email:auth_email
```

This part needs a bit of explaining. We do not want to include the entire `authors` array in this example. All we want is the first email there is, so we instruct the script to get `email` from `authors.0`. Also in this example we want to rename the key from `email` to `auth_email`, so we write `email:auth_email` instead of just `email`.

__Example: Using the metadata from inside the archive__

```sh
$ ls -p
src/  vendor/  stub.php  composer.json

$ cat stub.php
<?php
json = json_decode(file_get_contents("metadata"));
print_r(json);
```

When we run the archive with this `stub.php`, we get the following output, based on the previous example.

```sh
Array
(
    [type] => release
    [name] => imphp/test
    [version] => 0.0.1
    [auth_email] => test@imphp.myorg
)
```

### Debug

You can specify the `--debug` option to create a debug version. The difference is that in normal builds, the build script will strip all whitespace and comments from any `PHP` file. This however is not great for debugging as things like line information becomes useless.

__ls-phar.php__

There is a small script included called `ls-phar.php`. It's purpose is simply to dump different information about an archive. It can list all the files in the archive, any metadata and dump content from files inside the archive.
