<?php
    
class DpZip
{
    private $_zip;

    public function __construct($path) {
        $this->_zip = new ZipArchive();
        $this->_path = $path;
        if(file_exists($path)) {
            $this->_zip->open($path);
        }
        else {
            $this->_zip->open($path, ZipArchive::CREATE);
        }
    }

    public function FileNames() {
        $ary = array();
        for($i = 0; $i < $this->_zip->numFiles; $i++) {
            $ary[] = $this->FileName($i);
        }
        return $ary;
    }
    public function FileName($i) {
        return $this->_zip->statIndex($i)["name"];
    }

    public function ExtractByName($name, $topath) {
        $this->_zip->extractTo($topath, $name);
    }
    public function ExtractByIndex($index, $topath) {
        $name = $this->FileName($index);
        $this->_zip->extractTo($topath, $name);
    }

    public function ExtractAll($topath) {
        if(! is_dir($topath) ) {
            die("Zip destination not a directory.");
        }
        $this->_zip->extractTo($topath);
    }
}
?>
