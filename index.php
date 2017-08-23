<?php

require_once "ide-core/workspace.inc.php";
$workspace=new WorkSpace($auth);

switch ($_GET['action'])
{
  case 'view':
  default:
    print $workspace->getPage();
}
?>