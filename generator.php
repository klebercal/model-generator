<?php

/**
 * Model Generator
 * @version 0.4
 *
 * Generates model classes (with getters and setters) to use as an abstraction layer.
 * Check the README.md file for usage examples and more information.
 *
 * @author  Kleber C Batista <klebercal@gmail.com>
 * @link    https://github.com/klebercal/model-generator
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

###############################################################################
###################################################### MAIN APPLICATION FLOW ##
###############################################################################

// Terminate constants
define('TRM_DONE_THANKS'      , 1);
define('TRM_FEW_ARGUMENTS'    , 2);
define('TRM_HELP_REQUEST'     , 3);
define('TRM_INVALID_FILENAME' , 4);
define('TRM_INVALID_OPTION'   , 5);
define('TRM_INVALID_MODEL'    , 6);
define('TRM_MALFORMED_MODEL'  , 7);
define('TRM_MISSING_FILE'     , 8);
define('TRM_MISSING_FILENAME' , 9);
define('TRM_MISSING_MODEL'    , 10);
define('TRM_PERMISSION_DENIED', 11);
define('TRM_SYNTAX_ERRORS'    , 12);
define('TRM_UNKNOWN_COMMAND'  , 13);
define('TRM_UNKNOWN_OPTION'   , 14);

// Overwrite control constants
define('OVERWRITE_NONE'       , 15);
define('OVERWRITE_BASE'       , 16);
define('OVERWRITE_ALL'        , 17);

// Checks the command arguments and gets the model file
$file = check(); 
// Now validates the model within the file
$model = validate($file);
// Generates the models specified on the model file
generate();
// Greetings!
terminate(TRM_DONE_THANKS);


###############################################################################
###################################################### APPLICATION FUNCTIONS ##
###############################################################################
/**
 * Checks the argumments passed down to the command
 *
 * v0.4 Added overwrite confirmation options
 *
 * @todo Implement a path for the model file, so generator can run centralized
 *
 * @global int $argc
 * @global array $argv
 *
 */
function check()
{
    global $argc, $argv;

    if($argc > 1 && !preg_match("/^(-{1}[hm]|-{2}(help|model-file))$/", $argv[1])) {
        terminate(TRM_UNKNOWN_OPTION);
    }

    switch($argc) {
        case 1:
            terminate(TRM_FEW_ARGUMENTS);
        break;

        case 2:
            if(preg_match("/^(-{1}h|-{2}help)$/", $argv[1])) {
                terminate(TRM_HELP_REQUEST);
            } elseif(preg_match("/^(-{1}s|-{2}model-file)$/", $argv[1])) {
                terminate(TRM_MISSING_FILENAME);
            } else {
                terminate(TRM_UNKNOWN_COMMAND);
            }
        break;

        case 3:
        case 4:
            if (preg_match("/^(-{1}m|-{2}model-file)$/", $argv[1])) {
                if(preg_match("/^[A-Za-z0-9._-]+\.(php)$/", $argv[2])) {
                    if($argc === 4) {
                        if(preg_match("/^(-{1}b|-{2}overwrite-base)$/", $argv[3])) {
                            define('OVERWRITE', OVERWRITE_BASE);
                        }
                        if(preg_match("/^(-{1}o|-{2}overwrite-all)$/", $argv[3])) {
                            define('OVERWRITE', OVERWRITE_ALL);
                        }
                    } else {
                        define('OVERWRITE', OVERWRITE_NONE);
                    }
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
 * Validates the file and the defined model
 *
 * v0.1 - Added model file validation
 *
 * @param string $file : the name of the file which contains the array '$model'
 */
function validate($file)
{
    // Checks if file exists
    if(!file_exists($file)) terminate(TRM_MISSING_FILE);
    // Checks for syntax errors on the model file
    exec("php -l $file 2>&1", $output, $status);
    if($status !== 0) terminate(TRM_SYNTAX_ERRORS);
    // Finally requires the file
    require $file;
    
    // Checks if the var "$model" was defined
    if(!isset($model)) terminate(TRM_MISSING_MODEL);
    // Checks if the var "$model" is an array
    if(!is_array($model)) terminate(TRM_INVALID_MODEL);

    // Right. Now let's see if the array is correctly formed
    foreach($model as $class_name => $class_info) {
        if(!array($class_info)) {
            terminate(TRM_MALFORMED_MODEL, "Class $class_name information must be an array");
        }
    }

    return $model;
}

/**
 * Controls the creation of the model files
 *
 * v0.1 - Support for plain PHP arrays
 * v0.2 - Added support for related objects (or embedded documents)
 * v0.3 - Added support to one-to-many embedded documents
 * v0.4 - Added Base Classes and overwrite confirmation
 *
 * @todo Add YAML support
 * @todo Add JSON support
 * @todo Add native type support and validation on getters and setters
 *
 * @global array $model : The array that contais the model information 
 */
function generate()
{
    global $model;

    // START!
    print "generator: Starting process...\n";

    // Outputs the current OVERWRITE option
    $overwrite = 'The overwrite option is set to ';
    switch(OVERWRITE) {
        case OVERWRITE_NONE:
            $overwrite.= 'DO NO OVERWRITING!';
        break;
        case OVERWRITE_ALL:
            $overwrite.= 'OVERWRITE ALL FILES!';
        break;
        
        case OVERWRITE_BASE:
            $overwrite.= 'OVERWRITE ONLY BASE FILES!';
        break;
    }
    print "generator: $overwrite\n\n";
    // Wait for the user to read messages
    sleep(2);

    // Awesome! Now just read the model and write the model files
    foreach($model as $class_name => $class_props) {
        // Generates and writes the base class
        base_class($class_name, $class_props);
        // Generates and writes the main class
        main_class($class_name, $class_props);
    }
    // Cool down
    sleep(2);
}

/**
 * Creates and writes a main class
 * 
 * @param string $name
 * @param array $props
 *
 * @global array $model : The array that contais the model information
 */
function main_class($name, array $props)
{
    global $model;

    // Generates the class header and footer
    $header = class_header($name, false);
    $footer = class_footer($name, false);

    // Writes the class
    write_class($name, $header, $footer, false);
}

/**
 * Creates and writes a base class
 * 
 * @param string $name
 * @param array $props
 * 
 * @global array $model : The array that contais the model information
 */
function base_class($name, array $props)
{
    global $model;

    // Generates the class header
    $header = class_header($name, true);

    // Cycles through the defined properties
    foreach($props as $prop_name) {
    
        // Checks if the property is a related one-to-many object
        if(strpos($prop_name, '*') === 0) {
            $is_1_to_N = true;
            $prop_name = substr($prop_name, 1);
        } else {
            $is_1_to_N = false;
        }

        // Checks if the property is a related object (or embedded document)
        $is_embedded = array_key_exists($prop_name, $model) ? true : false;

        // Generates the properties and methods
        $properties[] = class_prop($prop_name, $is_embedded, $is_1_to_N);
        $methods[]    = class_prop_setter($prop_name, $is_embedded, $is_1_to_N);
        $methods[]    = class_prop_getter($prop_name, $is_embedded);

        // Generates the class footer
        $footer = class_footer($name, true);

    }
    write_class($name, $header, $footer, true, $methods, $properties);
}

/**
 * Writes the class code
 *
 * @param string $name
 * @param string $header
 * @param string $footer
 * @param boolean $is_base : TRUE for a base class; FALSE for a main class
 * @param array $methods
 * @param array $properties
 */
function write_class($name, $header, $footer, $is_base, array $methods=array(), array $properties=array())
{
    // Checks if the base directory exists and is creatable
    if($is_base && !is_dir('base')) mkdir('base');

    // Defines the file, path and description
    $file = ($is_base) ? "base/{$name}Base.class.php" : "{$name}.class.php";
    $desc = ($is_base) ? "Base class '{$name}Base'"   : "Class '{$name}'";

    // Checks the overwrite settings
    switch(OVERWRITE) {
        case OVERWRITE_NONE:
            if(file_exists($file)) {
                print "generator: Skipping file '{$file}' -- already exists\n";
                return;
            }
        break;

        case OVERWRITE_ALL:
            if(file_exists($file) && !is_writable($file)) terminate(TRM_PERMISSION_DENIED, $file);
        break;

        case OVERWRITE_BASE:
            if($is_base) {
                if(file_exists($file) && !is_writable($file)) terminate(TRM_PERMISSION_DENIED, $file);
            } else {
                if(file_exists($file)) {
                    print "generator: Skipping file '{$file}' -- already exists\n";
                    return;       
                }
            }
        break;
    }

    // Concatenates the properties and methods
    if(!empty($methods) && !empty($properties)) {
        // Concatenates the code
        $properties = implode("\n\n", $properties);
        $methods    = implode("\n\n", $methods);

        $props_and_methods = "$properties\n\n$methods";
    } else {
        $props_and_methods = '    // Your code here';
    }

    // Writes the file
    file_put_contents($file, "{$header}\n{$props_and_methods}\n{$footer}", LOCK_EX);

    // Done!
    print "generator: {$desc} written in file '{$file}'\n";
}

/**
 * Generates the class HEADER
 *
 * @param string $name : The class name
 * @param boolean $is_base : TRUE for a base class header; FALSE for a main class header
 *
 * @return string : The class header syntax
 */
function class_header($class_name, $is_base) 
{
    $class_doc = ($is_base) ? "{$class_name}Base"                : $class_name;
    $class_dec = ($is_base) ? "abstract class {$class_name}Base" : "class {$class_name} extends {$class_name}Base";

    return <<<HEADER
<?php

/**
 * $class_doc
 *
 * Code automatically written by Model Generator
 * @link https://github.com/klebercal/model-generator
 *
 * @author Kleber C Batista <klebercal@gmail.com>
 * @license http://www.gnu.org/licenses/gpl.txt
 * @version 0.4
 */
$class_dec
{
HEADER;
}

/**
 * Generates the class FOOTER
 * 
 * @param string $name : The class name
 * @param boolean $is_base : TRUE for a base class footer; FALSE for a main class footer
 *
 * @return string : The class footer syntax
 */
function class_footer($class_name, $is_base)
{
    $class_name = ($is_base) ? "{$class_name}Base" : $class_name;

    // Generates the footer
    return <<<FOOTER
}
FOOTER;
}

/**
 * Generates the class property syntax
 * 
 * @param string $prop_name
 * @param boolean $is_embedded
 * @param boolean $is_1_to_N
 *
 * @return string : The class property syntax
 */
function class_prop($prop_name, $is_embedded, $is_1_to_N)
{
    $annotation = ($is_embedded ? "Embedded "              : "") . $prop_name;
    $syntax     = ($is_1_to_N)  ? "\$$prop_name = array()" : "\$$prop_name";

    return <<<PROP
    /** $annotation */
    private $syntax;
PROP;
}

/**
 * Generates the SETTER method syntax
 * 
 * @param string $prop_name
 * @param boolean $is_embedded
 * @param boolean $is_1_to_N
 * 
 * @return string : The setter method syntax
 */
function class_prop_setter($prop_name, $is_embedded, $is_1_to_N)
{
    // Only camelizes properties that are not related-embedded
    $method_name = $is_embedded ? $prop_name : camelize($prop_name);

    $annotation = "Setter method for" . ($is_embedded ? " Embedded" : "") . " '$prop_name'";
    $set_syntax = $is_1_to_N ? "\$this->{$prop_name}[] = \$$prop_name" : "\$this->$prop_name = \$$prop_name";
    $typehint   = $is_embedded ? "$prop_name " : '';

    return <<<SETTER
    /** $annotation */
    public function set$method_name($typehint\$$prop_name) 
    {
        $set_syntax;
    }
SETTER;
}

/**
 * Generates the GETTER method syntax
 * 
 * @param string $prop_name
 * @param boolean $is_embedded
 * 
 * @return string : The getter method syntax
 */
function class_prop_getter($prop_name, $is_embedded)
{
    // Only camelizes properties that are not related-embedded
    $method_name = $is_embedded ? $prop_name : camelize($prop_name);

    $annotation = "Getter method for" . ($is_embedded ? " Embedded" : "") . " '$prop_name'";

    return <<<GETTER
    /** $annotation */
    public function get$method_name() 
    {
        return \$this->$prop_name;
    }
GETTER;
}

/**
 * Terminates the script and shows the desired message
 *
 * @param int $code : The code that represent an application final state
 * @param string $extra : Additional information to be printed on program termination
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
            $out.= "\n$self Done! Thanks for using!";
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

        case TRM_INVALID_MODEL:
            $out.= "$self The variable \$model MUST BE an array.";
        break;

        case TRM_MISSING_FILE:
            $out.= "$self The model file you informed ({$argv[2]}) does not exist.";
        break;

        case TRM_MALFORMED_MODEL:
            $out.= "$self The model has an error: $extra.";
        break;

        case TRM_MISSING_FILENAME:
            $out.= "$self Missing model file name.\n$help.";
        break;

        case TRM_MISSING_MODEL:
            $out.= "$self The model was not defined on the file you informed ({$argv[2]}).";
        break;

        case TRM_PERMISSION_DENIED:
            $out.= "$self File $extra is not writable. Check permissions.\n";
        break;

        case TRM_SYNTAX_ERRORS:
            $out.= "$self Syntax errors were found on the model file ({$argv[2]}).";
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
 * v0.1 - Added help option
 *
 * @todo Option to override (or not) existent files
 */
function help()
{
    $help = <<<EOT
Model Generator - Version 0.4
-----------------------------
Generates model classes (with getters and setters) to use as an abstraction layer.
USAGE: php generator.php -[OPTIONS] [model_FILE]

    Available OPTIONS:

    -h, --help              Shows this help screen
    -m, --model-file        Provides the file that holds the model information (must be a .php file)
    
    Available OPERATION MODIFIERS:

    -o, --overwrite-all     Overwrites all existing files specified on the [model_FILE]
    -b, --overwrite-base    Overwrites only the existing base models specified on the [model_FILE]
    
More info: <https://github.com/klebercal/model-generator>
Report bugs to: <klebercal@gmail.com>
EOT;

    return $help;
}
/**
 * Converts a string to CamelCase (or camelCase) =)
 *
 * @param string $string       : The string which will be converted
 * @param boolean $first_upper : Flag that defines if the first letter will be upper case (default: TRUE) 
 * 
 * @return string : CamelCase version of the input string
 */
function camelize($value, $first_upper=true) 
{
    $lower  = strtolower($value);
    $words  = explode('_', $lower);

    array_walk($words, function(&$word, $i) use ($first_upper) { 
        if($i==0 && !$first_upper) return; $word = ucfirst($word); }
    );

    return implode('', $words);
}
