<?php
require_once "ide-core/ini.inc.php";
require_once "ide-core/csv.inc.php";
$cfg=new MikaINI('settings');

session_name($cfg->session);
session_start();

if (@$_SESSION['data'])
{
  $auth=unserialize($_SESSION['data']);
}
else
{
  $auth=new MikaSession();
  $_SESSION['data']=serialize($auth);
}

class MikaSession
{
  public $name;
  private $user;

  public function __construct()
  {
   $u=new MikaCSV('auth');
   $this->user=$u->getRow('guest','Handle');
   $this->name='guest';
  }

  public function __get($k)
  {
    return $this->user->$k;
  }

  public function login($name,$pass)
  {
   $u=new MikaCSV('auth');
   $user=$u->getRow($name,'Handle');

   if ($user->Password == crypt($pass,$user->Password))
   {
    $this->name=$name;
    $this->user=$user;

    return true;
   }
   else
   {
    return false;
   }
  }

  public function logout()
  {
   $this->name='guest';
   $u=new MikaCSV('auth');
   $this->user=$u->getRow('guest','Handle');

   return true;
  }
}
