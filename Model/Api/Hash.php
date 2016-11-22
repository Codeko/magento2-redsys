<?php

namespace Codeko\Redsys\Model\Api;

class Hash{
	//	The base modes are:
	//	'bin' - binary output (most compact)
	//	'bit' - bit output (largest)
	//	'oct' - octal output (medium-large)
	//	'hex' - hexidecimal (default, medium)
  
	// perform a hash on a string
	function __construct($str, $mode = 'hex'){
		trigger_error('hash::hash() NOT IMPLEMENTED', E_USER_WARNING);
		return false;
	}
  
	// chop the resultant hash into $length byte chunks
	function hashChunk($str, $length, $mode = 'hex'){
		trigger_error('hash::hashChunk() NOT IMPLEMENTED', E_USER_WARNING);
		return false;
	}
  
	// perform a hash on a file
	function hashFile($filename, $mode = 'hex'){
		trigger_error('hash::hashFile() NOT IMPLEMENTED', E_USER_WARNING);
		return false;
	}
  
	// chop the resultant hash into $length byte chunks
	function hashChunkFile($filename, $length, $mode = 'hex'){
		trigger_error('hash::hashChunkFile() NOT IMPLEMENTED', E_USER_WARNING);
		return false;
	}
}

