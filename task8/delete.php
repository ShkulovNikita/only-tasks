<?php

require 'vendor/autoload.php';

use AppClasses\Drive;

if (isset($_POST['fileForDelete'])) {
    Drive::deleteFile(htmlspecialchars($_POST['fileForDelete']));
}

header("Location: " . "index.php");
die();
