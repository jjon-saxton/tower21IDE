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
      $cproj=new Project($_GET['project'],$this->user);
      if (empty($vars['filelist']))
      {
        $vars['filelist']=$cproj->fetchFiles($_GET['path']);
      }
      if (empty($vars['cfile']))
      {
        if (empty($_GET['file']))
        {
          $vars['cfile']=$cproj->Description;
          if ($cproj->fileExists("/README.md"))
          {
            require_once('ide-core/markdown.inc.php');
            $markdown=$cproj->openFileRaw("/README.md");
            $vars['cfile'].="<hr />".Markdown($markdown);
          }
        }
        else
        {
          $vars['cfile']=$cproj->openFile($_GET['file']);
        }
      }
    }

    return $this->replaceVars($vars);
  }
  
  public function getReply($action,array $data)
  {
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
<div class="modal-body"><var>message</var></div>
HTML;
    }

    switch ($action)
    {
      case 'save':
        $vars['title']="Save File";
        if (empty($_GET['project']))
        {
          $vars['message']="<div class=\"alert alert-danger\">A project must be selected so I can no what file you are editing!</div>\n";
        }
        elseif (empty($_GET['file']))
        {
          $vars['message']="<div class=\"alert alert-danger\">No file selected!</div>\n";
        }
        else
        {
          $cp=new Project($_GET['project'],$this->user);
          if ($cp->saveFile($_GET['file'],$data['text']))
          {
            $vars['message']="<div class=\"alert alert-info\">Your changes were saved!</div>\n";
          }
          else
          {
            $vars['message']="<div class=\"alert alert-warning\">Could not save file! This is likely a server error, please try again.</div>\n";
          }
        }
    }
    
    return $this->replaceVars($vars,$html);
  }
  
  public function getForm($formname=null)
  {
    switch ($formname)
    {
      case 'addproject':
        $table=new CSV('auth');
        $aq=$table->query('Type=admin');
        if (!empty($aq))
        {
          $acand=$aq->fetchAll();
        }
        else
        {
          $acand=array();
        }
        $mq=$table->query('Type=manager');
        if (!empty($mq))
        {
          $mcand=$mq->fetchAll();
        }
        else
        {
          $mcand=array();
        }
        
        $ulist=array_merge($acand,$mcand);
        
        foreach ($ulist as $manager);
        {
          $mopts.="<option value=\"{$manager['ID']}\">{$manager['First']} {$manager['Last']} ({$manager['Handle']})</option>\n";
        }
        $vars['title']="Add Project";
        $vars['form']=<<<HTML
<script language="javascript" type="text/javascript">
$(function(){
  $('.form-control').focus(function(){
    var id=$(this).attr('id');
    var tip;
    switch (id){
      case 'name':
        tip="What should we call your project?";
        break;
      case 'manager':
        tip="Select the registered user who will manage this project.";
        break;
      case 'type':
        tip="Brief description of the type of project this is";
        break;
      case 'folder':
        tip="Root folder under which to store the files and folders for your project.";
        break;
      case 'git':
        tip="The URI where we will push git changes to, ignore if a git project is not set up or required.";
        break;
      case 'desc':
        tip="A description of your project. Most of this will be in your README, so please keep it clear and concise here.";
          break;
    }
    $("#helpTarget").text(tip);
  }).blur(function(){
    $("#helpTarget").text('Ready to submit form data?');
  });
});
</script>
<form action="{$cfg->url}?action=saveproject" method="post">
<label for="name">Project Name</label>
<input type="text" name="Name" id="name" required="required" class="form-control">
<label for="manager">Project Manager</label>
<select id="manager" name="Manager" required="required" class="form-control">{$mopts}</select>
<label for="type">Type</label>
<input type="text" maxlength="10" id="type" name="Type" class="form-control">
<label for="folder">Folder</label>
<input type="text" id="folder" name="Folder" required="required" class="form-control">
<label for="git">GIT Push URI</label>
<input type="text" id="git" name="GIT" class="form-control">
<label for="desc">Description</label>
<textarea id="desc" rows="5" class="form-control" name="Description" placeholder="Enter project description..."></textarea>
<hr>
<div class="text-center"><button type="submit" class="btn btn-primary">Add</button></div>
</form>
HTML;
        break;
    }
    
    $cfg=new IDEINI('settings');
    if (empty($_GET['modal']))
    {
      $html=$this->template;
      $vars['filelist']="<div id=\"helpTarget\" class=\"alert-info\">Form submission needed!</div>\n";
      $vars['cfile']="<h1>{$vars['title']}</h1>\n<p>{$vars['form']}</p>\n";
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
    if (@$this->user->Type == "guest")
    {
      $vars['userbtns']="<div id=\"MainToolbar\" class=\"btn-group\"><a href=\"mail:cem@tower21studios.com?subject=Dev+Access\" class=\"btn btn-danger\">Unauthorized!</a></div>\n";
    }
    else
    {
      $vars['userbtns']=<<<HTML
 <div id="MainToolbar" class="btn-group">
  <div class="btn-group">
  <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">{$this->user->name}<span class="caret"></span></button>
  <ul class="dropdown-menu">
    <li><a href="#">Your Settings</a></li>
    <li><a href="#">Contribute</a></li>
    <li><a href="#">Change Password</a></li>
  </ul>
  </div>
HTML;
      if ($this->user->Type == "manager" || $this->user->Type= "admin")
      {
       if (empty($_GET['project']))
       {
        $cpname="Projects";
        $cpopts=<<<HTML
<li><a href="#">Project Folders</a></li>
HTML;
       }
       else
       {
         $cp=new Project($_GET['project'],$this->user);
         $cpname=$cp->Name;
         $cpopts=<<<HTML
<li><a href="./{$cp->Folder}" target="_new">View Project</a></li>
<li><a href="#">Project Settings</a></li>
<li><a href="#">Remove Project</a></li>
HTML;
       }
       $vars['userbtns'].=<<<HTML
  <div class="btn-group">
  <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">{$cpname} <span class="caret"></span></button>
  <ul class="dropdown-menu">
    <li><a href="?section=addproject">Add Project</a></li>
    {$cpopts}
  </ul>
  </div>
HTML;
      }
      if ($this->user->Type == "admin")
      {
        $vars['userbtns'].="<a href=\"#\" class=\"btn btn-default\">Registrations</a> <a href=\"#\" class=\"btn btn-default\">IDE Settings</a>";
      }
      $vars['userbtns'].="</div>\n";
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
  private $table;
  private $user;
  private $info;
  
  public function __construct($id=1,IDESession $auth)
  {
    $table=new CSV('projects');
    $info=$table->getRow($id);
    
    $this->table=$table;
    if (empty($info))
    {
      return false;
    }
    else
    {
      $this->info=$info;
      $this->user=$auth;
      
      return true;
    }
  }
  
  public function __get($k)
  {
    return $this->info->$k;
  }
  
  public function getInfoByName($name)
  {
    if ($info=$this->table->getRow($name,"Name"))
    {
      $this->info=$info;
      return true;
    }
    else
    {
      return false;
    }
  }
  
  public function fetchFiles($path=null)
  {
    $cfg=new IDEINI('settings');
    $path=trim($path,"/");
    $fpath=$cfg->root.$this->info->Folder."/".$path;
    $html="<ul class=\"filelist\">\n";
    
    foreach(preg_grep("/^([^.])/",scandir($fpath)) as $item)
    {
      if (is_dir($fpath."/".$item))
      {
        $html.="<li class=\"list-item\"><span class=\"glyphicon glyphicon-folder-close\"></span> {$item}\n".$this->fetchFiles($path."/".$item)."</li>\n";
      }
      else
      {
        $html.="<li class=\"list-item\"><span class=\"glyphicon glyphicon-file\"></span> <a href=\"?project={$_GET['project']}&file={$path}/{$item}\">{$item}</a></li>\n";
      }
    }
    
    return $html."</ul>\n";
  }
  
  public function openFile($path)
  {
    $cfg=new IDEINI('settings');
    $path=ltrim($path,"/");
    $fpath=$cfg->root.$this->info->Folder."/".$path;
    $type=mime_content_type($fpath);
    list($main,$sub)=explode("/",$type);
    switch ($main)
    {
      case 'text':
        $txt=htmlspecialchars(file_get_contents($fpath));
        $ext=pathinfo($path,PATHINFO_EXTENSION);
        switch ($ext)
        {
          case 'js':
            $code="javascript";
            break;
          case 'html':
          case 'htm':
            $code="html";
            break;
          case 'md':
          case 'mmd':
            $code="markdown";
            break;
          default:
            $code=$ext;
        }
        $content=<<<HTML
<div id="operations" class="btn-group">
<button type="button" id="save" class="btn btn-primary">Save</button>
<button type="button" id="view" class="btn btn-success">Preview</button>
<button type="button" id="unlink" class="btn btn-danger">Delete!</button>
</div>
<div id="editor" class="preview text">{$txt}</div>
<form id="FinalText" action="./?action=save" method="post">
<input type="hidden" name="text" value="">
</form>
<script src="./ide-core/ace/ace.js"></script>
<script>
    var editor = ace.edit("editor");
    editor.setTheme("ace/theme/dreamweaver");
    editor.getSession().setMode("ace/mode/{$code}");
    editor.setKeyboardHandler("ace/keyboard/vim");
        
    $("#operations button#save").click(function(){
      var txt=editor.getValue();
      $.ajax({
        type: "POST",
        url: "./?action=save&modal=1&project={$_GET['project']}&file={$_GET['file']}",
        dataType: "html",
        data: {
          text: txt
        },
        success:function(data){
          $("#AJAXModal").find(".modal-content").html(data);
          $("#AJAXModal").modal('show');
        }
      })
    });
</script>
HTML;
        break;
      case 'image':
        switch ($sub)
        {
          case 'jpg':
          case 'png':
            $content="<div class=\"preview image\"><img src=\"{$cfg->url}{$this->info->Folder}/{$path}\"></div>\n";
            break;
          default:
            $content="<div class=\"alert alert-warning\">The image '{$path}' cannot be viewed in browser, '{$sub}' not supported!</div>\n";
        }
        break;
      case 'application':
      default:
        $content="<div class=\"alert alert-warning\">The file '{$path}' cannot be opened because there is no preview available for the type '{$type}'.</div>\n";
    }
    
    return $content;
  }
  
  public function openFileRaw($path)
  {
    $cfg=new IDEINI('settings');
    $path=ltrim($path,"/");
    $fpath=$cfg->root.$this->info->Folder."/".$path;
    $type=mime_content_type($fpath);
    list($main,$sub)=explode("/",$type);
    
    switch ($main)
    {
      case 'text':
        return file_get_contents($fpath);
        break;
      case 'image':
      default:
        return false;
    }
  }
  
  public function saveFile($path,$content)
  {
    $cfg=new IDEINI('settings');
    $path=ltrim($path,"/");
    $fpath=$cfg->root.$this->info->Folder."/".$path;
    
    return file_put_contents($fpath,$content);
  }
  
  public function fileExists($path)
  {
    $cfg=new IDEINI('settings');
    $path=ltrim($path,"/");
    $fpath=$cfg->root.$this->info->Folder."/".$path;
    
    return file_exists($fpath);
  }
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
    if($q=$this->table->query("Manager=".$this->user->ID))
    {
      while ($row=$q->fetch())
      {
        $proj=new Project(null,$this->user);
        $proj->getInfoByName($row->Name);
        $html.="<li><a href=\"?project={$proj->ID}\">{$proj->Name}</a></li>\n";
      }
    }
    else
    {
      $html.="<li class=\"text-warning\">You are not a part of any projects</li>\n";
    }
    
    return $html.="</ul>\n";
  }
}
