<?php
	// UI for GifCreator
	class GifCreatorUI {
		private $_data = [], $_errors = [];
		private $_gc = null;
		
		public function __construct($init = []) {
			$this->_data = isset($init['data']) ? $init['data'] : []; // request params
			$this->_gc = isset($init['gc']) ? $init['gc'] : null; // GifCreator instance
		}
		
		public function create() {
			// data expected: image_path[], duration[]
			$images = isset($this->_data['image_path']) ? $this->_data['image_path'] : false;
			$durations = isset($this->_data['duration']) ? $this->_data['duration'] : false;
			
			if(!$images || !count($images)) return $this->_errors('No images specified!');
			foreach($images as $i => $img) {
				if(!$img || !is_readable($img)) unset($images[$i], $durations[$i]);
			}
			if(!count($images)) return $this->_errors('No valid images specified!');
			
			$this->_gc->create($images, $durations, 0); // 0 = infinite loop
			$gif_bin = $this->_gc->getGif();
			$gif_html = '<img src="data:image/gif;base64,' . base64_encode($gif_bin) . '">';
			
			return $this->_header() . $gif_html . $this->_footer();
		}
		
		public function form() {
			ob_start();
			?>
			<h1>Specify pathes to your Images and duration for each</h1>
			<form action="index.php" method="post">
				<div id="images"></div>
				<input type="button" id="addImage" value="Add Image">
				<input type="submit" name="create" value="Create Animated Gif">

				<script>
					var images = []; // array of { image-path-x, duration-x }
					var saveImages = function() {
						var i = document.getElementsByClassName('image').length;
						if(!i) return;
						images = []; // reset images to repopulate
						for(var x = 0; x < i; x++){
							var ip = 'image-path-' + x;
							var d = 'duration-' + x;
							var im = {};
							im[ip] = document.getElementById(ip).value;
							im[d] = document.getElementById(d).value;
							images.push(im);
						}
					}
					var loadImages = function() {
						var i = document.getElementsByClassName('image').length;
						for(var x = 0; x < i; x++){
							if(images[x] == undefined) continue;
							document.getElementById('image-path-' + x).value = images[x]['image-path-' + x];
							document.getElementById('duration-' + x).value = images[x]['duration-' + x];
						}
					}
					var addImage = function() {
						var i = document.getElementsByClassName('image').length;
						saveImages();
						document.getElementById('images').innerHTML += '' +
							'<div class="image">' +
								'<div class="control" style="width: 68%;">' +
									'<label for="image-path-' + i + '">' +
										'File path to image #' + (i + 1) +
									'</label>' +
									'<input name="image_path[]" id="image-path-' + i + '" class="image-path">' +
								'</div>' +
								
								'<div class="control" style="width: 28%;">' +
									'<label for="duration-' + i + '">' +
										'Duration in msec' +
									'</label>' +
									'<input name="duration[]" id="duration-' + i + '" class="duration" value="100">' +
								'</div>' +
								
								'<div class="clear-fix"></div>' +
							'</div>';
						loadImages();
						document.getElementById('image-path-' + i).focus();
					}
					
					addImage();
					document.getElementById('addImage').onclick = addImage;
				</script>
			</form>
			<?php
			$form = ob_get_clean();
			
			return $this->_header() . $form . $this->_footer();
		}
		
		private function _add_error($error) {
			$this->_errors[] = $error;
		}
		
		private function _errors($error = null) {
			if($error !== null) $this->_add_error($error);
			$errors =	'<div class="errors">'.
							'The following error(s) occured: <ul><li>' . 
								implode('</li><li> ', $this->_errors) . 
							'</li></ul>' .
							'<input type="button" onclick="history.go(-1);" value="Go Back!">' .
						'</div>' .
						'<pre>' . var_export($this->_data['image_path']) . '</pre>';
			return $this->_header() . $errors . $this->_footer();
		}
		
		private function _header($title = 'Gif Creator UI') {
			ob_start();
			?><!DOCTYPE html>
			<html>
				<head>
					<meta charset="utf-8" />
					<title><?php echo $title; ?></title>
					<style>
						.clear-fix{ clear: both; }
						.image{
							border: solid 1px silver;
							padding: 1em;
							margin: 1em;
							max-width: 70em;
							border-radius: 3px;
						}
						.image .control{ float: left; }
						.image .control:not(:first-child){ margin-left: 1em; }
						.image label{ display: block; font-weight: bold; font-family: arial;}
						.image-path, .duration{ width: 100%; }
						.image input:focus{ border: solid 2px; background-color: lightyellow; }
						input[type=button],input[type=submit]{
							height: 4rem;
							margin: .5rem;
							vertical-align: top;
						}
						input[type=submit]{
							font-size: 2rem;
							width: 30rem; 
						}
						.errors{
							font-family: arial;
							padding: 1em;
							margin: 1em;
							color: #F70015;
							background-color: #FFD9DC;
							font-weight: bold;
							border: solid 1px #F70015;
							border-radius: 3px;
						}
					</style>			
				</head>
				<body>
			<?php
			return ob_get_clean();
		}
		
		private function _footer() {
			ob_start();
			?>
				</body>
			</html>
			<?php
			return ob_get_clean();
		}
	}

