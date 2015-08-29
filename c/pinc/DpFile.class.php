<?php
    
class DpFile
{
    private $_path;
    private $_info;
    private $_image_info;

    public function __construct($path) {
        $this->_path = $path;
        try {
            $this->_info = pathinfo($path);
            return;
        }
        catch(Exception $e) {
            StackDump();
        }
    }

    public function FileName() {
        return @$this->_info['basename'];
    }

    public function Extension() {
        return @$this->_info['extension'];
    }

    public function FileNameBase() {
        return @$this->_info['filename'];
    }

    public function DirPath() {
        return @$this->_info['dirname'];
    }

    public function FilePath() {
        return @realpath($this->_path);
    }

    public function Size() {
        return @filesize($this->FilePath());
    }

    public function Text() {
        return file_get_contents($this->_path);
    }

    public function Url() {
        return preg_replace("/^.*pgdpcanada.net/", "", $this->FilePath());
    }

    public function IsDirectory() {
        return is_dir($this->_path);
    }

    public function IsFile() {
        return is_file($this->_path);
    }

    public function IsImageFile() {
        if(empty($this->_image_info))
            $this->_image_info = getimagesize($this->_path);
        return $this->_image_info[0] > 0 
            && $this->_image_info[1] > 0;
    }

    public function Exists() {
        return ($this->FilePath() == "")
            ? false
            : is_file($this->FilePath());
    }

    public function Delete() {
        if($this->Exists()) {
            unlink($this->FilePath());
        }
    }
}
?>
