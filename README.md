Model Generator
================

Generates model classes (with getters and setters) to use as an abstraction layer.

Requirements
============

* PHP >= 5.3 [http://php.net/]
* PHP Command Line Interface [http://www.php-cli.com/]


Usage
=====

* Get the code...
* Create a symbolic link (on *nix) to the file 'generator.php' into the directory where you want your model classes to be generated.
* Or simply copy 'generator.php' anywhere in your filesystem (some folder where you can generate and then move the generated files).
* Open your terminal (*nix users, as I still did not tested on Windows\Mac plataforms). 
* Run 'php generator.php --schema-file FILE\_NAME.php' (OR 'php generator.php -s FILE_NAME.php')
* You have to specify your model abstraction on an external file. See below for instructions.

For help and more information, run 'php generator.php --help' (OR 'php generator.php -h')


Model Abstraction
=================

For the Model Generator to read and create the class code, you have to create an external .php file.
(I pretend to add YAML and JSON support further).

The file name doesn't matter at all: you will have to specify it on the command line. So it's really up to you.

The only obligatory thing is that it must have a variable named $schema, which contains the array that abstracts your model, according to the example:

```
$schema = array(
    'Cars' => array(
        'id',
        'manufacturer_id',
        'name',
        'motor',
        'created_at',
        'updated_at',
    ),
    'Manufacturer' => array(
        'id',
        'name',
        'country',
        'created_at',
        'updated_at',
    )
);
```
If you use related objects (or embedded documents), just name the property with the same name of the related-embedded class:

```
$schema = array(
    'Cars' => array(
        'id',
        'Manufacturer',
        'name',
        'motor',
        'created_at',
        'updated_at',
    ),
    'Manufacturer' => array(
        'id',
        'name',
        'country',
        'created_at',
        'updated_at',
    )
);


More Information
================

Copyright (c) 2013 Kleber C Batista (klebercal@gmail.com)                             
This project is licensed under the "GNU GPLv3" license (see LICENSE.txt).
