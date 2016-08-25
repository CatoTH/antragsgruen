<?php

/**
 * Source: SETASIGN FPDI FAQ – Using a pdf from a php variable instead of a file
 * https://www.setasign.com/support/faq/miscellaneous/using-a-pdf-from-a-php-variable-instead-of-a-file/
 */

// @codingStandardsIgnoreFile

namespace app\components;

class VarStream
{
    private $_pos;
    private $_stream;
    private $_cDataIdx;

    static protected $_data    = array();
    static protected $_dataIdx = 0;

    static function createReference(&$var)
    {
        $idx               = self::$_dataIdx++;
        self::$_data[$idx] =& $var;
        return str_replace(__NAMESPACE__ . '\\', '', __CLASS__) . '://' . $idx;
    }

    static function unsetReference($path)
    {
        $url      = parse_url($path);
        $cDataIdx = $url["host"];
        if (!isset(self::$_data[$cDataIdx]))
            return false;

        unset(self::$_data[$cDataIdx]);

        return true;
    }

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $url      = parse_url($path);
        $cDataIdx = $url["host"];
        if (!isset(self::$_data[$cDataIdx]))
            return false;
        $this->_stream = &self::$_data[$cDataIdx];
        $this->_pos    = 0;
        if (!is_string($this->_stream)) return false;
        $this->_cDataIdx = $cDataIdx;
        return true;
    }

    public function stream_read($count)
    {
        $ret = substr($this->_stream, $this->_pos, $count);
        $this->_pos += strlen($ret);
        return $ret;
    }

    public function stream_write($data)
    {
        $len           = strlen($data);
        $this->_stream =
            substr($this->_stream, 0, $this->_pos) .
            $data .
            substr($this->_stream, $this->_pos += $len);
        return $len;
    }

    public function stream_tell()
    {
        return $this->_pos;
    }

    public function stream_eof()
    {
        return $this->_pos >= strlen($this->_stream);
    }

    public function stream_seek($offset, $whence)
    {
        $len = strlen($this->_stream);
        switch ($whence) {
            case SEEK_SET:
                $newPos = $offset;
                break;
            case SEEK_CUR:
                $newPos = $this->_pos + $offset;
                break;
            case SEEK_END:
                $newPos = $len + $offset;
                break;
            default:
                return false;
        }
        $ret = ($newPos >= 0 && $newPos <= $len);
        if ($ret) $this->_pos = $newPos;
        return $ret;
    }

    public function url_stat($path, $flags)
    {
        $url     = parse_url($path);
        $dataIdx = $url["host"];
        if (!isset(self::$_data[$dataIdx]))
            return false;

        $size = strlen(self::$_data[$dataIdx]);
        return array(
            7      => $size,
            'size' => $size
        );
    }

    public function stream_stat()
    {
        $size = strlen($this->_stream);

        return array(
            'size' => $size,
            7      => $size,
        );
    }
}

stream_wrapper_register('VarStream', \app\components\VarStream::class) or die('Failed to register protocol VarStream://');
