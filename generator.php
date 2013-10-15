<?php

/**
 * Model Generator
 * @version 0.1
 *
 * Generates model classes (with getters and setters) to use as an abstraction layer.
 * Check the README.md file for usage examples end mor information.
 *
 * @author Kleber C Batista <klebercal@gmail.com>
 * @link https://github.com/klebercal/model-generator
 * @license http://www.gnu.org/licenses/gpl.txt
 *
 * REQUIRES: 
 * PHP >= 5.3                 @see http://php.net/
 * PHP Command Line Interface @see http://www.php-cli.com/
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(PHP_SAPI !== 'cli') die('Please run generator in CLI mode');

// Terminate constants
define('TRM_DONE_THANKS'     , 1);
define('TRM_FEW_ARGUMENTS'   , 2);
define('TRM_HELP_REQUEST'    , 3);
define('TRM_INVALID_FILENAME', 4);
define('TRM_INVALID_OPTION'  , 5);
define('TRM_INVALID_SCHEMA'  , 6);
define('TRM_MALFORMED_SCHEMA', 7);
define('TRM_MISSING_FILE'    , 8);
define('TRM_MISSING_FILENAME', 9);
define('TRM_MISSING_SCHEMA'  , 10);
define('TRM_SYNTAX_ERRORS'   , 11);
define('TRM_UNKNOWN_COMMAND' , 12);
define('TRM_UNKNOWN_OPTION'  , 13);


// Checks the command arguments and gets the schema file
$file = check();
// Now validates the schema within the file
$schema = validate($file);
// Generates the models specified on the schema file
generate($schema);
// Greetings!
terminate(TRM_DONE_THANKS);

/**
 * Checks the argumments passed down to the command
 */
function check()
{
    global $argc, $argv;

    if(!preg_match("/^(-{1}[hs]|-{2}(help|schema-file))$/", $argv[1])) {
        terminate(TRM_UNKNOWN_OPTION);
    }

    switch($argc) {
        case 1:
            terminate(TRM_FEW_ARGUMENTS);
        break;

        case 2:
            if(preg_match("/^(-{1}h|-{2}help)$/", $argv[1])) {
                terminate(TRM_HELP_REQUEST);
            } elseif(preg_match("/^(-{1}s|-{2}schema-file)$/", $argv[1])) {
                terminate(TRM_MISSING_FILENAME);
            } else {
                terminate(TRM_UNKNOWN_COMMAND);
            }
        break;

        case 3:
            if (preg_match("/^(-{1}s|-{2}schema-file)$/", $argv[1])) {
                if(preg_match("/^[A-Za-z0-9._-]+\.(php)$/", $argv[2])) {
                    return $argv[2];
                } else {
                    terminate(TRM_INVALID_FILENAME);
                }
            } else {
                terminate(TRM_UNKNOWN_COMMAND);
            }
        break;
    
        default:
            terminate(TRM_UNKNOWN_COMMAND);
        break;
    }
}

/**
 * Validates the file and the defined schema
 */
function validate($file)
{
    // Checks if file exists
    if(!file_exists($file)) terminate(TRM_MISSING_FILE);
    // Checks for syntax errors on the schema file
    exec("php -l $file 2>&1", $output, $status);
    if($status !== 0) terminate(TRM_SYNTAX_ERRORS);
    // Finally requires the file
    require $file;
    
    // Checks if the var "$schema" was defined
    if(!isset($schema)) terminate(TRM_MISSING_SCHEMA);
    // Checks if the var "$schema" is an array
    if(!is_array($schema)) terminate(TRM_INVALID_SCHEMA);

    // Right. Now let's see if the array is correctly formed
    foreach($schema as $class_name => $class_info) {
        if(!array($class_info)) {
            terminate(TRM_MALFORMED_SCHEMA, "Class $class_name information must be an array");
        }
    }

    return $schema;
}

/**
 * Generates the models
 * v0.1 - Support for plain PHP arrays
 *
 * @todo Add YAML support
 * @todo Add JSON support
 * @todo Add native type support and validation on getters and setters
 */
function generate($schema)
{
    // Awesome! Now just read the schema and write the model files
    foreach($schema as $class_name => $class_props) {
        $class_name = camelize($class_name);

        // Generates the header and the CLASS syntax
        $header = <<<HEADER
<?php

/**
 * $class_name
 *
 * Code automatically written by Model Generator
 * @link https://github.com/klebercal/model-generator
 *
 * @author Kleber C Batista <klebercal@gmail.com>
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version 0.1
 */
class $class_name 
{
HEADER;
        // Resets utils variables
        $properties = array();
        $methods    = array();

        foreach($class_props as $prop_name) {
            // Generates the property declaration
            $properties[] = <<<PROPS
    /** $prop_name */
    private \$$prop_name;
PROPS;

            $method_name = camelize($prop_name);

            // Generates the SETTER method syntax
            $methods[] = <<<SETTER
    /** Setter method for '$prop_name' */
    public function set$method_name(\$$prop_name) 
    {
        \$this->$prop_name = \$$prop_name;
    }
SETTER;

            // Generates the GETTER method syntax
            $methods[] = <<<GETTER
    /** Getter method for '$prop_name' */
    public function get$method_name() 
    {
        return \$this->$prop_name;
    }
GETTER;
            
            // Generates the footer
            $footer = <<<FOOTER
} // End of $class_name
FOOTER;


        }
        // Now concatenates all the code and puts it into the file
        $properties = implode("\n\n", $properties);
        $methods    = implode("\n\n", $methods);

        // Writes the file
        file_put_contents("$class_name.class.php", "$header\n$properties\n\n$methods\n$footer", LOCK_EX);
    }
}

/**
 * Terminates the script and shows the desired message
 *
 * v0.1 - Personalized messages according to the code
 */
function terminate($code, $extra=null)
{
    global $argv;

    $self    = "generator:";
    $help    = "Try --help for more information";
    $command = implode(' ', $argv);

    $out = '';
    switch($code) {
        case TRM_DONE_THANKS:
            $out.= "$self Done! Thanks for using!";
        break;

        case TRM_FEW_ARGUMENTS:
            $out.= "$self Too few arguments.\n$help.";
        break;

        case TRM_HELP_REQUEST:
            $out.= help();
        break;

        case TRM_INVALID_FILENAME:
            $out.= "$self Invalid filename '{$argv[2]}'.\n$help.";
        break;

        case TRM_INVALID_OPTION:
            $out.= "$self Invalid option '{$argv[1]}'.\n$help.";
        break;

        case TRM_INVALID_SCHEMA:
            $out.= "$self The variable \$schema MUST BE an array.";
        break;

        case TRM_MISSING_FILE:
            $out.= "$self The schema file you informed ({$argv[2]}) does not exist.";
        break;

        case TRM_MALFORMED_SCHEMA:
            $out.= "$self The schema has an error: $extra.";
        break;

        case TRM_MISSING_FILENAME:
            $out.= "$self Missing schema file name.\n$help.";
        break;

        case TRM_MISSING_SCHEMA:
            $out.= "$self The schema was not defined on the file you informed ({$argv[2]}).";
        break;

        case TRM_SYNTAX_ERRORS:
            $out.= "$self Syntax errors were found on the schema file ({$argv[2]}).";
        break;

        case TRM_UNKNOWN_COMMAND:
            $out.= "$self Unknown command: $command\n$help.";
        break;
        
        case TRM_UNKNOWN_OPTION:
            $out.= "$self Unknown option: {$argv[1]}\n$help.";
        break;
    }

    die("$out\n");
}

/**
 * Returns the help message
 *
 * @todo Option to override (or not) existent files
 */
function help()
{
    $help = <<<EOT
Model Generator - Version 0.1
-----------------------------
Generates model classes (with getters and setters) to use as an abstraction layer.
USAGE: php generator.php [OPTIONS] [SCHEMA_FILE]

    Available OPTIONS:

    -h, --help          Shows this help screen
    -s, --schema-file   Provides the file that holds the schema information (must be a .php file)
    
More info: <https://github.com/klebercal/model-generator>
Report bugs to: <klebercal@gmail.com>
EOT;

    return $help;
}

function camelize($value, $first_upper=true) 
{
    $lower  = strtolower($value);
    $words  = explode('_', $lower);

    array_walk($words, function(&$word, $i) use ($first_upper) { 
        if($i==0 && !$first_upper) return; $word = ucfirst($word); }
    );

    return implode('', $words);
}
