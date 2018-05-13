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
      $_SESSION['data']=serialize($auth);
      header("Location: ./");
    }
    else
    {
      print $workspace->getError(403);
    }
    break;
  case 'logout':
    if ($auth->logout())
    {
      $_SESSION['data']=serialize($auth);
      header ("Location: ./");
    }
    else
    {
      print $workspace->getError(500);
    }
  default:
    if (empty($_GET['action']))
    {
      print $workspace->getPage();
    }
    else
    {
      print $workspace->getReply($_GET['action'],$_POST);
    }
}
?>