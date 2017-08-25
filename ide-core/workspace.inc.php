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
  
  public function getForm($formname=null)
  {
    $cfg=new IDEINI('settings');
    if (empty($_GET['modal']))
    {
      $html=$this->template;
    }
    else
    {
      $html=<<<HTML
<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
  <h4><var>title</var></h4>
</div>
<div class="modal-body"><var>form</var></div>
HTML;
    }
    
    switch ($formname)
    {
      case 'login':
        $vars['title']="Login";
        $vars['form']=<<<HTML
<form action="{$cfg->url}?action=login" method="post">
<label for="name">Handle</label>
<input type="text" name="Handle" id="name" class="form-control">
<label for="pass">Password</label>
<input type="password" name="Password" id="pass" class="form-control">
<hr>
<div class="text-center"><button type="submit" class="btn btn-primary">Login</button></div>
</form>
HTML;
        break;
    }
    
    return $this->replaceVars($vars,$html);
  }
  
  public function replaceVars(array $vars,$custhtml=null)
  {
    if (empty($custhtml))
    {
      $html=$this->template;
    }
    else
    {
      $html=$custhtml;
    }
    $cfg=new IDEINI('settings');
    $vars['sitename']=$cfg->name;
    if (empty($this->user->Type) || $this->user->Type == "guest")
    {
      $vars['userbtns']="<div id=\"MainToolbar\" class=\"btn-group\"><a href=\"{$cfg->url}?action=login&modal=1\" data-toggle=\"modal\" data-target=\"#AJAXModal\" id=\"login\" class=\"btn btn-default\">Login</a></div>\n";
    }
    else
    {
      $vars['userbtns']="<div id=\"MainToolbar\" class=\"btn-group\"></button></div>\n";
    }
    
    if (empty($vars['title']))
    {
      $vars['title']=$vars['sitename'];
    }
    
    $html=preg_replace_callback("#<var>(.*?)</var>#",function($match) use ($vars){
      if (empty($vars[$match[1]]))
      {
        return "<span class=\"text-warning\">{$match[1]} is not defined!</span>";
      }
      else
      {
        return $vars[$match[1]];
      }
    },$html);
    
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
    $html="<ul class=\"projects ulist list-unstyled\">\n";
    $q=$this->table->query();
    while ($row=$q->fetch())
    {
      $html.="<li>{$row->Name}</li>\n";
    }
    
    return $html.="</ul>\n";
  }
}