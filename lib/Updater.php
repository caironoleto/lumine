<?php

class Lumine_Updater extends Lumine_EventListener
{

	private $filelist = array();

	public function getFileList()
	{
		$this->filelist = array();
		$this->_getFilesFromDir( LUMINE_INCLUDE_PATH );
		
		return $this->filelist;
	}
	
	private function _getFilesFromDir( $dir )
	{
		$dh = opendir( $dir );
		while( ($file = readdir($dh)) !== false )
		{
			if($file == '.' || $file == '..')
			{
				continue;
			}
			
			if(is_dir($dir .'/'. $file))
			{
				$this->_getFilesFromDir( $dir . '/' . $file );
			} else {
				$ds = DIRECTORY_SEPARATOR;
				$filename = $dir . $ds . $file;
				
				$item = array(
					'filename' => str_replace(LUMINE_INCLUDE_PATH . $ds, '', $filename),
					'filesize' => filesize($filename),
					'filedate' => filemtime($filename)
				);
				$this->filelist[] =  $item;
			}
		}
		
		closedir($dh);
	}

}


?>