<?php
class CSV
{
  private $raw;
  private $file;
  private $cols=array();
  private $array=array();

  public function __construct($name,$process_headers=true)
  {
   $file="./ide-data/{$name}.csv";
   $raw=file_get_contents($file);

   $temp=array_map('str_getcsv',file($file));
   if ($process_headers)
   {
    $header=array_shift($temp);
    $this->cols=$header;

    array_walk($temp,'_combine_array',$header);
   }
    else
    {
      foreach ($temp as $k=>$v)
      {
        $this->cols[]=$k;
      }
    }
   $this->file=$file;

   return $this->array=$temp;
  }
  
  public function query($q=null,$sort=null,$limit=0,$offset=0)
  {
    $full=$this->array;
    parse_str($q,$pairs);
    $distilled=array_filter($full,function($vals) use ($pairs){
      $intersection=array_intersect_assoc($vals,$pairs);
      return (count($intersection) === count($pairs));
    });
    $distilled=array_values($distilled);
    
    //TODO further filter by $limit and $offset then sort by field defined in $sort
    
    if (empty($q) && empty($distilled))
    {
      return new CSVResult($full); //No query an no matches so use the entire array as result
    }
    elseif (empty($distilled))
    {
      return null; //query, but no matches, so return nothing
    }
    else
    {
      return new CSVResult($distilled); //Matches found so we will narrow it down to just the matches
    }
  }

  public function getRow($v,$col='ID')
  {
   $temp=$this->array;
   $r=0;
   foreach ($temp as $rows)
   {
    if ($rows[$col] == $v)
    {
      break;
    }
    $r++;
   }

   if (is_array($temp[$r]))
   {
    return new CSVRow($temp[$r]);
   }
   else
   {
    return false;
   }
  }
  
  public function updateRow(array $data)
  {
    $ur=array();
    $temp=$this->array;
    $pk=$this->cols[0];
    $r=0;
    
    foreach ($data as $field=>$val) //sort out unused keys
    {
      if (array_search($field,$this->cols) !== FALSE)
      {
        if (strtolower($field) == "password")
        {
          $ur[$field]=crypt($val);
        }
        else
        {
          $ur[$field]=$val;
        }
      }
    }
    
    foreach ($temp as $rows) //find a row to update
    {
      if ($rows[$pk] == $ur[$pk])
      {
        break;
      }
      $r++;
    }
    
    $this->array[$r]=array_merge($temp[$r],$ur); //update row
    return $this->overWrite(); //overwrite existing CSV
  }
  
  public function delRow($v,$col=null)
  {
    if (empty($col))
    {
      $col=$this->cols[0];
    }
    
    $temp=$this->array;
    $r=0;
    foreach ($temp as $rows)
    {
      if ($rows[$col] == $v)
      {
        break;
      }
      $r++;
    }
    
    if (is_array($temp[$r]))
    {
      unset($this->array[$r]);
      return $this->overWrite();
    }
    else
    {
      return false;
    }
  }

  public function addRow($data)
  {
   if (is_array($data))
   {
    $nr=array();
    foreach ($this->cols as $field)
    {
     if (array_key_exists($field,$data)) //use user-supplied data
     {
      if ($field == "Password")
      {
       $nr[$field]=crypt($data[$field]);
      }
      else
      {
       $nr[$field]=$data[$field];
      }
     }
     else //no user-supplied data, so use default
     {
      if ($field == "UID" || $field == "ID" || $field == "row")
      {
       $last=end($this->array);
       $id=$last[$field]+1;
       $nr[$field]=$id;
      }
      elseif ($field == "Group")
      {
       $nr[$field]="users";
      }
      else
      {
       $nr[$field]=null;
      }
     }
    }
    $f=fopen($this->file,"a");
    $this->array[]=$nr;
    if (fputcsv($f,$nr))
    {
      fclose($f);
      return new MikaCSVRow($nr);
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
  
  private function overWrite()
  {
    $f=fopen($this->file,"w");
    $written=false;
    
    if (!empty($this->cols))
    {
     fputcsv($f,$this->cols);
    }
    
    foreach ($this->array as $row)
    {
      $written=fputcsv($f,$row);
    }
    
    fclose($f);
    return $written;
  }
}

class CSVResult
{
  private $array=array();
  private $rowNum=0;
  
  public function __construct(array $result)
  {
    $this->array=$result;
  }
  
  public function fetch()
  {
    $k=$this->rowNum;
    $temp=$this->array;
    if (!empty($temp[$k]) && is_array($temp[$k]))
    {
      $this->rowNum=$k+1;
      return new CSVRow($temp[$k]);
    }
    else
    {
      $this->rowNum=0;
      return false;
    }
  }
  
  public function fetchAll()
  {
    return $this->array;
  }
}

class CSVRow
{
  private $array=array();

  public function __construct(array $row)
  {
   $this->array=$row;
  }

  public function __get($k)
  {
    return $this->array[$k];
  }
  
  public function toArray()
  {
    return $this->array;
  }
}

function _combine_array(&$row,$key,$header)
{
 if (count ($header) == count($row))
 {
  $row=array_combine($header,$row);
 }
  else
  {
    var_dump(count($header));
    var_dump(count($row));
  }
}

function array_to_csv(array $array)
{
  $csv=array();
  foreach ($array as $item)
  {
    if (is_array($item))
    {
     $csv[]=array_to_csv($item);
    }
    else
    {
     $csv[]=$item;
    }
  }
  return implode(",",$csv);
}
