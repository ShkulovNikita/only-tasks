<?php

require_once 'classes/drive.php';

if (isset($_POST['fileForDelete'])) {
    Drive::deleteFile(htmlspecialchars($_POST['fileForDelete']));
}

header("Location: " . "index.php");
die();
