<?php

require_once "ide-core/workspace.inc.php";
$workspace=new WorkSpace($auth);

if (empty($_GET['action']))
{
  print $workspace->getPage();
}
else
{
  print $workspace->getReply($_GET['action'],$_POST);
}
?>