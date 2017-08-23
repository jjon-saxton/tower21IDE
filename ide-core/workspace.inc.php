<?php

require_once "ide-core/session.inc.php";

class WorkSpace
{
  private $user;
  private $template;
  
  public function __construct(IDESession $user)
  {
    $this->user=$user;
    $this->template=file_get_contents("ide-data/template.htm");
  }
  
  public function getPage(array $vars=null)
  {
    if (empty($_GET['project']))
    {
      $uprojs=new ProjectList($this->user);
      $vars['filelist']=$uprojs->fetchNames();
      $vars['cfile']="<p>Select a project to get started.</p>";
    }
    else
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
      }
    }

    return $this->replaceVars($vars);
  }
  
  public function replaceVars(array $vars)
  {
    $html=$this->template;
    $cfg=new IDEINI('settings');
    $vars['sitename']=$cfg->name;
    
    //TODO replace variables in template
    
    return $html;
  }
}

class Project
{
  
}

class ProjectList
{
  private $table;
  private $user;
  
  public function __construct(IDESession $auth)
  {
    $this->user=$auth;
    $this->table=new CSV('projects');
  }
  
  public function fetchNames()
  {
    $html="<ul class=\"projects ulist\">\n";
    $q=$this->table->query();
    while ($row=$q->fetch())
    {
      $html.="<li>{$row->Name}</li>\n";
    }
    
    return $html.="</ul>\n";
  }
}