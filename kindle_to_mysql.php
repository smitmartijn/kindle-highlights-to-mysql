<?php

require 'config.inc.php';

require 'composer/vendor/autoload.php';
require 'parse_kindle_highlights.inc.php';


$OUTPUT_FILE = "output.html";


output("Executing amazon_login.js first, to download the highlights page from Amazon..");

// execute amazon_login.js via phantomjs and store the output in a variable
$cmd = $CONFIG['phantomjs_path']." ".__DIR__."/amazon_login.js ".$CONFIG['amazon_username']." ".$CONFIG['amazon_password']." ".$OUTPUT_FILE;
ob_start();
passthru($cmd);
$phantom_output = ob_get_clean();

// the human check in Amazons system sometimes catches us. Exit if it does.
if(preg_match("/Amazon blocked our login, exiting/", $phantom_output))
{
  output("Amazon blocked our login, can't continue!", "fail");
  output($phantom_output, "success");
  exit;
}

output($phantom_output, "success");

output("Parsing '".$OUTPUT_FILE."': storing the books and highlights in the database..");
parse_kindle_highlights($OUTPUT_FILE);

output("All done!", "success");

?>
