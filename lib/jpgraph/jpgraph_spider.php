<?php
/*=======================================================================
// File:	JPGRAPH_SPIDER.PHP
// Description: Spider plot extension for JpGraph
// Created: 	2001-02-04
// Author:	Johan Persson (johanp@aditus.nu)
// Ver:		$Id: s.jpgraph_spider.php 1.1 02/11/18 06:18:19-00:00 jpm $
//
// License:	This code is released under GPL 2.0
// Copyright (C) 2001 Johan Persson
//========================================================================
*/

//===================================================
// CLASS FontProp
// Description: Utility class to enable the use
// of a "title" instance variable for the spider axis.
// This clas is only used to hold a font and a color
// property for the axis title.
//===================================================
class FontProp {
    var $font_family=FF_FONT1, $font_style=FS_NORMAL,$font_size=14,$font_color=array(0,0,0);
    function SetFont($family,$style=FS_NORMAL,$size=14) {
	$this->font_family = $family;
	$this->font_style = $style;
	$this->font_size = $size;
    }

    function SetColor($c) {
	$this->font_color = $c;
    }
}

class SpiderLogTicks extends Ticks {
//---------------
// CONSTRUCTOR
    function SpiderLogTicks() {
    }
//---------------
// PUBLIC METHODS

    // TODO: Add Argument grid
    function Stroke(&$aImg,&$grid,$aPos,$aAxisAngle,&$aScale,&$aMajPos,&$aMajLabel) {
	$start = $aScale->GetMinVal();
	$limit = $aScale->GetMaxVal();
	$nextMajor = 10*$start;
	$step = $nextMajor / 10.0;
	$count=1;

	$ticklen_maj=5;
	$dx_maj=round(sin($aAxisAngle)*$ticklen_maj);
	$dy_maj=round(cos($aAxisAngle)*$ticklen_maj);
	$ticklen_min=3;
	$dx_min=round(sin($aAxisAngle)*$ticklen_min);
	$dy_min=round(cos($aAxisAngle)*$ticklen_min);

	$aMajPos=array();
	$aMajLabel=array();

	if( $this->supress_first )
	    $aMajLabel[]="";
	else
	    $aMajLabel[]=$start;
	$yr=$aScale->RelTranslate($start);
	$xt=round($yr*cos($aAxisAngle))+$aScale->scale_abs[0];
	$yt=$aPos-round($yr*sin($aAxisAngle));
	$aMajPos[]=$xt+2*$dx_maj;
	$aMajPos[]=$yt-$aImg->GetFontheight()/2;
	$grid[]=$xt;
	$grid[]=$yt;

	$aImg->SetLineWeight($this->weight);

	for($y=$start; $y<=$limit; $y+=$step,++$count  ) {
	    $yr=$aScale->RelTranslate($y);
	    $xt=round($yr*cos($aAxisAngle))+$aScale->scale_abs[0];
	    $yt=$aPos-round($yr*sin($aAxisAngle));
	    if( $count % 10 == 0 ) {
		$grid[]=$xt;
		$grid[]=$yt;
		$aMajPos[]=$xt+2*$dx_maj;
		$aMajPos[]=$yt-$aImg->GetFontheight()/2;
		if( !$this->supress_tickmarks )	{
		    if( $this->majcolor!="" ) $aImg->PushColor($this->majcolor);
		    $aImg->Line($xt+$dx_maj,$yt+$dy_maj,$xt-$dx_maj,$yt-$dy_maj);
		    if( $this->majcolor!="" ) $aImg->PopColor();
		}
		$aMajLabel[]=$nextMajor;
		$nextMajor *= 10;
		$step *= 10;
		$count=1;
	    }
	    else
		if( !$this->supress_minor_tickmarks )	{
		    if( $this->mincolor!="" ) $aImg->PushColor($this->mincolor);
		    $aImg->Line($xt+$dx_min,$yt+$dy_min,$xt-$dx_min,$yt-$dy_min);
		    if( $this->mincolor!="" ) $aImg->PopColor();
		}
	}
    }
}

class SpiderLinearTicks extends LinearTicks {
//---------------
// CONSTRUCTOR
    function SpiderLinearTicks() {
	// Empty
    }

//---------------
// PUBLIC METHODS

    // TODO: Add argument grid
    function Stroke(&$aImg,&$grid,$aPos,$aAxisAngle,&$aScale,&$aMajPos,&$aMajLabel) {
	// Prepare to draw linear ticks
	$maj_step_abs = abs($aScale->scale_factor*$this->major_step);
	$min_step_abs = abs($aScale->scale_factor*$this->minor_step);
	$nbrmaj = floor(($aScale->world_abs_size)/$maj_step_abs);
	$nbrmin = floor(($aScale->world_abs_size)/$min_step_abs);
	$skip = round($nbrmin/$nbrmaj); // Don't draw minor ontop of major

	// Draw major ticks
	$ticklen2=4;
	$dx=round(sin($aAxisAngle)*$ticklen2);
	$dy=round(cos($aAxisAngle)*$ticklen2);
	$label=$aScale->scale[0]+$this->major_step;

	$aImg->SetLineWeight($this->weight);

	for($i=1; $i<=$nbrmaj; ++$i) {
	    $xt=round($i*$maj_step_abs*cos($aAxisAngle))+$aScale->scale_abs[0];
	    $yt=$aPos-round($i*$maj_step_abs*sin($aAxisAngle));
	    $aMajLabel[]=$label;
	    $label += $this->major_step;
	    $grid[]=$xt;
	    $grid[]=$yt;
	    $aMajPos[($i-1)*2]=$xt+2*$dx;
	    $aMajPos[($i-1)*2+1]=$yt-$aImg->GetFontheight()/2;
	    if( !$this->supress_tickmarks ) {
		if( $this->majcolor!="" ) $aImg->PushColor($this->majcolor);
		$aImg->Line($xt+$dx,$yt+$dy,$xt-$dx,$yt-$dy);
		if( $this->majcolor!="" ) $aImg->PopColor();
	    }
	}

	// Draw minor ticks
	$ticklen2=3;
	$dx=round(sin($aAxisAngle)*$ticklen2);
	$dy=round(cos($aAxisAngle)*$ticklen2);
	if( !$this->supress_tickmarks && !$this->supress_minor_tickmarks)	{
	    if( $this->mincolor!="" ) $aImg->PushColor($this->mincolor);
	    for($i=1; $i<=$nbrmin; ++$i) {
		if( ($i % $skip) == 0 ) continue;
		$xt=round($i*$min_step_abs*cos($aAxisAngle))+$aScale->scale_abs[0];
		$yt=$pos-round($i*$min_step_abs*sin($aAxisAngle));
		$aImg->Line($xt+$dx,$yt+$dy,$xt-$dx,$yt-$dy);
	    }
	    if( $this->mincolor!="" ) $aImg->PopColor();
	}
    }
}



//===================================================
// CLASS SpiderAxis
// Description: Implements axis for the spider graph
//===================================================
class SpiderAxis extends Axis {
    var $title_color="navy";
    var $title=null;
//---------------
// CONSTRUCTOR
    function SpiderAxis(&$img,&$aScale,$color=array(0,0,0)) {
	parent::Axis($img,$aScale,$color);
	$this->len=$img->plotheight;
	$this->font_size = FF_FONT1;
	$this->title = new FontProp();
	$this->color = array(0,0,0);
    }
//---------------
// PUBLIC METHODS
    function SetTickLabels($l) {
	$this->ticks_label = $l;
    }


    // Stroke the axis
    // $pos 			= Vertical position of axis
    // $aAxisAngle = Axis angle
    // $grid			= Returns an array with positions used to draw the grid
    //	$lf			= Label flag, TRUE if the axis should have labels
    function Stroke($pos,$aAxisAngle,&$grid,$title,$lf) {
	$this->img->SetColor($this->color);

	// Determine end points for the axis
	$x=round($this->scale->world_abs_size*cos($aAxisAngle)+$this->scale->scale_abs[0]);
	$y=round($pos-$this->scale->world_abs_size*sin($aAxisAngle));

	// Draw axis
	$this->img->SetColor($this->color);
	$this->img->SetLineWeight($this->weight);
	if( !$this->hide )
	    $this->img->Line($this->scale->scale_abs[0],$pos,$x,$y);

	$this->scale->ticks->Stroke($this->img,$grid,$pos,$aAxisAngle,$this->scale,$majpos,$majlabel);

	// Draw labels
	if( $lf && !$this->hide ) {
	    $this->img->SetFont($this->font_family,$this->font_style,$this->font_size);
	    $this->img->SetTextAlign("left","top");
	    $this->img->SetColor($this->color);

	    // majpos contsins (x,y) coordinates for labels
	    for($i=0; $i<count($majpos)/2; ++$i) {
		if( $this->ticks_label != null )
		    $this->img->StrokeText($majpos[$i*2],$majpos[$i*2+1],$this->ticks_label[$i]);
		else
		    $this->img->StrokeText($majpos[$i*2],$majpos[$i*2+1],$majlabel[$i]);
	    }
	}
	$this->_StrokeAxisTitle($pos,$aAxisAngle,$title);
    }
//---------------
// PRIVATE METHODS

    function _StrokeAxisTitle($pos,$aAxisAngle,$title) {
	// Draw title of this axis
	$this->img->SetFont($this->title->font_family,$this->title->font_style,$this->title->font_size);
	$this->img->SetColor($this->title->font_color);
	$marg=6;
	$xt=round(($this->scale->world_abs_size+$marg)*cos($aAxisAngle)+$this->scale->scale_abs[0]);
	$yt=round($pos-($this->scale->world_abs_size+$marg)*sin($aAxisAngle));

	// Position the axis title.
	// dx, dy is the offset from the top left corner of the bounding box that sorrounds the text
	// that intersects with the extension of the corresponding axis. The code looks a little
	// bit messy but this is really the only way of having a reasonable position of the
	// axis titles.
	$h=$this->img->GetFontHeight();
	$w=$this->img->GetTextWidth($title);
	while( $aAxisAngle > 2*M_PI ) $aAxisAngle -= 2*M_PI;
	if( $aAxisAngle>=7*M_PI/4 || $aAxisAngle <= M_PI/4 ) $dx=0;
	if( $aAxisAngle>=M_PI/4 && $aAxisAngle <= 3*M_PI/4 ) $dx=($aAxisAngle-M_PI/4)*2/M_PI;
	if( $aAxisAngle>=3*M_PI/4 && $aAxisAngle <= 5*M_PI/4 ) $dx=1;
	if( $aAxisAngle>=5*M_PI/4 && $aAxisAngle <= 7*M_PI/4 ) $dx=(1-($aAxisAngle-M_PI*5/4)*2/M_PI);

	if( $aAxisAngle>=7*M_PI/4 ) $dy=(($aAxisAngle-M_PI)-3*M_PI/4)*2/M_PI;
	if( $aAxisAngle<=M_PI/4 ) $dy=(1-$aAxisAngle*2/M_PI);
	if( $aAxisAngle>=M_PI/4 && $aAxisAngle <= 3*M_PI/4 ) $dy=1;
	if( $aAxisAngle>=3*M_PI/4 && $aAxisAngle <= 5*M_PI/4 ) $dy=(1-($aAxisAngle-3*M_PI/4)*2/M_PI);
	if( $aAxisAngle>=5*M_PI/4 && $aAxisAngle <= 7*M_PI/4 ) $dy=0;

	if( !$this->hide )
	    $this->img->StrokeText($xt-$dx*$w,$yt-$dy*$h,$title);

    }


} // Class


//===================================================
// CLASS SpiderGrid
// Description: Draws grid for the spider graph
//===================================================
class SpiderGrid extends Grid {
//------------
// CONSTRUCTOR
    function SpiderGrid() {
    }

//----------------
// PRIVATE METHODS
    function Stroke(&$img,&$grid) {
	if( !$this->show ) return;
	$nbrticks = count($grid[0])/2;
	$nbrpnts = count($grid);
	$img->SetColor($this->grid_color);
	$img->SetLineWeight($this->weight);
	for($i=0; $i<$nbrticks; ++$i) {
	    for($j=0; $j<$nbrpnts; ++$j) {
		$pnts[$j*2]=$grid[$j][$i*2];
		$pnts[$j*2+1]=$grid[$j][$i*2+1];
	    }
	    for($k=0; $k<$nbrpnts; ++$k ){
		$l=($k+1)%$nbrpnts;
		if( $this->type == "solid" )
		    $img->Line($pnts[$k*2],$pnts[$k*2+1],$pnts[$l*2],$pnts[$l*2+1]);
		elseif( $this->type == "dotted" )
		    $img->DashedLine($pnts[$k*2],$pnts[$k*2+1],$pnts[$l*2],$pnts[$l*2+1],1,6);
		elseif( $this->type == "dashed" )
		    $img->DashedLine($pnts[$k*2],$pnts[$k*2+1],$pnts[$l*2],$pnts[$l*2+1],2,4);
		elseif( $this->type == "longdashed" )
		    $img->DashedLine($pnts[$k*2],$pnts[$k*2+1],$pnts[$l*2],$pnts[$l*2+1],8,6);
	    }
	    $pnts=array();
	}
    }
} // Class


//===================================================
// CLASS SpiderPlot
// Description: Plot a spiderplot
//===================================================
class SpiderPlot {
    var $data=array();
    var $fill=false, $fill_color=array(200,170,180);
    var $color=array(0,0,0);
    var $legend="";
    var $weight=1;
//---------------
// CONSTRUCTOR
    function SpiderPlot($data) {
	$this->data = $data;
    }

//---------------
// PUBLIC METHODS
    function Min() {
	return Min($this->data);
    }

    function Max() {
	return Max($this->data);
    }

    function SetLegend($legend) {
	$this->legend=$legend;
    }

    function SetFill($f=true) {
	$this->fill = $f;
    }

    function SetLineWeight($w) {
	$this->weight=$w;
    }

    function SetColor($color,$fill_color=array(160,170,180)) {
	$this->color = $color;
	$this->fill_color = $fill_color;
    }

    function GetCSIMareas() {
	JpGraphError::Raise("JpGraph Error: Client side image maps not supported for SpiderPlots.");
    }

    function Stroke(&$img, $pos, &$scale, $startangle) {
	$nbrpnts = count($this->data);
	$astep=2*M_PI/$nbrpnts;
	$a=$startangle;

	// Rotate each point to the correct axis-angle
	// TODO: Update for LogScale
	for($i=0; $i<$nbrpnts; ++$i) {
	    //$c=$this->data[$i];
	    $cs=$scale->RelTranslate($this->data[$i]);
	    $x=round($cs*cos($a)+$scale->scale_abs[0]);
	    $y=round($pos-$cs*sin($a));
	    /*
	      $c=log10($c);
	      $x=round(($c-$scale->scale[0])*$scale->scale_factor*cos($a)+$scale->scale_abs[0]);
	      $y=round($pos-($c-$scale->scale[0])*$scale->scale_factor*sin($a));
	    */
	    $pnts[$i*2]=$x;
	    $pnts[$i*2+1]=$y;
	    $a += $astep;
	}
	if( $this->fill ) {
	    $img->SetColor($this->fill_color);
	    $img->FilledPolygon($pnts);
	}
	$img->SetLineWeight($this->weight);
	$img->SetColor($this->color);
	$img->Polygon($pnts);
    }

//---------------
// PRIVATE METHODS
    function GetCount() {
	return count($this->data);
    }

    function Legend(&$graph) {
	if( $this->legend=="" ) return;
	if( $this->fill )
	    $graph->legend->Add($this->legend,$this->fill_color);
	else
	    $graph->legend->Add($this->legend,$this->color);
    }

} // Class

//===================================================
// CLASS SpiderGraph
// Description: Main container for a spider graph
//===================================================
class SpiderGraph extends Graph {
    var $posx;
    var $posy;
    var $len;
    var $plots=null, $axis_title=null;
    var $grid,$axis=null;
//---------------
// CONSTRUCTOR
    function SpiderGraph($width=300,$height=200,$cachedName="",$timeout=0,$inline=1) {
	$this->Graph($width,$height,$cachedName,$timeout,$inline);
	$this->posx=$width/2;
	$this->posy=$height/2;
	$this->len=min($width,$height)*0.35;
	$this->SetColor(array(255,255,255));
	$this->SetTickDensity(TICKD_NORMAL);
	$this->SetScale("lin");
    }

//---------------
// PUBLIC METHODS
    function SupressTickMarks($f=true) {
	$this->axis->scale->ticks->SupressTickMarks($f);
    }

    function SetScale($axtype,$ymin=1,$ymax=1) {
	if( $axtype != "lin" && $axtype != "log" ) {
	    JpGraphError::Raise("Illegal scale for spiderplot ($axtype). Must be \"lin\" or \"log\"");
	}
	if( $axtype=="lin" ) {
	    $this->yscale = new LinearScale(1,1);
	    $this->yscale->ticks = new SpiderLinearTicks();
	    $this->yscale->ticks->SupressMinorTickMarks();
	}
	elseif( $axtype=="log" ) {
	    $this->yscale = new LogScale(1,1);
	    $this->yscale->ticks = new SpiderLogTicks();
	    //JpGraphError::Raise("JpGraph Error: Logarithmic spider plots are not yet supported");
	}

	$this->axis = new SpiderAxis($this->img,$this->yscale);
	$this->grid = new SpiderGrid();
    }

    function SetPlotSize($s) {
	if( $s<0.1 || $s>1 )
	    JpGraphError::Raise("JpGraph Error: Spider Plot size must be between 0.1 and 1. (Your value=$s)");
	$this->len=min($this->img->width,$this->img->height)*$s/2;
    }

    function SetTickDensity($densy=TICKD_NORMAL) {
	$this->ytick_factor=25;
	switch( $densy ) {
	    case TICKD_DENSE:
		$this->ytick_factor=12;
	    break;
	    case TICKD_NORMAL:
		$this->ytick_factor=25;
	    break;
	    case TICKD_SPARSE:
		$this->ytick_factor=40;
	    break;
	    case TICKD_VERYSPARSE:
		$this->ytick_factor=70;
	    break;
	    default:
		JpGraphError::Raise("Unsupported Tick density: $densy");
	}
    }

    function SetCenter($px,$py=0.5) {
	assert($px > 0 && $py > 0 );
	$this->posx=$this->img->width*$px;
	$this->posy=$this->img->height*$py;
    }

    function SetColor($c) {
	$this->SetMarginColor($c);
    }

    function SetTitles($title) {
	$this->axis_title = $title;
    }

    function Add(&$splot) {
	$this->plots[]=$splot;
    }

    function GetPlotsYMinMax() {
	$min=$this->plots[0]->Min();
	$max=$this->plots[0]->Max();
	foreach( $this->plots as $p ) {
	    $max=max($max,$p->Max());
	    $min=min($min,$p->Min());
	}
	if( $min < 0 )
	    JpGraphError::Raise("JpGraph Error: Minimum data $min (Spider plots only makes sence to use when all data points > 0)");
	return array($min,$max);
    }

    // Stroke the Spider graph
    function Stroke($aStrokeFileName="") {
	// Set Y-scale
	if( !$this->yscale->IsSpecified() &&
	count($this->plots)>0 ) {
	    list($min,$max) = $this->GetPlotsYMinMax();
	    $this->yscale->AutoScale($this->img,0,$max,$this->len/$this->ytick_factor);
	}
	// Set start position end length of scale (in absolute pixels)
	$this->yscale->SetConstants($this->posx,$this->len);

	// We need as many axis as there are data points
	$nbrpnts=$this->plots[0]->GetCount();

	// If we have no titles just number the axis 1,2,3,...
	if( $this->axis_title==null ) {
	    for($i=0; $i<$nbrpnts; ++$i )
		$this->axis_title[$i] = $i+1;
	}
	elseif(count($this->axis_title)<$nbrpnts)
	    JpGraphError::Raise("JpGraph: Number of titles does not match number of points in plot.");
	for($i=0; $i<count($this->plots); ++$i )
	    if( $nbrpnts != $this->plots[$i]->GetCount() )
		JpGraphError::Raise("JpGraph: Each spider plot must have the same number of data points.");

	$this->StrokeFrame();
	$astep=2*M_PI/$nbrpnts;

	// Prepare legends
	for($i=0; $i<count($this->plots); ++$i)
	    $this->plots[$i]->Legend($this);
	$this->legend->Stroke($this->img);

	// Plot points
	$a=M_PI/2;
	for($i=0; $i<count($this->plots); ++$i )
	    $this->plots[$i]->Stroke($this->img, $this->posy, $this->yscale, $a);

	// Draw axis and grid
	for( $i=0,$a=M_PI/2; $i<$nbrpnts; ++$i, $a+=$astep ) {
	    $this->axis->Stroke($this->posy,$a,$grid[$i],$this->axis_title[$i],$i==0);
	}
	$this->grid->Stroke($this->img,$grid);
	$this->title->Center($this->img->left_margin,$this->img->width-$this->img->right_margin,5);
	$this->title->Stroke($this->img);

	// Stroke texts
	if( $this->texts != null )
	    foreach( $this->texts as $t)
		$t->Stroke($this->img);


	// Finally output the image
	$this->cache->PutAndStream($this->img,$this->cache_name,$this->inline,$aStrokeFileName);
    }
} // Class

/* EOF */
