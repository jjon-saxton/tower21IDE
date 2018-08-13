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
      case 'saveproject':
        $vars['title']="Save Project";
        if (empty($_GET['project']))
        {
          $np=new ProjectList($this->user);
          if ($item=$np->put($data))
          {
            $vars['message']="<div class=\"alert alert-info\">Your project has been added!</div>\n";
          }
          else
          {
            $vars['message']="<div class=\"alert alert-warning\">Could not add your project!</div>\n";
          }
        }
        else
        {
          $up=new Project($_GET['project'],$this->user);
          if ($item=$up->update($data))
          {
            $vars['message']="<div class=\"alert alert-info\">Your project information has been updated!</div>\n";
          }
          else
          {
            $vars['message']="<div class=\"alert alert-warning\">Could not updated project information!</div>\n";
          }
        }
        break;
      case 'delproject':
        $vars['title']="Delete Project";
        if (!empty($_GET['project']) || !empty($_POST['confirm']))
        {
          $proj=new Project($_GET['project'],$this->user);
          if ($proj->drop())
          {
            $vars['message']="<div class=\"alert alert-info\">Your project and its folder have been removed!</div>\n";
          }
          else
          {
            $vars['message']="<div class=\"alert alert-warning\">Could not remove your project or its folder!</div>\n";
          }
        }
        else
        {
          $vars['message']="<div class=\"alert alert-danger\">Could not continue, project number or confirmation was not set!</div>\n";
        }
        break;
      case 'newfolder':
        if (empty($_GET['project']))
        {
          $vars['title']="Create Folder?";
          $vars['message']="<div class=\"alert alert-danger\">A project must be selected so I know where to put your folder!</div>\n";
        }
        else
        {
          $cp=new Project($_GET['project'],$this->user);
          $vars['title']="Create Folder in ".$cp->Name;
          $cfg=new IDEINI('settings');
          if (mkdir($cfg->root.$cp->Folder."/".$_POST['path']))
          {
            header("Location: {$cfg->url}?project=".$cp->ID);
          }
          else
          {
            $vars['message']="<div class=\"alert alert-warning\">Could not create folder '{$cp->Folder}{$_POST['path']}'!</div>\n";
          }
        }
        break;
      case 'newfile':
        if (empty($_GET['project']))
        {
          $vars['title']="Create File?";
          $vars['message']="<div class=\"alert alert-danger\">A prject must be selected or I don't know where to put your file!</div>\n";
        }
        else
        {
          $cp=new Project($_GET['project'],$this->user);
          $vars['title']="Create new file in ".$cp->Name;
          $cfg=new IDEINI('settings');
          if (file_put_contents($cfg->root.$cp->Folder."/".$_POST['path'],"New Text"))
          {
            header("Location: {$cfg->url}?project={$cp->ID}&file=".$_POST['path']);
          }
          else
          {
            $vars['message']="<div class=\"alert alert-warning\">Could not create file '{$cp->Folder}{$_POST['path']}'!</div>\n";
          }
        }
        break;
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
    
    if (empty($_GET['modal']))
    {
      $vars['filelist']="<div class=\"alert alert-info\">Saving changes...</div>\n";
      $vars['cfile']=$vars['message'];
    }
    
    return $this->replaceVars($vars,$html);
  }
  
  public function getForm($formname=null)
  {
    switch ($formname)
    {
      case 'info':
        $proj=new Project($_GET['project'],$this->user);
        $parg="&project=".$_GET['project'];
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
        $vars['title']="Project Information";
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
<form action="{$cfg->url}?action=saveproject{$parg}" method="post">
<label for="name">Project Name</label>
<input type="text" name="Name" id="name" required="required" class="form-control" value="{$proj->Name}">
<label for="manager">Project Manager</label>
<select id="manager" name="Manager" required="required" class="form-control">{$mopts}</select>
<label for="type">Type</label>
<input type="text" maxlength="10" id="type" name="Type" class="form-control" value="{$proj->Type}">
<label for="folder">Folder</label>
<input type="text" id="folder" name="Folder" required="required" class="form-control" value="{$proj->Folder}">
<label for="git">GIT Push URI</label>
<input type="text" id="git" name="GIT" class="form-control" value="{$proj->GIT}">
<label for="desc">Description</label>
<textarea id="desc" rows="5" class="form-control" name="Description" placeholder="Enter project description...">{$proj->Description}</textarea>
<hr>
<div class="text-center"><button type="submit" class="btn btn-primary">Save</button></div>
</form>
HTML;
        break;
      case 'dropproject':
        $proj=new Project($_GET['project'],$this->user);
        $vars['title']="Drop Project - ".$proj->Name;
        $vars['form']=<<<HTML
<form action="{$cfg->url}?action=delproject&project={$proj->ID}" method="post">
<p>Are you sure you would like to remove the project '{$proj->Name}'? This process will also remove all files and sub-folders within the project folder (<code>{$proj->Folder}</code>). This action <strong>CANNOT</strong> be undone. Please backup in files or data you do not wish to lose <em>before</em> continuing.</p>
<div class="text-center"><input type="checkbox" name="Confirm" value=1 required="required" id="acknowledge"><label for="acknowledge"> I have read and understand the above</label><br />
<button type="submit" class="btn btn-danger">Continue?</button> <button type="button" data-dismiss="modal" class="btn btn-info">Cancel</button></div>
</form>
HTML;
        break;
      case 'newfolder':
      case 'newfile':
        $vars['title']="New File/Folder";
        $vars['form']=<<<HTML
<form action="{$cfg->url}?action={$_GET['section']}&project={$_GET['project']}" method="post">
<label for="path">Path</label>
<div class="input-group">
<input type="text" name="path" class="form-control" placeholder="full path relative to project folder for your new file or folder">
<span class="input-group-btn"><button type="submit" class="btn btn-primary">Create</button></span>
</div>
</form>
HTML;
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
<li><a href="./?section=info&project={$cp->ID}">Project Info</a></li>
<li><a href="./?section=dropproject&project={$cp->ID}&modal=1" data-target="#AJAXModal" data-toggle="modal">Remove Project</a></li>
<li><hr /></li>
<li><a href="./?section=newfolder&project={$cp->ID}&modal=1" data-target="#AJAXModal" data-toggle="modal">New Folder</a></li>
<li><a href="./?section=newfile&project={$cp->ID}&modal=1" data-target="#AJAXModal" data-toggle="modal">New File</a></li>
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
  
  public function infoToArray()
  {
    return $this->info;
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
  
  public function update($data)
  {
    $data['ID']=$_GET['project'];
    return $this->table->updateRow($data);
  }
  
  public function drop()
  {
    $cfg=new IDEINI('settings');
    if ($this->table->delRow($_GET['project'],'ID'))
    {
      return rmdirr($cfg->root.$this->info->Folder);
    }
    else
    {
      return false;
    }
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
  
  public function put($data)
  {
    $cfg=new IDEINI('settings');
    if (!empty($data['Folder']))
    {
      if (mkdir($cfg->root.$data['Folder']))
      {
        $start=<<<MARKDOWN
{$data['Name']}
===============
{$data['Description']}
MARKDOWN;
        file_put_contents($cfg->root.$data['Folder']."/README.md",$start);
        if ($item=$this->table->addRow($data))
        {
          return $item;
        }
        else
        {
          rmdir($cfg->root.$data['Folder']);
          return false;
        }
      }
      else
      {
        return false;
      }
    }
    else
    {
      return false;
    }
  }
}

function rmdirr($dir,$empty_only=false)
{
  rtrim($dir,"/");
  if (file_exists($dir) && is_readable($dir))
  {
    $handle=opendir($dir);
    while (FALSE !== ($item=readdir($handle)))
    {
      if ($item != '.' && $item != '..')
      {
	$path=$dir.'/'.$item;
	if (is_dir($path))
	{
	  rmdirr($path);
	}
	else
	{
	  unlink($path);
	}
      }
    }
    closedir($handle);
    
    if ($empty_only == FALSE)
    {
      if (!rmdir($dir))
      {
	trigger_error("Unable to remove folder!",E_USER_ERROR);
	return false;
      }
    }
    return true;
  }
  elseif (!file_exists($dir))
  {
    trigger_error("Directory '{$dir}' does not exists!",E_USER_ERROR);
    return false;
  }
  else
  {
    trigger_error("Directory '{$dir}' could not be opened for read!",E_USER_ERROR);
    return false;
  }
}