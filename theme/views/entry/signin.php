<?php if (!defined('APPLICATION')) exit();
$signinUrl = c('Garden.Authenticator.SignInUrl');
$locationHeader = "Location: " . $signinUrl;
header($locationHeader, true, 301);
