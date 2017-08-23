<?php

require_once "ide-core/session.inc.php";

class WorkSpace
{
  private $user;
  private $template;
  
  public function __construct(MikaSession $user)
  {
    $this->user=$user;
    $this->template=file_get_contents("ide-data/template.html");
  }
  
  public function getPage(array $vars=null)
  {
    $cproj=new Project(); //TODO must be able to select a project!
    if (empty($vars['filelist']))
    {
      $vars['filelist']=$cproj->fetchFiles($_GET['path']);
    }
    if (empty($vars['cfile']))
    {
      if (empty($_GET['file']))
      {
        //TODO HTML instructions for user to find and 'open' a file
      }
      else
      {
        $vars['cfile']=$cproj->openFile($_GET['file']);
      }
      
      return $this->replaceVars($vars);
    }
  }
  
  public function replaceVars(array $vars)
  {
    $html=$this->template;
    
    //TODO replace variables in template
    
    return $html;
  }
}

class Project
{
  
}