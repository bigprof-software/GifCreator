<?php
	$curr_dir = dirname(__FILE__);
	include("{$curr_dir}/GifCreator/GifCreator.php");
	include("{$curr_dir}/GifCreatorUI/GifCreatorUI.php");
	
	$action = 'form';	
	if(isset($_REQUEST['create'])) $action = 'create';

	$ui = new GifCreatorUI([
		'data' => $_REQUEST,
		'gc' => new GifCreator\GifCreator()
	]);
	echo $ui->$action();