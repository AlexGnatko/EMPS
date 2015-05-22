<?php

include_once 'Text/Diff.php';
include_once 'Text/Diff/Renderer.php';
include_once 'Text/Diff/Renderer/unified.php';
include_once 'Text/Diff/Renderer/inline.php';

class EMPS_Diff_Renderer extends Text_Diff_Renderer {

	var $ins_prefix = '<ins>';
	var $ins_suffix = '</ins>';
	var $del_prefix = '<del>';
	var $del_suffix = '</del>';
	
	function EMPS_Diff_Renderer($context_lines = 10000, $ins_prefix = '<ins>', $ins_suffix = '</ins>', $del_prefix = '<del>', $del_suffix = '</del>')
    {
		$this->$ins_prefix = $ins_prefix;
		$this->$ins_suffix = $ins_suffix;
		$this->$del_prefix = $del_prefix;
		$this->$del_suffix = $del_suffix;
		
        $this->_leading_context_lines = $context_lines;
        $this->_trailing_context_lines = $context_lines;
    }

    function _lines($lines)
    {
        foreach ($lines as $line) {
            echo "$line ";
            // FIXME: don't output space if it's the last line.
        }
    }

    function _blockHeader($xbeg, $xlen, $ybeg, $ylen)
    {
		return '';
    }

    function _startBlock($header)
    {
        echo $header;
    }

    function _added($lines)
    {
		echo $this->ins_prefix;
        $this->_lines($lines);
		echo $this->ins_suffix;
    }

    function _deleted($lines)
    {
		echo $this->del_prefix;
        $this->_lines($lines);
		echo $this->del_suffix;
    }

    function _changed($orig, $final)
    {
        $this->_deleted($orig);
        $this->_added($final);
    }

}


class EMPS_Diff {
	public $lines = 50000;
	public function diff_result($text1, $text2){

		$hlines1 = explode("\n", $text1);
		$hlines2 = explode("\n", $text2);
	
		// create the diff object
		$diff = &new Text_Diff($hlines1, $hlines2);
		
		// get the diff in unified format
		// you can add 4 other parameters, which will be the ins/del prefix/suffix tags
//		$renderer = &new EMPS_Diff_Renderer(50000);
		$renderer = &new Text_Diff_Renderer_inline($this->lines);
		return $renderer->render($diff);
	
	}
}

?>