<?php
/*=======================================================================
// File: 	JPGRAPH_DIR.PHP
// Description:	Specification of file directories for JpGraph
// Created: 	22/11/2001
// Author:	Johan Persson (johanp@aditus.nu)
// Ver:		$Id: s.jpgraph_dir.php 1.1 02/11/18 06:18:19-00:00 jpm $
//
// License:	This code is released under GPL 2.0
// Copyright (C) 2001 Johan Persson
//========================================================================
*/

//------------------------------------------------------------------
// Manifest Constants that control varius aspect of JpGraph
//------------------------------------------------------------------
// The full absolute name of directory to be used as a cache. This directory MUST
// be readable and writable for PHP. Must end with '/'
define('CACHE_DIR', '/tmp/jpgraph_cache/');

// The URL relative name where the cache can be found, i.e
// under what HTTP directory can the cache be found. Normally
// you would probably assign an alias in apache configuration
// for the cache directory.
define('APACHE_CACHE_DIR', '/jpgraph_cache/');

// Directory for TTF fonts. Must end with '/'
define('TTF_DIR', APP_JPGRAPH_PATH . '/ttf/');
