<?php 

namespace Apolinux\LogReader ;

use Apolinux\Logreader\LogReaderException;

class LogReader{

  private $xmllint_cmd ;

  public function read($filename, $format, $ignore_errors=true, $pretty=true){
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
    if($pretty){
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
      $out = $this->adaptText($out);
      yield $out ;
    }
  }

  private function parseXmlExists()  {
    return `which xmllint`;
  }

  private function adaptText($out){
    if($this->xmllint_cmd){
      $xmllint_cmd = trim($this->xmllint_cmd);
      $out = $this->adaptXml($out,$xmllint_cmd);
    }

    $out = $this->adaptJson($out);

    return $out; 
  }

  private function adaptJson($out){
    $out2=preg_replace_callback(
      '/"(\{.*\})"/',
      function($matches) {
        $json = $matches[1];
        $json = str_replace('\"','"',$json);
        $data = json_decode($json);
        if($data === null){
          $data2= $matches[0] ;
        }else{
          $data2 = json_encode($data,JSON_PRETTY_PRINT);
        } 
        return $data2 ;
      },
      $out
    );
    return $out2 ;
  }

  private function adaptXml($out,$xmllint_cmd){
    $out2=preg_replace_callback(
      '/"(<.*>)"/',
      function($matches) use ($xmllint_cmd){
        //$xml=str_replace('\"','"',$matches[1]);
        $xml = $matches[1];
        
        $out=`echo "$xml"|$xmllint_cmd  --format -`;
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
        //array_walk($part3_obj, fn($item, $clave) => $this->parseJson($item, $clave));
        $out[3] = $part3_obj ;
      }
    }
    
    return $out ;
  }

  private function parseJson($item,$key){
    if(preg_match('/<?xml/',$item)){
      $objxml = simplexml_load_string($item);
      if($objxml !== false){
        $item = 'faltaaaaaaa';
      }
    }
  }
}