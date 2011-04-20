<?php
/*=======================================================================
// File:	JPGRAPH_PIE3D.PHP
// Description: 3D Pie plot extension for JpGraph
// Created: 	2001-03-24
// Author:	Johan Persson (johanp@aditus.nu)
// Ver:		$Id: s.jpgraph_pie3d.php 1.1 02/11/18 06:18:19-00:00 jpm $
//
// License:	This code is released under GPL 2.0
// Copyright (C) 2001 Johan Persson
//========================================================================
*/

//===================================================
// CLASS PiePlot3D
// Description: Plots a 3D pie with a specified projection
// angle between 20 and 70 degrees.
//===================================================
class PiePlot3D extends PiePlot {
    var $labelhintcolor="red",$showlabelhint=true,$labelmargin=0.30;
    var $angle=30;

//---------------
// CONSTRUCTOR
    function PiePlot3d(&$data) {
	$this->data = $data;
	$this->title = new Text("");
	$this->title->SetFont(FF_FONT1,FS_BOLD);
    }

//---------------
// PUBLIC METHODS

    // Specify projection angle for 3D in degrees
    // Must be between 20 and 70 degrees
    function SetAngle($a) {
	if( $a<30 || $a>70 )
	    JpGraphError::Raise("JpGraph: 3D Pie projection angle must be between 30 and 70 degrees.");
	else
	    $this->angle = $a;
    }

    function AddSliceToCSIM($i,$xc,$yc,$height,$width,$thick,$sa,$ea) {  //Slice number, ellipse centre (x,y), height, width, start angle, end angle

	//add coordinates of the centre to the map
	$coords = "$xc, $yc";

	//add coordinates of the first point on the arc to the map
	$xp = floor($width*cos($sa)/2+$xc);
	$yp = floor($yc-$height*sin($sa)/2);
	$coords.= ", $xp, $yp";

	//If on the front half, add the thickness offset
	if ($sa >= M_PI && $sa <= 2*M_PI*1.01) {
	    $yp = floor($yp+($thick*$width));
	    $coords.= ", $xp, $yp";
	}

	//add coordinates every 0.2 radians
	$a=$sa+0.2;
	while ($a<$ea) {
	    $xp = floor($width*cos($a)/2+$xc);
	    if ($a >= M_PI && $a <= 2*M_PI*1.01) {
		$yp = floor($yc-($height*sin($a)/2)+$thick*$width);
	    } else {
		$yp = floor($yc-$height*sin($a)/2);
	    }
	    $coords.= ", $xp, $yp";
	    $a += 0.2;
	}

	//Add the last point on the arc
	$xp = floor($width*cos($ea)/2+$xc);
	$yp = floor($yc-$height*sin($ea)/2);

	//If on the front half, add the thickness offset
	if ($ea >= M_PI && $ea <= 2*M_PI*1.01) {
	    $coords.= ", $xp, ".floor($yp+($thick*$width));
	}
	$coords.= ", $xp, $yp";
	if( !empty($this->csimalts[$i]) ) {
	    $tmp=sprintf($this->csimalts[$i],$this->data[$i]);
	    $alt="alt=\"$tmp\"";
	}
	if( !empty($this->csimtargets[$i]) )
	    $this->csimareas .= "<area shape=\"poly\" coords=\"$coords\" href=\"".$this->csimtargets[$i]."\" $alt>\r\n";
    }


    function ExplodeSlice($e) {
	JpGraphError::Raise("JpGraph Error: Exploding slices are not (yet) implemented for 3d pies graphs.");
	//$this->explode_slice=$e;
    }

    // Distance from the pie to the labels
    function SetLabelMargin($m) {
	assert($m>0 && $m<1);
	$this->labelmargin=$m;
    }

    // Show a thin line from the pie to the label for a specific slice
    function ShowLabelHint($f=true) {
	$this->showlabelhint=$f;
    }

    // Set color of hint line to label for each slice
    function SetLabelHintColor($c) {
	$this->labelhintcolor=$c;
    }

    function Stroke(&$img) {

	$colors = array_keys($img->rgb->rgb_table);
   	sort($colors);
   	$ta=$this->themearr[$this->theme];

   	if( $this->setslicecolors==null )
	    $numcolors=count($ta);
   	else
	    $numcolors=count($this->setslicecolors);

	// Draw the slices
	$sum=0;
	foreach($this->data as $d)
	    $sum += $d;

	// Format the titles for each slice
	for( $i=0; $i<count($this->data); ++$i) {
	    if( $this->labeltype==0 )
		if( $sum != 0 )
		    $l = round(100*$this->data[$i]/$sum,$this->precision);
		else
		    $l=0;
	    else
		$l = $this->data[$i];
	    $l = sprintf($this->labelformat,$l);
	    if( $this->show_psign ) $l .= "%";
	    $this->labels[$i]=$l;
	}

	// Set up the pie-circle with some heuristic constants
	$thick=0.16-($this->angle-20)/60*0.07;

	$width = floor(2.0*$this->radius*min($img->width,$img->height));
	$height = ($this->angle/90.0)*$width;
	$xc = $this->posx*$img->width;
	$yc = $this->posy*$img->height;

	$img->SetColor($this->color);
	$img->Ellipse($xc,$yc,$width,$height);
	$img->Arc($xc,$yc+$width*$thick,$width,$height,0,180);
	$img->Line($xc+$width/2,$yc,$xc+$width/2,$yc+$width*$thick);
	$img->Line($xc-$width/2,$yc,$xc-$width/2,$yc+$width*$thick);
	$fillPerimeter[0] = array('x' => round((($xc - ($width / 2)) + 1)),
				  'y' => round(($yc + ($width * $thick) / 2)));

	// Draw the first slice first line
	$img->SetColor($this->color);
	$img->SetLineWeight($this->weight);
	$a = $this->startangle;

	$xp = $width*cos($a)/2+$xc;
	$yp = $yc-$height*sin($a)/2;
	$img->Line($xc,$yc,$xp,$yp);

	for($i=0; $sum>0 && $i<count($this->data); $i++) {
	    $img->SetColor($this->color);
	    $d = $this->data[$i];
	    $la = $a + M_PI*$d/$sum;
	    $old_a = $a;
	    $a += 2*M_PI*$d/$sum;

	    if ($this->csimtargets[$i]) {
		$this->AddSliceToCSIM($i,$xc,$yc,$height,$width,$thick,$old_a,$a);
	    }

	    $xp = $width*cos($a)/2+$xc;
	    $yp = $yc-$height*sin($a)/2;

	    if( $i<count($this->data)-1)
		$img->Line($xc,$yc,$xp,$yp);

	    if( $a > M_PI && $a < 0.999*2*M_PI )
		$img->Line($xp,$yp,$xp,$yp+$width*$thick-1);

	    if($a < M_PI) {
		$fillPerimeter[$i + 1] = $fillPerimeter[$i];
	    } else {
		$fillPerimeter[$i + 1] = array('x' => round(($xp + 1)),
					       'y' => round(($yp + ($width * $thick) / 2)));
	    }

	    if( $this->setslicecolors==null )
		$slicecolor=$colors[$ta[$i%$numcolors]];
	    else
		$slicecolor=$this->setslicecolors[$i%$numcolors];

	    if( $this->show_labels ) {
		$margin = 1 + $this->labelmargin;
		$xp = $width*cos($la)/2*$margin;
		$yp = $height*sin($la)/2*$margin;

		if( ($la >= 0 && $la <= M_PI) || $la>2*M_PI*0.98 ) {
		    $this->StrokeLabels($this->labels[$i],$img,$la,$xc+$xp,$yc-$yp);
		    if( $this->showlabelhint ) {
			$img->SetColor($this->labelhintcolor);
			$img->Line($xc+$xp/$margin,$yc-$yp/$margin,$xc+$xp,$yc-$yp);
		    }
		}
		else {
		    $this->StrokeLabels($this->labels[$i],$img,$la,$xc+$xp,$yc-$yp+$width*$thick);
		    if( $this->showlabelhint ) {
			$img->SetColor($this->labelhintcolor);
			$img->Line($xc+$xp/$margin,$yc-$yp/$margin+$width*$thick,$xc+$xp,$yc-$yp+$width*$thick);
		    }
		}


		$img->SetColor($slicecolor);
		$xp = $width*cos($la)/3+$xc;
		$yp = $yc-$height*sin($la)/3;
		$img->Fill(round($xp), round($yp));

		// Make the edge color 35% darker
		$img->SetColor($slicecolor.":0.65");

		if($fillPerimeter[$i]['x'] <= $xc + ($width / 2)) {
		    $img->Fill($fillPerimeter[$i]['x'],$fillPerimeter[$i]['y']);
		}
	    }
	}

	// Adjust title position
	$this->title->Pos($xc,$yc-$img->GetFontHeight()-$this->radius,"center","bottom");
	$this->title->Stroke($img);

	// Draw the pie ellipse one more time since the filling might have
	// written partly on the lines due to the filling in the edges.
	$img->SetColor($this->color);
	$img->Ellipse($xc,$yc,$width,$height);
	$img->Arc($xc,$yc+$width*$thick,$width,$height,0,180);

	// Draw the first slice first line
	$a = $this->startangle;
	$xp = $width*cos($a)/2+$xc;
	$yp = $yc-$height*sin($a)/2;
	$img->Line($xc,$yc,$xp,$yp);

	// Draw the rest of the slice lines
	for($i=0, $a=0; $sum>0 && $i<count($this->data); $i++) {
	    $d = $this->data[$i];
	    $la = $a + M_PI*$d/$sum;
	    $old_a = $a;
	    $a += 2*M_PI*$d/$sum;

	    $xp = $width*cos($a)/2+$xc;
	    $yp = $yc-$height*sin($a)/2;

	    if( $a > M_PI && $a < 0.999*2*M_PI )
		$img->Line($xp,$yp,$xp,$yp+$width*$thick-1);

	    if( $i<count($this->data)-1)
		$img->Line($xc,$yc,$xp,$yp);
	}

	$img->Line($xc+$width/2,$yc,$xc+$width/2,$yc+$width*$thick);
	$img->Line($xc-$width/2,$yc,$xc-$width/2,$yc+$width*$thick);

    }

//---------------
// PRIVATE METHODS

    // Position the labels of each slice
    function StrokeLabels($label,$img,$a,$xp,$yp) {

	$img->SetFont($this->font_family,$this->font_style,$this->font_size);
	$img->SetColor($this->font_color);
	$img->SetTextAlign("left","top");
	$marg=6;

	// Position the axis title.
	// dx, dy is the offset from the top left corner of the bounding box that sorrounds the text
	// that intersects with the extension of the corresponding axis. The code looks a little
	// bit messy but this is really the only way of having a reasonable position of the
	// axis titles.
	$h=$img->GetTextHeight($label);
	$w=$img->GetTextWidth($label);
	while( $a > 2*M_PI ) $a -= 2*M_PI;
	if( $a>=7*M_PI/4 || $a <= M_PI/4 ) $dx=0;
	if( $a>=M_PI/4 && $a <= 3*M_PI/4 ) $dx=($a-M_PI/4)*2/M_PI;
	if( $a>=3*M_PI/4 && $a <= 5*M_PI/4 ) $dx=1;
	if( $a>=5*M_PI/4 && $a <= 7*M_PI/4 ) $dx=(1-($a-M_PI*5/4)*2/M_PI);

	if( $a>=7*M_PI/4 ) $dy=(($a-M_PI)-3*M_PI/4)*2/M_PI;
	if( $a<=M_PI/4 ) $dy=(1-$a*2/M_PI);
	if( $a>=M_PI/4 && $a <= 3*M_PI/4 ) $dy=1;
	if( $a>=3*M_PI/4 && $a <= 5*M_PI/4 ) $dy=(1-($a-3*M_PI/4)*2/M_PI);
	if( $a>=5*M_PI/4 && $a <= 7*M_PI/4 ) $dy=0;

	$img->StrokeText($xp-$dx*$w,$yp-$dy*$h,$label);
    }
} // Class

/* EOF */
