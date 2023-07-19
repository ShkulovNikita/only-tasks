<?php
require_once 'classes/user.php';
User::logout();
header("Location: " . "index.php");
