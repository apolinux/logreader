<?php 

namespace Apolinux\Logreader;

use Exception;

class FileNotFoundException extends Exception{
  public function __construct($filename)
  {
    parent::__construct("The file '$filename' does not exist",1);
  }
}