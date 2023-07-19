<?php

require 'vendor/autoload.php';

use AppClasses\User;

User::logout();
header("Location: " . "index.php");
