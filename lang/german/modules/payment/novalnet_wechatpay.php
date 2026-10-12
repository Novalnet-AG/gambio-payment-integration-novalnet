<?php

include_once(dirname(__FILE__).'/novalnet.php');
$currentFile = basename(__FILE__);
$fileNameWithoutExtension = pathinfo($currentFile, PATHINFO_FILENAME);
loadNovalnetLanguagePhrases($fileNameWithoutExtension . '.lang.inc.php');