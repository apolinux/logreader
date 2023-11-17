<?php 

namespace Apolinux\LogReader ;

use Apolinux\Logreader\LogReaderException;

class LogReader{

  private $xmllint_cmd ;

  public function read($filename, $json_pretty=false, $xml_pretty=false, $show_errors=true){
    if(is_string($filename)){
      if(! file_exists($filename)){
        throw new FileNotFoundException($filename);
      }
      if(! $file=fopen($filename, 'r+')){
        throw new LogReaderException("Can't open file '$filename'");
      }
    }elseif(is_resource($filename)){
      $file = $filename ;
    }else{
      throw new LogReaderException("'$filename' is not a resource or a string filename");
    }

    $json_options = JSON_UNESCAPED_SLASHES ;
    if($json_pretty){
      $json_options |= JSON_PRETTY_PRINT;
    }
    $this->xmllint_cmd = $this->parseXmlExists();

    while(! feof($file)){
      $line = fgets($file, 4096);
      $line = trim($line);
      if(empty($line)){
        continue ;
      }
      $line_conv = $this->readLine($line);
      $out = json_encode($line_conv,$json_options);
      $out = $this->adaptText($out, $json_pretty, $xml_pretty, $show_errors);
      yield $out ;
    }
  }

  private function parseXmlExists()  {
    return `which xmllint`;
  }

  private function adaptText($out, $json_pretty, $xml_pretty, $show_errors=true){
    if($this->xmllint_cmd && $xml_pretty){
      $xmllint_cmd = trim($this->xmllint_cmd);
      $out = $this->adaptXml($out,$xmllint_cmd, $show_errors);
    }

    $out = $this->adaptJson($out, $json_pretty);

    return $out; 
  }

  private function adaptJson($out, $json_pretty=false){
    $json_options=null;
    if($json_pretty){
      $json_options = JSON_PRETTY_PRINT;
    }
    $out2=preg_replace_callback(
      '/"(\{.*\})"/',
      function($matches) use($json_options){
        $json = $matches[1];
        $json = str_replace('\"','"',$json);
        $data = json_decode($json);
        if($data === null){
          $data2= $matches[0] ;
        }else{
          
          $data2 = json_encode($data, $json_options);
        } 
        return $data2 ;
      },
      $out
    );
    return $out2 ;
  }

  private function adaptXml($out,$xmllint_cmd, $show_errors=true){
    $out2=preg_replace_callback(
      '/"(<.*?>)"/',
      function($matches) use ($xmllint_cmd, $show_errors){
        $xml = $matches[1];
        if($show_errors){
          $out=`echo "$xml"|$xmllint_cmd  --format -`;
        }else{
          $out=`echo "$xml"|$xmllint_cmd  --format - 2>/dev/null`;
        }
        if(trim($out)==''){
          return $matches[0] ;
        }
        return "\"$out\"";
      },
      $out
    );
    return $out2 ;
  }

  private function readLine(string $line){
    $parts = explode('|',$line);
    $out=$parts ;

    if(isset($parts[3])){
      $part3 = $parts[3];
      $part3_obj = json_decode($part3,true);
      if($part3_obj !== null){
        
        $out[3] = $part3_obj ;
      }
    }
    
    return $out ;
  }
}