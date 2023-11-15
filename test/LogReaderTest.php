<?php

use Apolinux\LogReader\LogReader;
use PHPUnit\Framework\TestCase;

class LogReaderTest extends TestCase{
  public function testReadLogOk(){
    $reader = new LogReader;
    //$filename = $this->generateFile();
    $filename = __DIR__ .'/fixtures/testA.log';
    $stream = $reader->read($filename,'',true,false);
    $out = [];
    foreach($stream as $line){
       $out[] = $line ;
    }
    $this->assertJson($out[0]);
  }
  
  private function generateFile(){
    $filename = fopen('php://memory','r+');
    fwrite($filename,"2023-|efch\n");
    fwrite($filename,"abcde|efch\n");
    rewind($filename);
    return $filename ;
  }
}