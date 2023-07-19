<?php

require_once 'classes/drive.php';

if (isset($_POST['download'])) {
    Drive::downloadFile(htmlspecialchars($_POST['download']));
}
