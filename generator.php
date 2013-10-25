<?php

/**
 * Model Generator
 * @version 0.3
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

// Terminate constants
define('TRM_DONE_THANKS'     , 1);
define('TRM_FEW_ARGUMENTS'   , 2);
define('TRM_HELP_REQUEST'    , 3);
define('TRM_INVALID_FILENAME', 4);
define('TRM_INVALID_OPTION'  , 5);
define('TRM_INVALID_MODEL'  , 6);
define('TRM_MALFORMED_MODEL', 7);
define('TRM_MISSING_FILE'    , 8);
define('TRM_MISSING_FILENAME', 9);
define('TRM_MISSING_MODEL'  , 10);
define('TRM_SYNTAX_ERRORS'   , 11);
define('TRM_UNKNOWN_COMMAND' , 12);
define('TRM_UNKNOWN_OPTION'  , 13);


// Checks the command arguments and gets the model file
$file = check();
// Now validates the model within the file
$model = validate($file);
// Generates the models specified on the model file
generate($model);
// Greetings!
terminate(TRM_DONE_THANKS);

/**
 * Checks the argumments passed down to the command
 *
 * @todo Implement a path for the model file, so generator can run centralized
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
            if (preg_match("/^(-{1}m|-{2}model-file)$/", $argv[1])) {
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
 * Validates the file and the defined model
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
 * Generates the models
 * v0.1 - Support for plain PHP arrays
 * v0.2 - Added support for related objects (or embedded documents)
 * v0.3 - Added support to one-to-many embedded documents
 *
 * @todo Add YAML support
 * @todo Add JSON support
 * @todo Add native type support and validation on getters and setters
 */
function generate($model)
{
    // Awesome! Now just read the model and write the model files
    foreach($model as $class_name => $class_props) {
        print "generator: Writing model '$class_name'...";
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
 * @version 0.3
 */
class $class_name 
{
HEADER;
        // Resets utils variables
        $properties = array();
        $methods    = array();

        foreach($class_props as $prop_name) {
            // Checks if the property is a related one-to-many object
            $one_to_many = false;
            if(strpos($prop_name, '*') === 0) {
                $one_to_many = true;
                $prop_name = substr($prop_name, 1);
            }

            // Checks if the property is a related object (or embedded document)
            $related_embedded = array_key_exists($prop_name, $model) ? true : false;

            // Generates the property declaration
            $annotation  = ($related_embedded ? "Embedded " : "") . $prop_name;
            $prop_syntax = $one_to_many ? "\$$prop_name = array()" : "\$$prop_name";
            $properties[] = <<<PROPS
    /** $annotation */
    private $prop_syntax;
PROPS;
            // Only camelizes properties that are not related-embedded
            $method_name = $related_embedded ? $prop_name : camelize($prop_name);

            // Generates the SETTER method syntax
            $annotation = "Setter method for" . ($related_embedded ? " Embedded" : "") . " '$prop_name'";
            $set_syntax = $one_to_many ? "\$this->{$prop_name}[] = \$$prop_name" : "\$this->$prop_name = \$$prop_name";
            $typehint   = $related_embedded ? "$prop_name " : '';

            $methods[] = <<<SETTER
    /** $annotation */
    public function set$method_name($typehint\$$prop_name) 
    {
        $set_syntax;
    }
SETTER;

            // Generates the GETTER method syntax
            $annotation = "Getter method for" . ($related_embedded ? " Embedded" : "") . " '$prop_name'";
            $methods[] = <<<GETTER
    /** $annotation */
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
        
        // Done!
        print " done!\n";
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
            $out.= "$self Thanks for using!";
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
 * @todo Option to override (or not) existent files
 */
function help()
{
    $help = <<<EOT
Model Generator - Version 0.3
-----------------------------
Generates model classes (with getters and setters) to use as an abstraction layer.
USAGE: php generator.php [OPTIONS] [model_FILE]

    Available OPTIONS:

    -h, --help          Shows this help screen
    -m, --model-file   Provides the file that holds the model information (must be a .php file)
    
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
