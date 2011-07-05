<?php
/*=======================================================================
// File:	JPGRAPH_SCATTER.PHP
// Description: Scatter (and impuls) plot extension for JpGraph
// Created: 	2001-02-11
// Author:	Johan Persson (johanp@aditus.nu)
// Ver:		$Id: s.jpgraph_scatter.php 1.1 02/11/18 06:18:19-00:00 jpm $
//
// License:	This code is released under GPL 2.0
// Copyright (C) 2001 Johan Persson
//========================================================================
*/

//===================================================
// CLASS ScatterPlot
// Description: Render X and Y plots
//===================================================
class ScatterPlot extends Plot {
    var $impuls = false;
    var $linkpoints = false, $linkpointweight=1, $linkpointcolor="black";
//---------------
// CONSTRUCTOR
    function ScatterPlot(&$datay,$datax=false) {
	if( (count($datax) != count($datay)) && is_array($datax))
	    JpGraphError::Raise("JpGraph: Scatterplot must have equal number of X and Y points.");
	$this->Plot($datay,$datax);
	$this->mark = new PlotMark();
	$this->mark->SetType(MARK_CIRCLE);
	$this->mark->SetColor($this->color);
    }

//---------------
// PUBLIC METHODS
    function SetImpuls($f=true) {
	$this->impuls = $f;
    }

    // Combine the scatter plot points with a line
    function SetLinkPoints($f=true,$lpc="black",$weight=1) {
	$this->linkpoints=$f;
	$this->linkpointcolor=$lpc;
	$this->linkpointweight=$weight;
    }

    function Stroke(&$img,&$xscale,&$yscale) {
	$ymin=$yscale->scale_abs[0];
	if( $yscale->scale[0] < 0 )
	    $yzero=$yscale->Translate(0);
	else
	    $yzero=$yscale->scale_abs[0];
	for( $i=0; $i<$this->numpoints; ++$i ) {
	    if( isset($this->coords[1]) )
		$xt = $xscale->Translate($this->coords[1][$i]);
	    else
		$xt = $xscale->Translate($i);
	    $yt = $yscale->Translate($this->coords[0][$i]);
	    if( $this->linkpoints && isset($yt_old) ) {
		$img->SetColor($this->linkpointcolor);
		$img->SetLineWeight($this->linkpointweight);
		$img->Line($xt_old,$yt_old,$xt,$yt);
	    }
	    if( $this->impuls ) {
		$img->SetColor($this->color);
		$img->SetLineWeight($this->weight);
		$img->Line($xt,$yzero,$xt,$yt);
	    }
	    $this->mark->Stroke($img,$xt,$yt);
	    $xt_old = $xt;
	    $yt_old = $yt;
	}
    }

    // Framework function
    function Legend(&$aGraph) {
	if( $this->legend != "" ) {
	    $aGraph->legend->Add($this->legend,$this->mark->fill_color);
	}
    }

} // Class
/* EOF */
