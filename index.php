<?php

require_once "ide-core/workspace.inc.php";
$workspace=new WorkSpace($auth);

switch ($_GET['action'])
{
  case 'login':
    if (empty($_POST['Handle']))
    {
      print $workspace->getForm('login');
    }
    elseif ($auth->login($_POST['Handle'],$_POST['password']))
    {
      header("Location: ./?author=".$auth->ID);
    }
    else
    {
      print $workspace->getError(403);
    }
    break;
  case 'logout':
    if ($auth->logout())
    {
      header ("Location: ./?action=login");
    }
    else
    {
      print $workspace->getError(500);
    }
  case 'view':
  default:
    print $workspace->getPage();
}
?>