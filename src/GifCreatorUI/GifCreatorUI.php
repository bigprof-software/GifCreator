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
			$indicator = isset($this->_data['add-image-index-indicator']) ? $this->_data['add-image-index-indicator'] : false;
			
			if(!$images || !count($images)) return $this->_errors('No images specified!');
			// remove non-readable images
			foreach($images as $i => $img) {
				if(!$img || !is_readable($img)) unset($images[$i], $durations[$i]);
			}
			if(!count($images)) return $this->_errors('No valid images specified!');
			
			// apply index-indicator
			foreach($images as $i => $img) {
				if($indicator) $images[$i] = $this->_indicator($img, $i, count($images));
			}
			
			$this->_gc->create($images, $durations, 0); // 0 = infinite loop
			$gif_bin = $this->_gc->getGif();
			$gif_html = '<img src="data:image/gif;base64,' . base64_encode($gif_bin) . '">';
			
			return $this->_header() . $gif_html . $this->_footer();
		}
		
		/**
		 *  @brief Overlays an image index indicator to the given image
		 *  
		 *  @param [in] $img_path path to image
		 *  @param [in] $i current index, 0-based
		 *  @param [in] $total number of images
		 *  @return image resource (as returned from imagecreatefrom*() ), false on error
		 */
		private function _indicator($img_path, $i, $total) {
			if(!$imgf = $this->_imagecreate($img_path)) return false;
			$img = $imgf($img_path);
			
			if(!($total = max(0, intval($total)))) return $img;
			
			$i = max(0, min($total - 1, intval($i)));
			
			list($width, $height) = getimagesize($img_path);
			if(!$width || !$height) return false;
			
			$r = max(5, intval($width / 100)); // radius of indicator
			$dist = 3 * $r; // distance between centers
			$cx = intval($width / 2 - ($dist * ($total - 1)) / 2); // x-coord for 1st indicator
			$cy = $height - 3 * $r; // y-coord for indicators
			
			// try to get indicator colors from existing pallette, or nearest if not found
			// this should minimize new colors added to pallette, thus avoiding degrading
			// image quality as we only have 255 possible colors
			$color = imagecolorexact($img, 0xDD, 0xDD, 0xDD);
			$white = imagecolorexact($img, 0xFF, 0xFF, 0xFF);
			if($color == -1) $color = imagecolorclosest($img, 0xDD, 0xDD, 0xDD);
			if($white == -1) $white = imagecolorclosest($img, 0xFF, 0xFF, 0xFF);
			
			for($n = 0; $n < $total; $n++) {
				imagefilledarc($img, $cx, $cy, $r * 2, $r * 2, 0, 360, $color, IMG_ARC_PIE);
				if($n != $i)
					imagefilledarc($img, $cx, $cy, $r * 1.5 , $r * 1.5 , 0, 360, $white, IMG_ARC_PIE);
				$cx += $dist;
			}
			
			return $img;
		}
		
		/**
		 *  @brief determines the correct imagecreatefrom* function based on file extension
		 *  
		 *  @param [in] $img_path image file path
		 *  @return string containing correct function name, false on error
		 */
		private function _imagecreate($img_path) {
			$m = [];
			if(!preg_match('/\.(png|jpg|jpeg|gif)$/i', $img_path, $m)) return false;
			$ext = strtolower($m[1]);
			
			switch($ext){
				case 'png': return 'imagecreatefrompng';
				case 'gif': return 'imagecreatefromgif';
			}
			
			return 'imagecreatefromjpeg';
		}
		
		public function form() {
			ob_start();
			?>
			<h1>Specify paths to your Images and duration for each</h1>
			<form action="index.php" method="post">
				<div id="images"></div>
				<label for="add-image-index-indicator">
					<input type="checkbox" name="add-image-index-indicator" id="add-image-index-indicator" value="true">
					Add image index indicator at the bottom of all images.
				</label>
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
			
						// copy last image to new one for easier data entry
						if(i) {
							document.getElementById('image-path-' + i).value = images[i - 1]['image-path-' + (i - 1)];
							document.getElementById('duration-' + i).value = images[i - 1]['duration-' + (i - 1)];
						}
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
						body{ padding: 1rem; font-family: arial; }
						.clear-fix{ clear: both; }
						label{ display: block; font-weight: bold; font-family: arial;}
						.image{
							border: solid 1px silver;
							padding: 1rem;
							margin: 0;
							max-width: 70rem;
						}
						.image:last-child{
							border-bottom-left-radius: 3px;
							border-bottom-right-radius: 3px;
							margin-bottom: 1rem;
						}
						.image:first-child{
							border-top-left-radius: 3px;
							border-top-right-radius: 3px; 
							margin-top: 1rem;
						}
						.image:not(:first-child){
							border-top: none;
						}
						.image .control{ float: left; }
						.image .control:not(:first-child){ margin-left: 1em; }
						.image-path, .duration{ width: 100%; }
						.image input:focus{ border: solid 2px; background-color: lightyellow; }
						input[type=button],input[type=submit]{
							height: 4rem;
							margin: .5rem .5rem .5rem 0;
							vertical-align: top;
						}
						input[type=submit]{
							font-size: 2rem;
							width: 30rem; 
						}
						.errors{
							font-family: arial;
							padding: 1rem;
							margin: 1rem;
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

