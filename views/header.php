<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Box Anemometer: Slow Query Log</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <link rel="shortcut icon" href="img/favicon.ico" type="image/x-icon" />

    <!-- Le styles -->
	<link href="css/jquery-ui.css" rel="stylesheet" type="text/css"/>
  	<script src="js/jquery-1.8.3.min.js"></script>
	<script src="js/jquery-ui-1.9.2.custom.min.js"></script>
    <link href="css/bootstrap.css" rel="stylesheet">
    
    <!-- addon timepicker to date -->
    <script src="js/jquery-ui-timepicker-addon.js"></script>
    
    <style>
      /* css for timepicker */
      .ui-timepicker-div .ui-widget-header { margin-bottom: 8px; }
      .ui-timepicker-div dl { text-align: left; }
      .ui-timepicker-div dl dt { height: 25px; margin-bottom: -25px; }
      .ui-timepicker-div dl dd { margin: 0 10px 10px 65px; }
      .ui-timepicker-div td { font-size: 90%; }
      .ui-tpicker-grid-label { background: none; border: none; margin: 0; padding: 0; }
      span.weekend { color: #999999; }
      table.table.table-striped.table-bordered.table-condensed tbody tr.weekend td { color: #999999; }
    </style>

    <style>
	pre.prettyprint { font-size: 90%; !important; }
	.nowrap { white-space: pre; overflow: scroll; }
    </style>
  
    <!-- typahead -->
    <script src="js/bootstrap-typeahead.js"></script>
    
    <!-- google pretty print -->
    <link href="css/prettify.css" rel="stylesheet" type="text/css">
    <script src="js/prettify.js" type="text/javascript"></script>
    <script src="js/lang-sql.js" type="text/javascript"></script>

    <!-- dropdown menus  and other addons -->
    <script src="js/bootstrap-dropdown.js"></script>
    <script src="js/bootstrap-collapse.js"></script>
    <script src="js/bootstrap-tab.js"></script>
    <script src="js/bootstrap-combobox.js"></script>
    <link href="css/bootstrap-combobox.css" media="screen" rel="stylesheet" type="text/css">
    
    
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
      
      .navbar .brand{
      	padding: 0 20px !important; /* prevent brand from expanding navbar height */
      }
      
      .navbar .brand img{ /* center logo vertically in navbar */
      	position: relative;
      	top: 6px;
      }
      
      #quicksearch{
      	margin-bottom: 0; /* prevent quicksearch form from expanding navbar height */
      }
    </style>
    <link href="css/bootstrap-responsive.css" rel="stylesheet">

  <body>
  <div class="container">
