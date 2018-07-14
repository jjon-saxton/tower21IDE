<?php
require_once "ide-core/ini.inc.php";
require_once "ide-core/csv.inc.php";
$cfg=new IDEINI('settings');

session_name($cfg->session);
session_start();

if (@$_SESSION['data'])
{
  $auth=unserialize($_SESSION['data']);
}
else
{
  $auth=new IDESession();
  if (!empty($_SERVER['PHP_AUTH_USER']))
  {
    $auth->login($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);
  }
  $_SESSION['data']=serialize($auth);
}

class IDESession
{
  public $name;
  private $user;

  public function __construct()
  {
   $u=new CSV('auth');
   $this->user=$u->getRow('guest','Handle');
   $this->name='guest';
  }

  public function __get($k)
  {
    return $this->user->$k;
  }

  public function login($name,$pass)
  {
   $u=new CSV('auth');
   $user=$u->getRow($name,'Handle');

   if (!empty($pass))
   {
    $this->name=$name;
    $this->user=$user;
     
    if (!empty($user->Site))
    {
      header ("Location: ./{$user->Site}");
    }

    return true;
   }
   else
   {
    return false;
   }
  }
}
