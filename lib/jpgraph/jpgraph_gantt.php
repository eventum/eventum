<?php
/*=======================================================================
// File:	JPGRAPH_GANTT.PHP
// Description:	JpGraph Gantt plot extension
// Created: 	2001-11-12
// Author:	Johan Persson (johanp@aditus.nu)
// Ver:		$Id: s.jpgraph_gantt.php 1.1 02/11/18 06:18:19-00:00 jpm $
//
// License:	This code is released under GPL 2.0
//
//========================================================================
*/

// Scale Header types
DEFINE("GANTT_HDAY",1);
DEFINE("GANTT_HWEEK",2);
DEFINE("GANTT_HMONTH",4);
DEFINE("GANTT_HYEAR",8);

// Bar patterns
DEFINE("GANTT_RDIAG",BAND_RDIAG);	// Right diagonal lines
DEFINE("GANTT_LDIAG",BAND_LDIAG); // Left diagonal lines
DEFINE("GANTT_SOLID",BAND_SOLID); // Solid one color
DEFINE("GANTT_VLINE",BAND_VLINE); // Vertical lines
DEFINE("GANTT_HLINE",BAND_HLINE);  // Horizontal lines
DEFINE("GANTT_3DPLANE",BAND_3DPLANE);  // "3D" Plane
DEFINE("GANTT_HVCROSS",BAND_HVCROSS);  // Vertical/Hor crosses
DEFINE("GANTT_DIAGCROSS",BAND_DIAGCROSS); // Diagonal crosses

// Conversion constant
DEFINE("SECPERDAY",3600*24);

// Locales
DEFINE("LOCALE_EN",0);
DEFINE("LOCALE_SE",1);

// Layout of bars
DEFINE("GANTT_EVEN",1);
DEFINE("GANTT_FROMTOP",2);

// Styles for week header
DEFINE("WEEKSTYLE_WNBR",0);
DEFINE("WEEKSTYLE_FIRSTDAY",1);
DEFINE("WEEKSTYLE_FIRSTDAY2",1);

// Styles for month header
DEFINE("MONTHSTYLE_SHORTNAME",0);
DEFINE("MONTHSTYLE_LONGNAME",1);
DEFINE("MONTHSTYLE_LONGNAMEYEAR2",2);
DEFINE("MONTHSTYLE_SHORTNAMEYEAR2",3);
DEFINE("MONTHSTYLE_LONGNAMEYEAR4",4);
DEFINE("MONTHSTYLE_SHORTNAMEYEAR4",5);

//===================================================
// CLASS DateLocale
// Description: Hold localized text used in dates
// ToDOo: Rewrite this to use the real local locale
// instead.
//===================================================
class DateLocale {
    var $iLocale=0; 	// Default to english
    var $iDayAbb = array(
	array("M","T","W","T","F","S","S"),				// English locale
	array("M","T","O","T","F","L","S"));			// Swedish locale
    var $iShortDay = array(
	array("Mon","Tue","Wed","Thu","Fri","Sat","Sun"),
	array("Mån","Tis","Ons","Tor","Fre","Lör","Sön"));
    var $iShortMonth = array(
	array("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"),
	array("Jan","Feb","Mar","Apr","Maj","Jun","Jul","Aug","Sep","Okt","Nov","Dec"));
    var $iMonthName = array(
	array("January","February","Mars","April","May","June","July","August","September","October","November","December"),
	array("Januari","Februari","Mars","April","Maj","Juni","Juli","Augusti","September","Oktober","November","December"));

//---------------
// CONSTRUCTOR
    function DateLocale() {
	// Empty
    }

//---------------
// PUBLIC METHODS
    function Set($aLocale) {
	if( $aLocale < LOCALE_EN || $aLocale > LOCALE_SE )
	    JpGraphError::Raise("<b>JpGraph Error:</b> Unsupported locale ($aLocale)");
	$this->iLocale = $aLocale;
    }

    function GetDayAbb() {
	return $this->iDayAbb[$this->iLocale];
    }

    function GetShortDay() {
	return $this->iShortDay[$this->iLocale];
    }

    function GetShortMonth($aMonth=null) {
	return $this->iShortMonth[$this->iLocale];
    }

    function GetShortMonthName($aNbr) {
	return $this->iShortMonth[$this->iLocale][$aNbr];
    }

    function GetLongMonthName($aNbr) {
	return $this->iMonthName[$this->iLocale][$aNbr];
    }

    function GetMonth() {
	return $this->iMonthName[$this->iLocale];
    }
}

//===================================================
// CLASS GanttGraph
// Description: Main class to handle gantt graphs
//===================================================
class GanttGraph extends Graph {
    var $scale;							// Public accessible
    var $iObj=array();				// Gantt objects
    var $iLabelHMarginFactor=0.2;	// 10% margin on each side of the labels
    var $iLabelVMarginFactor=0.4;	// 40% margin on top and bottom of label
    var $iLayout=GANTT_FROMTOP;	// Could also be GANTT_EVEN

//---------------
// CONSTRUCTOR
    // Create a new gantt graph
    function GanttGraph($aWidth=-1,$aHeight=-1,$aCachedName="",$aTimeOut=0,$aInline=true) {
	Graph::Graph($aWidth,$aHeight,$aCachedName,$aTimeOut,$aInline);
	$this->scale = new GanttScale($this->img);
	$this->img->SetMargin($aWidth/17,$aWidth/17,$aHeight/7,$aHeight/10);

	$this->scale->ShowHeaders(GANTT_HWEEK|GANTT_HDAY);
	$this->SetBox();
    }

//---------------
// PUBLIC METHODS
    // Set what headers should be shown
    function ShowHeaders($aFlg) {
	$this->scale->ShowHeaders($aFlg);
    }

    // Specify the fraction of the font height that should be added
    // as vertical margin
    function SetLabelVMarginFactor($aVal) {
	$this->iLabelVMarginFactor = $aVal;
    }

    // Add a new Gantt object
    function Add(&$aObject) {
	if( is_array($aObject) ) {
	    for($i=0; $i<count($aObject); ++$i)
		$this->iObj[] = $aObject[$i];
	}
	else
	    $this->iObj[] = $aObject;
    }

    // Override inherit method from Graph and give a warning message
    function SetScale() {
	JpGraphError::Raise("<b>JpGraph Error:</b> SetScale() is not meaningfull with Gantt charts.");
	// Empty
    }

    // Specify the date range for Gantt graphs (if this is not set it will be
    // automtically determined from the input data)
    function SetDateRange($aStart,$aEnd) {
	$this->scale->SetRange($aStart,$aEnd);
    }

    // Get the maximum width of the titles for the bars
    function GetMaxLabelWidth() {
	$m=0;
	if( $this->iObj != null ) {
	    $m = $this->iObj[0]->title->GetWidth($this->img);
	    for($i=1; $i<count($this->iObj); ++$i) {
		if( $this->iObj[$i]->title->HasTabs() ) {
		    list($tot,$w) = $this->iObj[$i]->title->GetWidth($this->img,true);
		    $m=max($m,$tot);
		}
		else
		    $m=max($m,$this->iObj[$i]->title->GetWidth($this->img));
	    }
	}
	return $m;
    }

    // Get the maximum height of the titles for the bars
    function GetMaxLabelHeight() {
	$m=0;
	if( $this->iObj != null ) {
	    $m = $this->iObj[0]->title->GetHeight($this->img);
	    for($i=1; $i<count($this->iObj); ++$i) {
		$m=max($m,$this->iObj[$i]->title->GetHeight($this->img));
	    }
	}
	return $m;
    }

    function GetMaxBarAbsHeight() {
	$m=0;
	if( $this->iObj != null ) {
	    $m = $this->iObj[0]->GetAbsHeight($this->img);
	    for($i=1; $i<count($this->iObj); ++$i) {
		$m=max($m,$this->iObj[$i]->GetAbsHeight($this->img));
	    }
	}
	return $m;
    }

    // Get the maximum used line number (vertical position) for bars
    function GetBarMaxLineNumber() {
	$m=0;
	if( $this->iObj != null ) {
	    $m = $this->iObj[0]->GetLineNbr();
	    for($i=1; $i<count($this->iObj); ++$i) {
		$m=max($m,$this->iObj[$i]->GetLineNbr());
	    }
	}
	return $m;
    }

    // Get the minumum and maximum used dates for all bars
    function GetBarMinMax() {
	$max=$this->scale->NormalizeDate($this->iObj[0]->GetMaxDate());
	$min=$this->scale->NormalizeDate($this->iObj[0]->GetMinDate());
	for($i=1; $i<count($this->iObj); ++$i) {
	    $max=Max($max,$this->scale->NormalizeDate($this->iObj[$i]->GetMaxDate()));
	    $min=Min($min,$this->scale->NormalizeDate($this->iObj[$i]->GetMinDate()));
	}
	$minDate = date("Y-m-d",$min);
	$min = strtotime($minDate);
	$maxDate = date("Y-m-d",$max);
	$max = strtotime($maxDate);
	return array($min,$max);
    }

    // Stroke the gantt chart
    function Stroke($aStrokeFileName="") {

	// Should we autoscale dates?
	if( !$this->scale->IsRangeSet() ) {
	    list($min,$max) = $this->GetBarMinMax();
	    $this->scale->SetRange($min,$max);
	}

	if( $this->img->img == null ) {
	    // The predefined left, right, top, bottom margins.
	    // Note that the top margin might incease depending on
	    // the title.
	    $lm=30;$rm=30;$tm=20;$bm=30;
	    if( BRAND_TIMING ) $bm += 10;

	    // First find out the height
	    $n=$this->GetBarMaxLineNumber()+1;
	    $m=max($this->GetMaxLabelHeight(),$this->GetMaxBarAbsHeight());
	    $height=$n*((1+$this->iLabelVMarginFactor)*$m);

	    // Add the height of the scale titles
	    $h=$this->scale->GetHeaderHeight();
	    $height += $h;

	    // Calculate the top margin needed for title and subtitle
	    if( $this->title->t != "" ) {
		$tm += $this->title->GetFontHeight($this->img);
	    }
	    if( $this->subtitle->t != "" ) {
		$tm += $this->subtitle->GetFontHeight($this->img);
	    }

	    // ...and then take the bottom and top plot margins into account
	    $height += $tm + $bm + $this->scale->iTopPlotMargin + $this->scale->iBottomPlotMargin;

	    // Now find the minimum width for the chart required
	    $fw=$this->scale->day->GetFontWidth($this->img)+4;
	    $nd=$this->scale->GetNumberOfDays();
	    if( !$this->scale->IsDisplayDay() ) {
				// If we don't display the individual days we can shrink the
				// scale a little bit. This is a little bit pragmatic at the
				// moment and should be re-written to take into account
				// a) What scales exactly are shown and
				// b) what format do they use so we know how wide we need to
				// make each scale text space at minimum.
		$fw /= 2;
		if( !$this->scale->IsDisplayWeek() ) {
		    $fw /= 1.8;
		}
	    }

	    // Now determine the width for the activity titles column
	    // This is complicated by the fact that the titles may have
	    // tabs. In that case we also need to calculate the individual
	    // tab positions based on the width of the individual columns

	    $titlewidth = $this->GetMaxLabelWidth();

	    // Now get the total width taking
	    // titlewidth, left and rigt margin, dayfont size
	    // into account
	    $width = $titlewidth + $nd*$fw + $lm+$rm;

	    $this->img->CreateImgCanvas($width,$height);
	    $this->img->SetMargin($lm,$rm,$tm,$bm);
	}

	// Should we start from the top or just spread the bars out even over the
	// available height
	$this->scale->SetVertLayout($this->iLayout);
	if( $this->iLayout == GANTT_FROMTOP ) {
	    $maxheight=max($this->GetMaxLabelHeight(),$this->GetMaxBarAbsHeight());
	    $this->scale->SetVertSpacing($maxheight*(1+$this->iLabelVMarginFactor));
	}
	// If it hasn't been set find out the maximum line number
	if( $this->scale->iVertLines == -1 )
	    $this->scale->iVertLines = $this->GetBarMaxLineNumber()+1;

	$maxwidth=max($this->GetMaxLabelWidth(),$this->scale->tableTitle->GetWidth($this->img));
	$this->scale->SetLabelWidth($maxwidth*(1+$this->iLabelHMarginFactor));
	$this->StrokePlotArea();
	$this->scale->Stroke();
	$this->StrokePlotBox();

	for($i=0; $i<count($this->iObj); ++$i) {
	    $this->iObj[$i]->SetLabelLeftMargin(round($maxwidth*$this->iLabelHMarginFactor/2));
	    $this->iObj[$i]->Stroke($this->img,$this->scale);
	}

	$this->StrokeTitles();
	$this->cache->PutAndStream($this->img,$this->cache_name,$this->inline,$aStrokeFileName);
    }
}

//===================================================
// CLASS TextProperty
// Description: Holds properties for a text
//===================================================
class TextProperty {
    var $iFFamily=FF_FONT1,$iFStyle=FS_NORMAL,$iFSize=10;
    var $iColor="black";
    var $iShow=true;
    var $iText="";
    var $iHAlign="left",$iVAlign="bottom";

//---------------
// CONSTRUCTOR
    function TextProperty($aTxt="") {
	$this->iText = $aTxt;
    }

//---------------
// PUBLIC METHODS
    function Set($aTxt) {
	$this->iText = $aTxt;
    }

    // Set text color
    function SetColor($aColor) {
	$this->iColor = $aColor;
    }

    function HasTabs() {
	return substr_count($this->iText,"\t") > 0;
    }

    // Get number of tabs in string
    function GetNbrTabs() {
	substr_count($this->iText,"\t");
    }

    // Set alignment
    function Align($aHAlign,$aVAlign="bottom") {
	$this->iHAlign=$aHAlign;
	$this->iVAlign=$aVAlign;
    }

    // Specify font
    function SetFont($aFFamily,$aFStyle=FS_NORMAL,$aFSize=10) {
	$this->iFFamily = $aFFamily;
	$this->iFStyle	 = $aFStyle;
	$this->iFSize	 = $aFSize;
    }

    // Get width of text. If text contains several columns separated by
    // tabs then return both the total width as well as an array with a
    // width for each column.
    function GetWidth($aImg,$aUseTabs=false,$aTabExtraMargin=1.1) {
	if( strlen($this->iText)== 0 ) return;
	$tmp = explode("\t",$this->iText);
	$aImg->SetFont($this->iFFamily,$this->iFStyle,$this->iFSize);
	if( count($tmp) <= 1 || !$aUseTabs ) {
	    return $aImg->GetTextWidth($this->iText);
	}
	else {
	    $tot=0;
	    for($i=0; $i<count($tmp); ++$i) {
		$res[$i] = $aImg->GetTextWidth($tmp[$i]);
		$tot += $res[$i]*$aTabExtraMargin;
	    }
	    return array($tot,$res);
	}
    }

    // Get total height of text
    function GetHeight($aImg) {
	$aImg->SetFont($this->iFFamily,$this->iFStyle,$this->iFSize);
	return $aImg->GetFontHeight();
    }

    // Unhide/hide the text
    function Show($aShow) {
	$this->iShow=$aShow;
    }

    // Stroke text at (x,y) coordinates. If the text contains tabs then the
    // x parameter should be an array of positions to be used for each successive
    // tab mark. If no array is supplied then the tabs will be ignored.
    function Stroke($aImg,$aX,$aY) {
	if( $this->iShow ) {
	    $aImg->SetColor($this->iColor);
	    $aImg->SetFont($this->iFFamily,$this->iFStyle,$this->iFSize);
	    $aImg->SetTextAlign($this->iHAlign,$this->iVAlign);
	    if( $this->GetNbrTabs() <= 1 || !is_array($aX) ) {
				// Get rid of any "\t" characters and stroke string
		$aImg->StrokeText($aX,$aY,str_replace("\t"," ",$this->iText));
	    }
	    else {
		$tmp = explode("\t",$this->iText);
		$n = min(count($tmp),count($aX));
		for($i=0; $i<$n; ++$i) {
		    $aImg->StrokeText($aX[$i],$aY,$tmp[$i]);
		}
	    }
	}
    }
}

//===================================================
// CLASS HeaderProperty
// Description: Data encapsulating class to hold property
// for each type of the scale headers
//===================================================
class HeaderProperty {
    var $iTitleVertMargin=3,$iFFamily=FF_FONT0,$iFStyle=FS_NORMAL,$iFSize=8;
    var $iFrameColor="black",$iFrameWeight=1;
    var $iShowLabels=true,$iShowGrid=true;
    var $iBackgroundColor="white";
    var $iWeekendBackgroundColor="lightgray",$iSundayTextColor="red"; // these are only used with day scale
    var $iTextColor="black";
    var $iLabelFormStr="%d";
    var $grid,$iStyle=0;

//---------------
// CONSTRUCTOR
    function HeaderProperty() {
	$this->grid = new LineProperty();
    }

//---------------
// PUBLIC METHODS
    function Show($aShow) {
	$this->iShowLabels = $aShow;
    }

    function SetFont($aFFamily,$aFStyle=FS_NORMAL,$aFSize=10) {
	$this->iFFamily = $aFFamily;
	$this->iFStyle	 = $aFStyle;
	$this->iFSize	 = $aFSize;
    }

    function SetFontColor($aColor) {
	$this->iTextColor = $aColor;
    }

    function GetFontHeight($aImg) {
	$aImg->SetFont($this->iFFamily,$this->iFStyle,$this->iFSize);
	return $aImg->GetFontHeight();
    }

    function GetFontWidth($aImg) {
	$aImg->SetFont($this->iFFamily,$this->iFStyle,$this->iFSize);
	return $aImg->GetFontWidth();
    }

    function SetStyle($aStyle) {
	$this->iStyle = $aStyle;
    }

    function SetBackgroundColor($aColor) {
	$this->iBackgroundColor=$aColor;
    }

    function SetFrameWeight($aWeight) {
	$this->iFrameWeight=$aWeight;
    }

    function SetFrameColor($aColor) {
	$this->iFrameColor=$aColor;
    }

    // Only used by day scale
    function SetWeekendColor($aColor) {
	$this->iWeekendBackgroundColor=$aColor;
    }

    // Only used by day scale
    function SetSundayFontColor($aColor) {
	$this->iSundayTextColor=$aColor;
    }

    function SetTitleVertMargin($aMargin) {
	$this->iTitleVertMargin=$aMargin;
    }

    function SetLabelFormatString($aStr) {
	$this->iLabelFormStr=$aStr;
    }
}

//===================================================
// CLASS GanttScale
// Description: Responsible for calculating and showing
// the scale in a gantt chart. This includes providing methods for
// converting dates to position in the chart as well as stroking the
// date headers (days, week, etc).
//===================================================
class GanttScale {
    var $day,$week,$month,$year;
    var $divider,$dividerh,$tableTitle;
    var $iStartDate=-1,$iEndDate=-1;
    // Number of gantt bar position (n.b not necessariliy the same as the number of bars)
    // we could have on bar in position 1, and one bar in position 5 then there are two
    // bars but the number of bar positions is 5
    var $iVertLines=-1;
    // The width of the labels (defaults to the widest of all labels)
    var $iLabelWidth;
    // Out image to stroke the scale to
    var $iImg;
    var $iTableHeaderBackgroundColor="white",$iTableHeaderFrameColor="black";
    var $iTableHeaderFrameWeight=1;
    var $iAvailableHeight=-1,$iVertSpacing=-1,$iVertHeaderSize=-1;
    var $iDateLocale;
    var $iVertLayout=GANTT_EVEN;
    var $iTopPlotMargin=10,$iBottomPlotMargin=15;
    var $iUsePlotWeekendBackground=true;

//---------------
// CONSTRUCTOR
    function GanttScale(&$aImg) {
	$this->iImg = &$aImg;
	$this->iDateLocale = new DateLocale();
	$this->day = new HeaderProperty();
	$this->day->grid->SetColor("gray");

	$this->week = new HeaderProperty();
	$this->week->SetLabelFormatString("w%d");
	$this->week->SetFont(FF_FONT1);

	$this->month = new HeaderProperty();
	$this->month->SetFont(FF_FONT1,FS_BOLD);

	$this->year = new HeaderProperty();
	$this->year->SetFont(FF_FONT1,FS_BOLD);

	$this->divider=new LineProperty();
	$this->dividerh=new LineProperty();
	$this->tableTitle=new TextProperty();
    }

//---------------
// PUBLIC METHODS
    // Specify what headers should be visible
    function ShowHeaders($aFlg) {
	$this->day->Show($aFlg & GANTT_HDAY);
	$this->week->Show($aFlg & GANTT_HWEEK);
	$this->month->Show($aFlg & GANTT_HMONTH);
	$this->year->Show($aFlg & GANTT_HYEAR);

	// Make some default settings of gridlines whihc makes sense
	if( $aFlg & GANTT_HWEEK ) {
	    $this->month->grid->Show(false);
	    $this->year->grid->Show(false);
	}
    }

    // Should the weekend background stretch all the way down in the plotarea
    function UseWeekendBackground($aShow) {
	$this->iUsePlotWeekendBackground = $aShow;
    }

    // Have a range been specified?
    function IsRangeSet() {
	return $this->iStartDate!=-1 && $this->iEndDate!=-1;
    }

    // Should the layout be from top or even?
    function SetVertLayout($aLayout) {
	$this->iVertLayout = $aLayout;
    }

    // Which locale should be used?
    function SetDateLocale($aLocale) {
	$this->iDateLocale->Set($aLocale);
    }

    // Number of days we are showing
    function GetNumberOfDays() {
	return round(($this->iEndDate-$this->iStartDate)/SECPERDAY)+1;
    }

    // The widthj of the actual plot area
    function GetPlotWidth() {
	$img=$this->iImg;
	return $img->width - $img->left_margin - $img->right_margin;
    }

    // Specify the width of the titles(labels) for the activities
    // (This is by default set to the minimum width enought for the
    // widest title)
    function SetLabelWidth($aLabelWidth) {
	$this->iLabelWidth=$aLabelWidth;
    }

    // Do we show day scale?
    function IsDisplayDay() {
	return $this->day->iShowLabels;
    }

    // Do we show week scale?
    function IsDisplayWeek() {
	return $this->week->iShowLabels;
    }

    // Do we show month scale?
    function IsDisplayMonth() {
	return $this->month->iShowLabels;
    }

    // Do we show year scale?
    function IsDisplayYear() {
	return $this->year->iShowLabels;
    }

    // Specify spacing (in percent of bar height) between activity bars
    function SetVertSpacing($aSpacing) {
	$this->iVertSpacing = $aSpacing;
    }

    // Specify scale min and max date either as timestamp or as date strings
    // Always round to the nearest week boundary
    function SetRange($aMin,$aMax) {
	$this->iStartDate = $this->NormalizeDate($aMin);
	$this->iEndDate = $this->NormalizeDate($aMax);

	// Get day in week Sun=0
	$ds=strftime("%w",$this->iStartDate);
	$de=strftime("%w",$this->iEndDate);

	if( $ds==0 ) $ds=7;
	if( $de==0 ) $de=7;

	// We want to start on Monday
	$this->iStartDate -= SECPERDAY*($ds-1);

	// We want to end on a Sunday
	$this->iEndDate += SECPERDAY*(7-$de);
    }

    // Specify background for the table title area (upper left corner of the table)
    function SetTableTitleBackground($aColor) {
	$this->iTableHeaderBackgroundColor = $aColor;
    }

///////////////////////////////////////
// PRIVATE Methods

    // Determine the height of all the scale headers combined
    function GetHeaderHeight() {
	$img=$this->iImg;
	$height=1;
	if( $this->day->iShowLabels ) {
	    $height += $this->day->GetFontHeight($img);
	    $height += $this->day->iTitleVertMargin;
	}
	if( $this->week->iShowLabels ) {
	    $height += $this->week->GetFontHeight($img);
	    $height += $this->week->iTitleVertMargin;
	}
	if( $this->month->iShowLabels ) {
	    $height += $this->month->GetFontHeight($img);
	    $height += $this->month->iTitleVertMargin;
	}
	if( $this->year->iShowLabels ) {
	    $height += $this->year->GetFontHeight($img);
	    $height += $this->year->iTitleVertMargin;
	}
	return $height;
    }

    // Get width (in pisels) for a single day
    function GetDayWidth() {
	return ($this->GetPlotWidth()-$this->iLabelWidth+1)/$this->GetNumberOfDays();
    }

    // Nuber of days in a year
    function GetNumDaysInYear($aYear) {
	if( $this->IsLeap($aYear) )
	    return 366;
	else
	    return 365;
    }

    // Get day number in year
    function GetDayNbrInYear($aDate) {
	return 0+strftime("%j",$aDate);
    }

    // Get week number
    function GetWeekNbr($aDate) {
	// We can't use the internal strftime() since it gets the weeknumber
	// wrong since it doesn't follow ISO.
	// Even worse is that this works differently if we are on a Windows
	// or UNIX box (it even differs between UNIX boxes how strftime()
	// is natively implemented)
	//
	// Credit to Nicolas Hoizey <nhoizey@phpheaven.net> for this elegant
	// version of Week Nbr calculation.

	$day = $this->NormalizeDate($aDate);

	/*-------------------------------------------------------------------------
	  According to ISO-8601 :
	  "Week 01 of a year is per definition the first week that has the Thursday in this year,
	  which is equivalent to the week that contains the fourth day of January.
	  In other words, the first week of a new year is the week that has the majority of its
	  days in the new year."

	  Be carefull, with PHP, -3 % 7 = -3, instead of 4 !!!

	  day of year             = date("z", $day) + 1
	  offset to thursday      = 3 - (date("w", $day) + 6) % 7
	  first thursday of year  = 1 + (11 - date("w", mktime(0, 0, 0, 1, 1, date("Y", $day)))) % 7
	  week number             = (thursday's day of year - first thursday's day of year) / 7 + 1
	  ---------------------------------------------------------------------------*/

	$thursday = $day + 60 * 60 * 24 * (3 - (date("w", $day) + 6) % 7);              // take week's thursday
	$week = 1 + (date("z", $thursday) - (11 - date("w", mktime(0, 0, 0, 1, 1, date("Y", $thursday)))) % 7) / 7;

	return $week;
    }

    // Is year a leap year?
    function IsLeap($aYear) {
	// Is the year a leap year?
	//$year = 0+date("Y",$aDate);
	if( $aYear % 4 == 0)
	    if( !($aYear % 100 == 0) || ($aYear % 400 == 0) )
		return true;
	return false;
    }

    // Get current year
    function GetYear($aDate) {
	return 0+Date("Y",$aDate);
    }

    // Return number of days in a year
    function GetNumDaysInMonth($aMonth,$aYear) {
	$days=array(31,28,31,30,31,30,31,31,30,31,30,31);
	$daysl=array(31,29,31,30,31,30,31,31,30,31,30,31);
	if( $this->IsLeap($aYear))
	    return $daysl[$aMonth];
	else
	    return $days[$aMonth];
    }

    // Get day in month
    function GetMonthDayNbr($aDate) {
	return 0+strftime("%d",$aDate);
    }

    // Get day in year
    function GetYearDayNbr($aDate) {
	return 0+strftime("%j",$aDate);
    }

    // Get month number
    function GetMonthNbr($aDate) {
	return 0+strftime("%m",$aDate);
    }

    // Translate a date to screen coordinates	(horizontal scale)
    function TranslateDate($aDate) {
	$aDate = $this->NormalizeDate($aDate);
	$img=$this->iImg;
	if( $aDate < $this->iStartDate || $aDate > $this->iEndDate )
	    JpGraphError::Raise("<b>JpGraph Error:</b> Date is outside specified scale range.");
	return ($aDate-$this->iStartDate)/SECPERDAY*$this->GetDayWidth()+$img->left_margin+$this->iLabelWidth;;
    }

    // Get screen coordinatesz for the vertical position for a bar
    function TranslateVertPos($aPos) {
	$img=$this->iImg;
	$ph=$this->iAvailableHeight;
	if( $aPos > $this->iVertLines )
	    JpGraphError::Raise("<b>JpGraph Error:</b> Illegal vertical position $aPos");
	if( $this->iVertLayout == GANTT_EVEN ) {
	    // Position the top bar at 1 vert spacing from the scale
	    return round($img->top_margin + $this->iVertHeaderSize +  ($aPos+1)*$this->iVertSpacing);
	}
	else {
	    // position the top bar at 1/2 a vert spacing from the scale
	    return round($img->top_margin + $this->iVertHeaderSize  + $this->iTopPlotMargin + ($aPos+1)*$this->iVertSpacing);
	}
    }

    // What is the vertical spacing?
    function GetVertSpacing() {
	return $this->iVertSpacing;
    }

    // Convert a date to timestamp
    function NormalizeDate($aDate) {
	if( is_string($aDate) )
	    return strtotime($aDate);
	elseif( is_int($aDate) || is_float($aDate) )
	    return $aDate;
	else
	    JpGraphError::Raise("<b>JpGraph Error:</b> Unknown date format in GanttScale ($aDate).");
    }

    // Stroke the day scale (including gridlines)
    function StrokeDays($aYCoord) {
	$wdays=$this->iDateLocale->GetDayAbb();
	$img=$this->iImg;
	$daywidth=$this->GetDayWidth();
	$xt=$img->left_margin+$this->iLabelWidth;
	$yt=$aYCoord+$img->top_margin;
	if( $this->day->iShowLabels ) {
	    $img->SetFont($this->day->iFFamily,$this->day->iFStyle,$this->day->iFSize);
	    $xb=$img->width-$img->right_margin;
	    $yb=$yt + $img->GetFontHeight() + $this->day->iTitleVertMargin + $this->day->iFrameWeight;
	    $img->SetColor($this->day->iBackgroundColor);
	    $img->FilledRectangle($xt,$yt,$xb,$yb);

	    $img->SetColor($this->day->grid->iColor);
	    $x = $xt;
	    $img->SetTextAlign("center");
	    for($i=0; $i<$this->GetNumberOfDays(); ++$i, $x+=$daywidth) {
		$day=$i%7;
		if( $day==5 ) {
		    $img->PushColor($this->day->iWeekendBackgroundColor);
		    if( $this->iUsePlotWeekendBackground )
			$img->FilledRectangle($x,$yt+$this->day->iFrameWeight,$x+2*$daywidth,$img->height-$img->bottom_margin);
		    else
			$img->FilledRectangle($x,$yt+$this->day->iFrameWeight,$x+2*$daywidth,$yb-$this->day->iFrameWeight);
		    $img->PopColor();
		}
		if( $day==6 )
		    $img->PushColor($this->day->iSundayTextColor);
		else
		    $img->PushColor($this->day->iTextColor);
		$img->StrokeText(round($x+$daywidth/2+1),
				 round($yb-$this->day->iTitleVertMargin),
				 $wdays[$i%7]);
		$img->PopColor();
		$img->Line($x,$yt,$x,$yb);
		$this->day->grid->Stroke($img,$x,$yb,$x,$img->height-$img->bottom_margin);
	    }
	    $img->SetColor($this->day->iFrameColor);
	    $img->SetLineWeight($this->day->iFrameWeight);
	    $img->Rectangle($xt,$yt,$xb,$yb);
	    return $yb - $img->top_margin;
	}
	return $aYCoord;
    }

    // Stroke week header and grid
    function StrokeWeeks($aYCoord) {
	$wdays=$this->iDateLocale->GetDayAbb();
	$img=$this->iImg;
	$weekwidth=$this->GetDayWidth()*7;
	$xt=$img->left_margin+$this->iLabelWidth;
	$yt=$aYCoord+$img->top_margin;
	$img->SetFont($this->week->iFFamily,$this->week->iFStyle,$this->week->iFSize);
	$xb=$img->width-$img->right_margin;
	$yb=$yt + $img->GetFontHeight() + $this->week->iTitleVertMargin + $this->week->iFrameWeight;

	$week = $this->iStartDate;
	$weeknbr=$this->GetWeekNbr($week);
	if( $this->week->iShowLabels ) {
	    $img->SetColor($this->week->iBackgroundColor);
	    $img->FilledRectangle($xt,$yt,$xb,$yb);
	    $img->SetColor($this->week->grid->iColor);
	    $x = $xt;
	    if( $this->week->iStyle==WEEKSTYLE_WNBR ) {
		$img->SetTextAlign("center");
		$txtOffset = $weekwidth/2+1;
	    }
	    elseif( $this->week->iStyle==WEEKSTYLE_FIRSTDAY || $this->week->iStyle==WEEKSTYLE_FIRSTDAY2 ) {
		$img->SetTextAlign("left");
		$txtOffset = 2;
	    }
	    else
		JpGraphError::Raise("<b>JpGraph Error:</b>Unknown formatting style for week.");

	    for($i=0; $i<$this->GetNumberOfDays()/7; ++$i, $x+=$weekwidth) {
		$img->PushColor($this->week->iTextColor);

		if( $this->week->iStyle==WEEKSTYLE_WNBR )
		    $txt = sprintf($this->week->iLabelFormStr,$weeknbr);
		elseif( $this->week->iStyle==WEEKSTYLE_FIRSTDAY )
		    $txt = date("j/n",$week);
		elseif( $this->week->iStyle==WEEKSTYLE_FIRSTDAY2 ) {
		    $monthnbr = date("n",$week)-1;
		    $shortmonth = $this->iDateLocale->GetShortMonthName($monthnbr);
		    $txt = Date("j",$week)." ".$shortmonth;
		}

		$img->StrokeText(round($x+$txtOffset),round($yb-$this->week->iTitleVertMargin),$txt);

		$week += 7*SECPERDAY;
		$weeknbr = $this->GetWeekNbr($week);
		$img->PopColor();
		$img->Line($x,$yt,$x,$yb);
		$this->week->grid->Stroke($img,$x,$yb,$x,$img->height-$img->bottom_margin);
	    }
	    $img->SetColor($this->week->iFrameColor);
	    $img->SetLineWeight($this->week->iFrameWeight);
	    $img->Rectangle($xt,$yt,$xb,$yb);
	    return $yb-$img->top_margin;
	}
	return $aYCoord;
    }

    // Format the mont scale header string
    function GetMonthLabel($aMonthNbr,$year) {
	$sn = $this->iDateLocale->GetShortMonthName($aMonthNbr);
	$ln = $this->iDateLocale->GetLongMonthName($aMonthNbr);
	switch($this->month->iStyle) {
	    case MONTHSTYLE_SHORTNAME:
		$m=$sn;
	    break;
	    case MONTHSTYLE_LONGNAME:
		$m=$ln;
	    break;
	    case MONTHSTYLE_SHORTNAMEYEAR2:
		$m=$sn." '".substr("".$year,2);
	    break;
	    case MONTHSTYLE_SHORTNAMEYEAR4:
		$m=$sn." ".$year;
	    break;
	    case MONTHSTYLE_LONGNAMEYEAR2:
		$m=$ln." '".substr("".$year,2);
	    break;
	    case MONTHSTYLE_LONGNAMEYEAR4:
		$m=$ln." ".$year;
	    break;
	}
	return $m;
    }

    // Stroke month scale and gridlines
    function StrokeMonths($aYCoord) {
	if( $this->month->iShowLabels ) {
	    $monthnbr = $this->GetMonthNbr($this->iStartDate)-1;
	    $img=$this->iImg;

	    $xt=$img->left_margin+$this->iLabelWidth;
	    $yt=$aYCoord+$img->top_margin;
	    $img->SetFont($this->month->iFFamily,$this->month->iFStyle,$this->month->iFSize);
	    $xb=$img->width-$img->right_margin;
	    $yb=$yt + $img->GetFontHeight() + $this->month->iTitleVertMargin + $this->month->iFrameWeight;

	    $img->SetColor($this->month->iBackgroundColor);
	    $img->FilledRectangle($xt,$yt,$xb,$yb);

	    $img->SetLineWeight($this->month->grid->iWeight);
	    $img->SetColor($this->month->iTextColor);
	    $year = 0+strftime("%Y",$this->iStartDate);
	    $img->SetTextAlign("center");
	    $monthwidth=$this->GetDayWidth()*($this->GetNumDaysInMonth($monthnbr,$year)-$this->GetMonthDayNbr($this->iStartDate)+1);
	    // Is it enough space to stroke the first month?
	    $monthName = $this->GetMonthLabel($monthnbr,$year);
	    if( $monthwidth >= 1.2*$img->GetTextWidth($monthName) ) {
		$img->SetColor($this->month->iTextColor);
		$img->StrokeText(round($xt+$monthwidth/2+1),
				 round($yb-$this->month->iTitleVertMargin),
				 $monthName);
	    }
	    $x = $xt + $monthwidth;
	    while( $x < $xb ) {
		$img->SetColor($this->month->grid->iColor);
		$img->Line($x,$yt,$x,$yb);
		$this->month->grid->Stroke($img,$x,$yb,$x,$img->height-$img->bottom_margin);
		$monthnbr++;
		if( $monthnbr==12 ) {
		    $monthnbr=0;
		    $year++;
		}
		$monthName = $this->GetMonthLabel($monthnbr,$year);
		$monthwidth=$this->GetDayWidth()*$this->GetNumDaysInMonth($monthnbr,$year);
		if( $x + $monthwidth < $xb )
		    $w = $monthwidth;
		else
		    $w = $xb-$x;
		if( $w >= 1.2*$img->GetTextWidth($monthName) ) {
		    $img->SetColor($this->month->iTextColor);
		    $img->StrokeText(round($x+$w/2+1),
				     round($yb-$this->month->iTitleVertMargin),$monthName);
		}
		$x += $monthwidth;
	    }
	    $img->SetColor($this->month->iFrameColor);
	    $img->SetLineWeight($this->month->iFrameWeight);
	    $img->Rectangle($xt,$yt,$xb,$yb);
	    return $yb-$img->top_margin;
	}
	return $aYCoord;
    }

    // Stroke year scale and gridlines
    function StrokeYears($aYCoord) {
	if( $this->year->iShowLabels ) {
	    $year = $this->GetYear($this->iStartDate);
	    $img=$this->iImg;

	    $xt=$img->left_margin+$this->iLabelWidth;
	    $yt=$aYCoord+$img->top_margin;
	    $img->SetFont($this->year->iFFamily,$this->year->iFStyle,$this->year->iFSize);
	    $xb=$img->width-$img->right_margin;
	    $yb=$yt + $img->GetFontHeight() + $this->year->iTitleVertMargin + $this->year->iFrameWeight;

	    $img->SetColor($this->year->iBackgroundColor);
	    $img->FilledRectangle($xt,$yt,$xb,$yb);
	    $img->SetLineWeight($this->year->grid->iWeight);
	    $img->SetTextAlign("center");
	    if( $year == $this->GetYear($this->iEndDate) )
		$yearwidth=$this->GetDayWidth()*($this->GetYearDayNbr($this->iEndDate)-$this->GetYearDayNbr($this->iStartDate)+1);
	    else
		$yearwidth=$this->GetDayWidth()*($this->GetNumDaysInYear($year)-$this->GetYearDayNbr($this->iStartDate)+1);

	    // The space for a year must be at least 20% bigger than the actual text
	    // so we allow 10% margin on each side
	    if( $yearwidth >= 1.20*$img->GetTextWidth("".$year) ) {
		$img->SetColor($this->year->iTextColor);
		$img->StrokeText(round($xt+$yearwidth/2+1),
				 round($yb-$this->year->iTitleVertMargin),
				 $year);
	    }
	    $x = $xt + $yearwidth;
	    while( $x < $xb ) {
		$img->SetColor($this->year->grid->iColor);
		$img->Line($x,$yt,$x,$yb);
		$this->year->grid->Stroke($img,$x,$yb,$x,$img->height-$img->bottom_margin);
		$year += 1;
		$yearwidth=$this->GetDayWidth()*$this->GetNumDaysInYear($year);
		if( $x + $yearwidth < $xb )
		    $w = $yearwidth;
		else
		    $w = $xb-$x;
		if( $w >= 1.2*$img->GetTextWidth("".$year) ) {
		    $img->SetColor($this->year->iTextColor);
		    $img->StrokeText(round($x+$w/2+1),
				     round($yb-$this->year->iTitleVertMargin),
				     $year);
		}
		$x += $yearwidth;
	    }
	    $img->SetColor($this->year->iFrameColor);
	    $img->SetLineWeight($this->year->iFrameWeight);
	    $img->Rectangle($xt,$yt,$xb,$yb);
	    return $yb-$img->top_margin;
	}
	return $aYCoord;
    }

    // Stroke table title (upper left corner)
    function StrokeTableHeaders($aYBottom) {
	$img=$this->iImg;
	$xt=$img->left_margin;
	$yt=$img->top_margin;
	$xb=$xt+$this->iLabelWidth;
	$yb=$aYBottom+$img->top_margin;

	$img->SetColor($this->iTableHeaderBackgroundColor);
	$img->FilledRectangle($xt,$yt,$xb,$yb);
	$this->tableTitle->Align("center","center");
	$this->tableTitle->Stroke($img,$xt+($xb-$xt)/2+1,$yt+($yb-$yt)/2);
	$img->SetColor($this->iTableHeaderFrameColor);
	$img->SetLineWeight($this->iTableHeaderFrameWeight);
	$img->Rectangle($xt,$yt,$xb,$yb);

	// Draw the vertical dividing line
	$this->divider->Stroke($img,$xb,$yt,$xb,$img->height-$img->bottom_margin);

	// Draw the horizontal dividing line
	$this->dividerh->Stroke($img,$xt,$yb,$img->width-$img->right_margin,$yb);
    }

    // Main entry point to stroke scale
    function Stroke() {
	if( !$this->IsRangeSet() )
	    JpGraphError::Raise("<b>JpGraph Error:</b> Gantt scale has not been specified.");
	$img=$this->iImg;

	// Stroke all headers. Aa argument we supply the offset from the
	// top which depends on any previous headers
	$offy=$this->StrokeYears(0);
	$offm=$this->StrokeMonths($offy);
	$offw=$this->StrokeWeeks($offm);
	$offd=$this->StrokeDays($offw);

	// We stroke again in case days also have gridlines that may have
	// overwritten the weeks gridline (or month/year). It may seem that we should have logic
	// in the days routine instead but this is much easier and wont make to much
	// of an performance impact.
	$this->StrokeWeeks($offm);
	$this->StrokeMonths($offy);
	$this->StrokeYears(0);
	$this->StrokeTableHeaders($offd);

	// Now we can calculate the correct scaling factor for each vertical position
	$this->iAvailableHeight = $img->height - $img->top_margin - $img->bottom_margin - $offd;
	$this->iVertHeaderSize = $offd;
	if( $this->iVertSpacing == -1 )
	    $this->iVertSpacing = $this->iAvailableHeight / $this->iVertLines;
    }
}

//===================================================
// CLASS GanttPlotObject
// The common signature for a Gantt object
//===================================================
class GanttPlotObject {
    var $iVPos=0;					// Vertical position
    var $iLabelLeftMargin=2;	// Title margin
    var $iStart="";				// Start date
    var $title,$caption;
    var $iCaptionMargin=5;

    function GanttPlotObject() {
	$this->title = new TextProperty();
	$this->title->Align("left","center");
	$this->caption = new TextProperty();
    }

    function GetMinDate() {
	return $this->iStart;
    }

    function GetMaxDate() {
	return $this->iStart;
    }

    function SetCaptionMargin($aMarg) {
	$this->iCaptionMargin=$aMarg;
    }

    function GetAbsHeight($aImg) {
	return 0;
    }

    function GetLineNbr() {
	return $this->iVPos;
    }

    function SetLabelLeftMargin($aOff) {
	$this->iLabelLeftMargin=$aOff;
    }
}

//===================================================
// CLASS Progress
// Holds parameters for the progress indicator
// displyed within a bar
//===================================================
class Progress {
    var $iProgress=-1, $iColor="black", $iPattern=GANTT_SOLID;
    var $iDensity=98, $iHeight=0.65;

    function Set($aProg) {
	if( $aProg < 0.0 || $aProg > 1.0 )
	    JpGraphError::Raise("<b>JpGraph Error:</b> Progress value must in range [0, 1]");
	$this->iProgress = $aProg;
    }

    function SetPattern($aPattern,$aColor="blue",$aDensity=98) {
	$this->iPattern = $aPattern;
	$this->iColor = $aColor;
	$this->iDensity = $aDensity;
    }

    function SetHeight($aHeight) {
	$this->iHeight = $aHeight;
    }
}

//===================================================
// CLASS GanttBar
// Responsible for formatting individual gantt bars
//===================================================
class GanttBar extends GanttPlotObject {
    var $iEnd;
    var $iHeightFactor=0.5;
    var $iFillColor="white",$iFrameColor="blue";
    var $iShadow=false,$iShadowColor="darkgray",$iShadowWidth=1,$iShadowFrame="black";
    var $iPattern=GANTT_RDIAG,$iPatternColor="blue",$iPatternDensity=95;
    var $leftMark,$rightMark;
    var $progress;

//---------------
// CONSTRUCTOR
    function GanttBar($aPos,$aLabel,$aStart,$aEnd,$aCaption="",$aHeightFactor=0.6) {
	parent::GanttPlotObject();
	$this->iStart = $aStart;
	// Is the end date given as a date or as number of days added to start date?
	if( is_string($aEnd) )
	    $this->iEnd = strtotime($aEnd)+SECPERDAY;
	elseif(is_int($aEnd) || is_float($aEnd) )
	    $this->iEnd = strtotime($aStart)+round($aEnd*SECPERDAY);
	$this->iVPos = $aPos;
	$this->iHeightFactor = $aHeightFactor;
	$this->title->Set($aLabel);
	$this->caption = new TextProperty($aCaption);
	$this->caption->Align("left","center");
	$this->leftMark =new PlotMark();
	$this->leftMark->Hide();
	$this->rightMark=new PlotMark();
	$this->rightMark->Hide();
	$this->progress = new Progress();
    }

//---------------
// PUBLIC METHODS
    function SetShadow($aShadow=true,$aColor="gray") {
	$this->iShadow=$aShadow;
	$this->iShadowColor=$aColor;
    }

    function GetMaxDate() {
	return $this->iEnd;
    }

    function SetHeight($aHeight) {
	$this->iHeightFactor = $aHeight;
    }

    function SetColor($aColor) {
	$this->iFrameColor = $aColor;
    }

    function SetFillColor($aColor) {
	$this->iFillColor = $aColor;
    }

    function GetAbsHeight($aImg) {
	if( is_int($this->iHeightFactor) || $this->leftMark->show || $this->rightMark->show ) {
	    $m=-1;
	    if( is_int($this->iHeightFactor) )
		$m = $this->iHeightFactor;
	    if( $this->leftMark->show )
		$m = max($m,$this->leftMark->width*2);
	    if( $this->rightMark->show )
		$m = max($m,$this->rightMark->width*2);
	    return $m;
	}
	else
	    return -1;
    }

    function SetPattern($aPattern,$aColor="blue",$aDensity=95) {
	$this->iPattern = $aPattern;
	$this->iPatternColor = $aColor;
	$this->iPatternDensity = $aDensity;
    }

    function Stroke($aImg,$aScale) {
	$factory = new RectPatternFactory();
	$prect = $factory->Create($this->iPattern,$this->iPatternColor);
	$prect->SetDensity($this->iPatternDensity);

	// If height factor is specified as a float between 0,1 then we take it as meaning
	// percetage of the scale width between horizontal line.
	// If it is an integer > 1 we take it to mean the absolute height in pixels
	if( $this->iHeightFactor > -0.0 && $this->iHeightFactor <= 1.1)
	    $vs = $aScale->GetVertSpacing()*$this->iHeightFactor;
	elseif(is_int($this->iHeightFactor) && $this->iHeightFactor>2 && $this->iHeightFactor<200)
	    $vs = $this->iHeightFactor;
	else
	    JpGraphError::Raise("<b>JpGraph Error:</b>Specified height (".$this->iHeightFactor.") for gantt bar is out of range.");

	$xt = $aScale->TranslateDate($aScale->NormalizeDate($this->iStart));
	$xb = $aScale->TranslateDate($aScale->NormalizeDate($this->iEnd));
	$yt = $aScale->TranslateVertPos($this->iVPos)-$vs-($aScale->GetVertSpacing()/2-$vs/2);
	$yb = $aScale->TranslateVertPos($this->iVPos)-($aScale->GetVertSpacing()/2-$vs/2);

	$prect->ShowFrame(false);
	$prect->SetBackground($this->iFillColor);
	if( $this->iShadow ) {
	    $aImg->SetColor($this->iFrameColor);
	    $aImg->ShadowRectangle($xt,$yt,$xb,$yb,$this->iFillColor,$this->iShadowWidth,$this->iShadowColor);
	    $prect->SetPos(new Rectangle($xt+1,$yt+1,$xb-$xt-$this->iShadowWidth-2,$yb-$yt-$this->iShadowWidth-2));
	    $prect->Stroke($aImg);
	}
	else {
	    $prect->SetPos(new Rectangle($xt,$yt,$xb-$xt+1,$yb-$yt+1));
	    $prect->Stroke($aImg);
	    $aImg->SetColor($this->iFrameColor);
	    $aImg->Rectangle($xt,$yt,$xb,$yb);
	}
	if( $this->progress->iProgress > 0 ) {
	    $prog = $factory->Create($this->progress->iPattern,$this->progress->iColor);
	    $prog->SetDensity($this->progress->iDensity);
	    $barheight = ($yb-$yt+1);
	    if( $this->iShadow )
		$barheight -= $this->iShadowWidth;
	    $progressheight = floor($barheight*$this->progress->iHeight);
	    $marg = ceil(($barheight-$progressheight)/2);
	    $pos = new Rectangle($xt,
	    $yt + $marg,
	    ($xb-$xt+1)*$this->progress->iProgress,
	    $barheight-2*$marg);
	    $prog->SetPos($pos);
	    $prog->Stroke($aImg);
	}

	$middle = round($yt+($yb-$yt)/2);
	$this->title->Stroke($aImg,$aImg->left_margin+$this->iLabelLeftMargin,$middle);
	$this->leftMark->Stroke($aImg,$xt,$middle);
	$this->rightMark->Stroke($aImg,$xb,$middle);
	$margin = $this->iCaptionMargin;
	if( $this->rightMark->show )
	    $margin += $this->rightMark->GetWidth();

	$this->caption->Stroke($aImg,$xb+$margin,$middle);
    }
}

//===================================================
// CLASS MileStone
// Responsible for formatting individual milestones
//===================================================
class MileStone extends GanttPlotObject {
    var $mark;

//---------------
// CONSTRUCTOR
    function MileStone($aVPos,$aLabel,$aDate,$aCaption="") {
	GanttPlotObject::GanttPlotObject();
	$this->caption->Set($aCaption);
	$this->caption->Align("left","center");
	$this->caption->SetFont(FF_FONT1,FS_BOLD);
	$this->title->Set($aLabel);
	$this->title->SetColor("darkred");
	$this->mark = new PlotMark();
	$this->mark->SetWidth(10);
	$this->mark->SetType(MARK_DIAMOND);
	$this->mark->SetColor("darkred");
	$this->mark->SetFillColor("darkred");
	$this->iVPos = $aVPos;
	$this->iStart = $aDate;
    }

//---------------
// PUBLIC METHODS

    function GetAbsHeight($aImg) {
	return max($this->title->GetHeight($aImg),$this->mark->GetWidth());
    }

    function Stroke($aImg,$aScale) {
	// Put the mark in the middle at the middle of the day
	$x = $aScale->TranslateDate($aScale->NormalizeDate($this->iStart)+SECPERDAY/2);
	$y = $aScale->TranslateVertPos($this->iVPos)-($aScale->GetVertSpacing()/2);

	$this->mark->Stroke($aImg,$x,$y);
	$this->caption->Stroke($aImg,$x+$this->mark->width/2+$this->iCaptionMargin,$y);
	$x=$aImg->left_margin+$this->iLabelLeftMargin;
	$this->title->Stroke($aImg,$x,$y);
    }
}


//===================================================
// CLASS GanttVLine
// Responsible for formatting individual milestones
//===================================================

class GanttVLine extends GanttPlotObject {

    var $iLine,$title_margin=3;
    var $iDayOffset=0;	// Defult to left edge of day

//---------------
// CONSTRUCTOR
    function GanttVLine($aDate,$aTitle="",$aColor="black",$aWeight=3,$aStyle="dashed") {
	GanttPlotObject::GanttPlotObject();
	$this->iLine = new LineProperty();
	$this->iLine->SetColor($aColor);
	$this->iLine->SetWeight($aWeight);
	$this->iLine->SetStyle($aStyle);
	$this->iStart = $aDate;
	$this->title->Set($aTitle);
    }

//---------------
// PUBLIC METHODS

    function SetDayOffset($aOff=0.5) {
	if( $aOff < 0.0 || $aOff > 1.0 )
	    JpGraphError::Raise("<b>JpGraph Error:</b> Offset for vertical line must be in range [0,1]");
	$this->iDayOffset = $aOff;
    }

    function SetTitleMargin($aMarg) {
	$this->title_margin = $aMarg;
    }

    function Stroke($aImg,$aScale) {
	$x = $aScale->TranslateDate($aScale->NormalizeDate($this->iStart)+$this->iDayOffset*SECPERDAY);
	$y1 = $aScale->iVertHeaderSize+$aImg->top_margin;
	$y2 = $aImg->height - $aImg->bottom_margin;
	$this->iLine->Stroke($aImg,$x,$y1,$x,$y2);
	$this->title->Align("center","top");
	$this->title->Stroke($aImg,$x,$y2+$this->title_margin);
    }
}

// <EOF>
