<?php

Lumine::load('Report_IReport');

if( !class_exists('FPDF') )
{
	Lumine::load('Utils_fpdf');
}


class Lumine_Report_PDF extends Lumine_Report implements Lumine_IReport  {
	
	protected $pdf;
	protected $oldStyle;
	protected $currentStyle = array();
	
	public $defaultStyle = array(
		'font' => 'Arial',
		'size' => 10,
		'borders' => 'BTLR',
		'height' => 5,
		'align' => 'L',
		'fontcolor' => '#000000'
	);
	
	public $titleStyle = array (
		'font' => 'Arial',
		'bold' => true,
		'size' => 16,
		'borders' => 'BTLR',
		'height' => 16,
		'align' => 'C',
		'fontcolor' => '#000000'
	);
	
	public $headerStyle = array (
		'font' => 'Arial',
		'size' => 10,
		'borders' => 'BTLR',
		'height' => 5,
		'align' => 'L',
		'fontcolor' => '#000000'
	);
	
	public $borders = 'B';	
	public $orientation = 'P';
	public $title;
	public $footer;
	public $rowColors = array('#CCCCCC','#FFFFFF');
	public $headerFont;
	public $headerSize;
	public $headerColor;
	public $result;
	
	public $detailFont = 'Arial';
	public $detailFontSize = 8;
	
	public $titleFont = 'Arial';
	public $titleFontSize = 14;
	
	public $style;
	
	public function run()
	{
		if( !defined('FPDF_FONTPATH') )
		{
			define('FPDF_FONTPATH', LUMINE_INCLUDE_PATH . '/lib/Report/font/');
		}
		$this->pdf = new FPDF($this->orientation);
		$this->pdf->SetMargins(5,5,5);
		$this->pdf->SetFont('Arial','',10);
		$this->pdf->Open();
		$this->pdf->AliasNbPages();
		$this->pdf->AddPage();
		
		$this->setStyle( $this->titleStyle );
		
		$this->pdf->Cell( 0, $this->titleStyle['height'], $this->title, $this->titleStyle['borders'], 0, $this->titleStyle['align'] );
		$this->pdf->Ln();

		$this->setStyle( $this->headerStyle );
		
		foreach($this->columns as $column)
		{
			if( isset($column['style']) )
			{
				$this->setStyle( $column['style'] );
			} 
			$this->pdf->Cell( $column['width'], isset($column['height']) ? $column['height'] : $this->headerStyle['height'], $column['header'], isset($column['style']['borders']) ? $column['style']['borders'] : $this->headerStyle['borders'], 0, isset($column['style']['align']) ? $column['style']['align'] : $this->headerStyle['align'], isset($column['style']['color']) );
			$this->setStyle( $this->headerStyle );
		}
		$this->pdf->Ln();
		
		$this->setStyle( $this->defaultStyle );
		
		
		// preenche com os dados
		$cdx = 0;
		while( $this->obj->fetch() )
		{
			$cor = $this->rowColors[ $cdx ];
			$c = array();
			
			$c = $this->HexToDec( $cor );
			
			$iniy = $this->pdf->GetY();
			$inix = $this->pdf->GetX();
			$maxy = $iniy;
			//$this->pdf->SetDrawColor(128,0,0); 
			//$this->pdf->SetLineWidth(.5);
			
			foreach( $this->columns as $column )
			{
				if( $column['style']['color'] && empty($column['style']['colorOnlyHeader']) )
				{
					$x = $this->HexToDec( $column['style']['color'] );
					$this->pdf->SetFillColor( $x[0], $x[1], $x[2] );
				} else {
					$this->pdf->SetFillColor( $c[0], $c[1], $c[2] );
				}
				
				$this->pdf->Cell( $column['width'],
					isset($column['height']) ? $column['height'] : $this->defaultStyle['height'],
					$this->obj->$column['name'],
					isset($column['style']['borders']) ? $column['style']['borders'] : $this->defaultStyle['borders'],
					0,
					isset($column['style']['align']) ? $column['style']['align'] :$this->defaultStyle['align'],
					1);
			}
			
			if( ++$cdx >= count($this->rowColors) )
			{
				$cdx = 0;
			}
			
			$this->pdf->Ln();
		}
	}
	
	public function parseFromModel( $tpl )
	{
	}
	
	public function output()
	{
		$this->pdf->Output();
	}
	
	protected function setStyle( $style )
	{
		$this->oldStyle = $this->currentStyle;
		$this->currentStyle = $style;
		$this->applyStyle();
	}
	
	protected function applyStyle()
	{
		$biu = '';
		$size = 12;
		
		if( isset($this->currentStyle['size']) ) $size = $this->currentStyle['size'];
		if( !empty($this->currentStyle['bold']) ) $biu .= 'B';
		if( !empty($this->currentStyle['italic']) ) $biu .= 'I';
		if( !empty($this->currentStyle['underline']) ) $biu .= 'U';
		if( !empty($this->currentStyle['color']) )
		{
			$c = $this->HexToDec( $this->currentStyle['color'] );
			$this->pdf->SetFillColor( $c[0], $c[1], $c[2] );
		}
		
		if( !empty($this->currentStyle['fontcolor']) )
		{
			$c = $this->HexToDec( $this->currentStyle['fontcolor'] );
			$this->pdf->SetTextColor( $c[0], $c[1], $c[2] );
		}
		
		if( isset($this->currentStyle['font']) )
		{
			$this->pdf->SetFont( $this->currentStyle['font'], $biu, $size );
		} else {
			$this->pdf->SetFont( 'Arial', $biu, $size );
		}
	}
	
	protected function HexToDec( $cor )
	{
		$c = array();
		$c[] = hexdec($cor{1} . $cor{2});
		$c[] = hexdec($cor{3} . $cor{4});
		$c[] = hexdec($cor{5} . $cor{6});
		return $c;
	}
}


?>