<?php
class PDF extends FPDF_Rotate
{
	function RotatedText($x,$y,$txt,$angle)
	{
	    //Rotation du texte autour de son origine
	    $this->Rotate($angle,$x,$y);
	    $this->Text($x,$y,$txt);
	    $this->Rotate(0);
	}
	
	function RotatedImage($file,$x,$y,$w,$h,$angle)
	{
	    //Rotation de l'image autour du coin sup�rieur gauche
	    $this->Rotate($angle,$x,$y);
	    $this->Image($file,$x,$y,$w,$h);
	    $this->Rotate(0);
	}
}
?>