<?php
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup();
$sql = "SELECT * FROM ".USERS_TABLE;
$result = $db->sql_query($sql);
$rows = $db->sql_fetchrowset($result);
var_dump($rows);




