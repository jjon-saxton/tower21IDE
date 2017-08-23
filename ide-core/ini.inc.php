<?php
class MikaINI
{
  private $file;
  private $sections;
  private $arr=array();

  public function __construct($name,$process_sections=false)
  {
   $file="./mi-data/".$name.".ini";
   $arr=parse_ini_file($file,$process_sections);

   $this->sections=$process_sections;
   $this->arr=$arr;
   $this->file=$file;

   return is_array($this->arr);
  }

  public function __get($key)
  {
   if ($this->sections)
   {
   }
   else
   {
     return $this->arr[$key];
   }
  }

  public function __set($key,$val)
  {
    if ($this->sections)
    {
      
    }
    else
    {
      $this->arr[$key]=$val;
    }
  }

  public function toArray()
  {
   return $this->arr;
  }
  
  public function update(array $arr)
  {
    $temp=array();
    foreach ($arr as $k=>$v)
    {
      if (array_key_exists($k,$this->arr))
      {
        $temp[$k]=$v;
      }
    }
    
    return file_put_contents($this->file,$this->build($temp));
  }
  
  public function save()
  {
    return file_put_contents($this->file,$this->build());
  }
  
  private function build($temp=null)
  {
    if (empty($temp))
    {
      $temp=$this->arr;
    }
    $txt=null;
    foreach ($temp as $k=>$v)
    {
      if (is_array($v))
      {
        $txt.="[{$k}]\n".$this->build($temp);
      }
      else
      {
        $txt.=$k." = \"{$v}\"\n";
      }
    }
    
    return $txt;
  }

  public function toHTML($format='form')
  {
    switch ($format)
    {
      case 'dl':
       if ($this->sections)
       {
       }
       else
       {
        $html="<dl>\n";
        foreach ($this->arr as $k=>$v)
        {
         $html.="<dt>{$k}</dt>\n<dd>{$v}</dd>\n";
        }
        $html.="</dl>\n";
       }
       break;
      case 'form':
      default:
        if ($this->sections)
        {
          
        }
        else
        {
          $html=<<<HTML
<form method="post">
<input type="hidden" name="ini" value="settings">
HTML;
          $data_info['url']=array('label'=>'URL');
          $data_info['root']=array('label'=>'Script Base');
          $data_info['docroot']=array('label'=>'Content Directory');
          $data_info['dataroot']=array('label'=>'Data Storage');
          $sets=$this->arr;
          foreach ($sets as $key=>$value)
          {
            $label=ucwords($key);
            $type="text";
            if (!empty($data_info[$key]['label']))
            {
              $label=$data_info[$key]['label'];
            }
            if (!empty($data_info[$key]['type']))
            {
              $type=$data_info[$key]['type'];
            }
            $html.="<label for=\"{$key}\">{$label}</label>\n<input type=\"{$type}\" id=\"{$key}\" name=\"{$key}\" class=\"form-control\" value=\"{$value}\">\n";
          }
          $html.="<div class=\"text-center\"><button type=\"submit\" class=\"btn btn-primary\">Save</button></div>\n</form>";
        }
    }
    return $html;
  }
}
