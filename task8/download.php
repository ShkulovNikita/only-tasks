<?php

require 'vendor/autoload.php';

use AppClasses\Drive;

if (isset($_POST['download'])) {
    Drive::downloadFile(htmlspecialchars($_POST['download']));
}
