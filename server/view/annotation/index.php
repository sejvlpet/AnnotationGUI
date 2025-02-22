<?php
	session_start();
	
	// authentication code -----------------------------------------------
	// $valid_passwords = array ("user_name" => "user_pass");
	// $valid_users = array_keys($valid_passwords);

	// $user = $_SERVER['PHP_AUTH_USER'];
	// $pass = $_SERVER['PHP_AUTH_PW'];

	// $validated = (in_array($user, $valid_users)) && ($pass == $valid_passwords[$user]);

	// if (!$validated) {
	//   header('WWW-Authenticate: Basic realm="My Realm"');
	//   header('HTTP/1.0 401 Unauthorized');
	//   die ("Not authorized");
	// }

	// // If arrives here, is a valid user.
	// echo "<p>Vítejte (Welcome) $user.</p>";
	// authentication code end ----------------------------------------------




	// Annotation GUI code, not related to authentication
	if (!isset($_GET['name'])) {
		header('Location:../../');
		exit;
	}

	$project_dir = '/var/www/html/projects/';
	$project_names = scandir($project_dir);
	$project_name = $_GET['name'];

	$isOK = false;
	foreach ($project_names as $name) {
		if (strcmp($project_name, $name) == 0) {
			$isOK = true;
			break;
		}
	}

	if (!$isOK) {
		header('Location:../../');
		exit;		
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="content-type" charset="utf-8">

	<title>Super Sophisticated Annotation GUI</title>

	<link rel="stylesheet" type="text/css" href="../../css/bootstrap.min.css">
	<script type="text/javascript" src="../../js/jquery-3.5.1.min.js"></script>
	<script type="text/javascript" src="../../js/bootstrap.bundle.min.js"></script>
	<link rel="stylesheet" type="text/css" href="../../css/style.css">
	<link rel="stylesheet" type="text/css" href="../../css/main.css">

	<script type="text/javascript" src="./draw.js"></script>
	<script type="text/javascript" src="./annotation.js"></script>
	<script type="text/javascript" src="./common_control.js"></script>
	<script type="text/javascript" src="tracklet.js"></script>

	<script type="text/javascript">
		var project_name = '<?php echo $project_name; ?>';
		var project_url = '../../projects/' + project_name + '/';
	</script>
</head>
<body oncontextmenu="return false;">

<nav class="navbar navbar-dark bg-dark" style="height: 5%; padding-top: 0; padding-bottom: 0;">
	<a href="#" class="navbar-brand">
		Super Sophisticated Annotation GUI
	</a>

	<span class="navbar-text ml-auto" style="margin-right: 1%;">
		<small>Anotace se ukládají automaticky (Annotation results are automatically saved)</small>
	</span>
	<button type="button" class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#shortcut-dialog">?</button>
	<a type="button" class="btn btn-secondary btn-sm" href="../../" style="margin-left: 1%;">Zpět do menu (Back to Menu)</a>
</nav>

<div class="container-fluid" style="width: 100%; height: 95%;">
	<div class="row" style="height: 100%; padding: 1%;">
		<!-- Left Pane -->
		<div style="width:20%; padding-right: 1%; height: 100%;">
			<figure class="figure d-none d-sm-block">
				<div class="insideWrapper" style="height: 25%"; >
					<img src="" class="overedImage" id="thumb-image" style="max-width: 100%; height: auto;">
					<canvas class="coveringCanvas" id="canvas-thumb"></canvas>
				</div>
			</figure>

			<div style="padding-top: 5%;" class="video-control" >
				<button hidden class="btn btn-secondary btn-block require-selection" id="delete-at-current-frame" disabled onclick="delete_at_current_frame();" style="padding: 0px;">
					<div class="button-text">Delete track at current frame</div></button>
				<button class="btn btn-secondary btn-block require-selection" id="delete-in-subsequent-frames" disabled onclick="delete_in_subsequent_frames();" style="padding: 0px;">
					<div class="button-text">Ukončit track - již se neobjeví v dalších snímcích <br> (End tracklet - it will not be in any consecutive frames)</div></button>
				<button class="btn btn-secondary btn-block require-selection" id="delete-whole" disabled data-toggle="modal" data-target="#delete-dialog" style="padding: 0px;">
					<div class="button-text">Odstranit celý track - i z předchozích snímků <br> (Remove entire tracklet - will be removed even in previous frames)</div></button>
				<button class="btn btn-secondary btn-block require-selection" onclick="begin_link_tracklet();" id="link-tracklet" disabled style="padding: 0px;" hidden>
					<div class="button-text">Link tracklets</div></button>
				<button class="btn btn-primary btn-block" onclick="end_link_tracklet();" hidden id="end-link-tracklet" style="padding: 0px;">
					<div class="button-text" hidden>End Link tracklets</div></button>
				<button class="btn btn-secondary btn-block require-selection" id="cut-tracklet" disabled onclick="cut_tracklet();" style="padding: 0px;">
					<div class="button-text" hidden>Cut tracklet at current frame</div></button>
				<button class="btn btn-secondary btn-block" onclick="predict_next_frame(frame_index);" id="predict-next-frame" hidden>
					Predikovat do dalšího snímku <br> (Predict position in the next frame)</button>
				<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" id="auto-predict" onchange="auto_predict();" checked>
						<label class="custom-control-label" for="auto-predict" title="Automatically run next frame prediction when image is changed.">Automaticky predikovat (Auto Predict)</label>
					</div>

					<div class="custom-control custom-radio custom-control-inline">
						<input type="radio" id="csrt" value="csrt" name="prediction_algorithm" class="custom-control-input" checked>
						<label class="custom-control-label" for="csrt">CSRT</label>
					</div>

					<!-- <div class="custom-control custom-radio custom-control-inline" hidden>
						<input type="radio" id="homography" value="homography" name="prediction_algorithm" class="custom-control-input" >
						<label class="custom-control-label" for="homography">Homography</label>
					</div> -->

			</div>

			<div style="padding-top: 5%;" class="image-control" hidden>
				<button class="btn btn-secondary btn-block require-selection" id="delete-at-current-frame" disabled onclick="delete_at_current_frame();" style="padding: 0px;">
					<div class="button-text">Delete Bounding Box</div></button>
			</div>

			<div id="test"></div>
		</div>

		<!-- Right Pane -->
		<div style="width:80%; height: 100%;">
			<!-- Image Region -->
			<div class="insideWrapper" style="width: 100%; height: 95%;">
				<div style="position: relative; overflow: hidden; width: 100%; height: 100%;">
					<div id="attr-tooltip" class="attr-tooltips" hidden>attr</div>
				</div>
				<canvas style="width: 100%; height: 100%; position:absolute; top:0px; left:0px; background-color: #000000;" id="canvas-main"></canvas>
				<canvas style="width: 100%; height: 100%; position:absolute; top:0px; left:0px" id="canvas-draw"></canvas>

			</div>
	
			<!-- Seek bar -->
			<form class="range-field form-inline" style="width: 100%; height: 5%;">
				<div class="form-group" style="width: 15%;">
					<input type="number" min="1" max="100" value="1" class="form-control" id="current-frame-index" style="width: 50%;" oninput="updateFrameIndex(event.target.value);">
					<label style="width: 50%;" for="current-frame-index" id="max-frame-index">/100</label>
				</div>
				<input type="range" min="1" max="100" value="1" class="slider" id="seekbar"style="width: 85%;" 
					oninput="updateFrameIndex(event.target.value)" onkeydown="event.preventDefault()"/>
			</form>
		</div>
	</div>
</div>

<!-- Dialogue -->
<div class="modal" id="label-dialog" tabindex="-1" role="dialog" aria-labelledby="label-dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="label-dialog">Kategorie a atributy <br> (Category and Attribution)</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="selecting_new_category = false;">
				<span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body row">
				<div class="col-6">
					<h6>Kategorire (Category)</h6>
					<select class="custom-select" size="10" id="category-selection">
					</select>
					<hr>
					<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#add-label-dialog" hidden>Přidat nový (Add New)</button>
				</div>
				<div class="col-6">
					<h6>Atributy (Attribution)</h6>
					<div id="attribution-selection">
						<template id="attribution-template">
							<div class="custom-control custom-switch">
								<input type="checkbox" class="custom-control-input attr-checkbox" id="">
								<label class="custom-control-label attr-label" for=""></label>
							</div>
						</template>
					</div>

					<br>
					<div class="text-left video-control">
						<h6>Aplikovat na (Apply to)</h6>
						<div class="form-check">
							<input type="radio" class="form-check-input" name="attr-apply"  id="attr-subsequent" checked>
							<label class="form-check-label" for="attr-subsequent">Následující snímky (Subsequent frames)</label>
						</div>

						<div class="form-check">
							<input type="radio" class="form-check-input" name="attr-apply" id="attr-this">
							<label class="form-check-label" for="attr-this">Tento snímek (This frame)</label>
						</div>

						<div class="form-check">
							<input type="radio" class="form-check-input" name="attr-apply"  id="attr-whole">
							<label class="form-check-label" for="attr-whole">Celý track (Whole tracklet)</label>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="selecting_new_category = false;">Cancel</button>
				<button type="button" class="btn btn-primary" onclick="if(selecting_new_category){addTracklet(is_video)}else{assignLabel()}" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="add-label-dialog" tabindex="-1" role="dialog" aria-labelledby="add-label-dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="add-label-dialog">Add New Category</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<input type="text" class="form-control" placeholder="Supercategory Name" id="supercategory-name"><br>
					<input type="text" class="form-control" placeholder="New Category Name" id="new-category-name" oninput="checkNewCategoryName();">
					<small style="color: red;" id="duplicate_error" hidden>Category name already exists.</small>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="add-new-category-button" onclick="addNewCategory();" data-dismiss="modal" disabled>OK</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="delete-dialog" tabindex="-1" role="dialog" aria-labelledby="delete-dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="delete-dialog">Odstranit celý track (Delete Whole Tracklet)</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				Jste si jisti, že chce odstranit celý track? <br> (Are you sure want to delete the whole tracklet?)
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-warning" onclick="delete_whole();" data-dismiss="modal">OK</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="shortcut-dialog" tabindex="-1" role="dialog" aria-labelledby="shortcut-dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="shortcut-dialog">Shortcuts</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<ul style="text-align:left">
					<li><b>Arrow or A/D</b>: Move frames</li>
					<li><b>Delete</b>: Delete track at current frame</li>
					<li><b>Ctrl+Z</b>: Undo</li>
				</ul>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	const canvas_main = $('#canvas-main')[0];
	const canvas_draw = $('#canvas-draw')[0];
	const canvas_thumb = $('#canvas-thumb')[0];

	var frame_index = 1;
	let predictionRunning = false;


	const anti_alias = 1;
	const ctx_main = canvas_main.getContext('2d');
	const ctx_draw = canvas_draw.getContext('2d');
	const ctx_thumb = canvas_thumb.getContext('2d');

	canvas_main.width = window.innerWidth * anti_alias;
	canvas_main.height = window.innerHeight * anti_alias;
	canvas_draw.width = window.innerWidth * anti_alias;
	canvas_draw.height = window.innerHeight * anti_alias;


	$(window).resize(function () { 
		canvas_main.width = window.innerWidth * anti_alias;
		canvas_main.height = window.innerHeight * anti_alias;
		canvas_draw.width = window.innerWidth * anti_alias;
		canvas_draw.height = window.innerHeight * anti_alias;

		updateScreen(); 
	});

	var img_x = 0;
	var img_y = 0;
	var img_scale = 1;

	var moving_image = false;
	var making_box = false;
	var selecting_new_category = false;
	var moving_box = false;
	var resizing_box = false;
	var forceImageMove = false;
	var tracklet_linking = false;

	var mx = null, my = null;
	var sx = null, sy = null;
	var x1 = null, y1 = null, x2 = null, y2 = null;

	var is_video = true;

	const current_image = new Image();
	current_image.onload = () => { updateImageCanvas(); updateDrawCanvas(); };

	function checkNewCategoryName() {
		for (var i = 0; i < annotation.categories.length; i++) {
			if (annotation.categories[i].name === $('#new-category-name').val()) {
				$('#duplicate_error').attr('hidden', false);
				$('#add-new-category-button').attr('disabled', true);
				return;
			}
		}

		$('#duplicate_error').attr('hidden', true);

		if ($('#new-category-name').val() !== '') {
			$('#add-new-category-button').attr('disabled', false);			
		}		
	}

	function addNewCategory() {
		annotation.categories.push(
			{supercategory: $('#supercategory-name').val(),
			id: next_category_id,
			name: $('#new-category-name').val()},
		);

		$('#category-selection').append(
			$('<option>')
				.val(next_category_id)
				.text($('#new-category-name').val())
				.prop('selected', false));

		next_category_id++;

		$('#new-category-name').val('');
	}

	//
	// Button function
	//
	function setSelectedBox(new_box) {
		selected_box = new_box;
		$('.require-selection').attr('disabled', selected_box === null);
	}

	//
	// Coordinate computation
	//
	function mouse_in_rect(x, y, w, h) {
		var [mx_, my_] = canvasToImage(mx, my);
		return x <= mx_ && mx_ <= x + w && y <= my_ && my_ <= y + h;
	}

	function real_scale() {
		return real_scale_(canvas_main, current_image, img_scale);
	}

	function canvasToImage(x, y) {
		return canvasToImage_(x, y, img_x, img_y, canvas_main, current_image, img_scale);
	}

	function imageToCanvas(x, y) {
		return imageToCanvas_(x, y, img_x, img_y, canvas_main, current_image, img_scale);
	}

	//
	// Drawing
	//
	async function updateFrameIndex(new_index) {
		if (($("#label-dialog").data('bs.modal') || {})._isShown || making_box || selecting_new_category) {
			return;
		}

		// lock keyboard during the waiting for prediction
		$(document).on('keydown.predict', function(e) {
            e.preventDefault();
        });

		if ($('#auto-predict').prop('checked') && frame_index + 1 === new_index) {
	        predictionRunning = true; // set flag to true before prediction
	        $(document).on('keydown.predict', function(e) {
	            if (predictionRunning) {
	                e.preventDefault();
	                return;
	            }
	        });

	        try {
	            await predict_next_frame(frame_index);
	        } catch (error) {
	            console.log(error);
	        } finally {
	            predictionRunning = false; // set flag to false after prediction
	            $(document).off('keydown.predict');
	        }
    	}

		frame_index = Math.max(Math.min(new_index, annotation.images.length), 1);

		$('#current-frame-index').val(frame_index);
		$('#seekbar').val(frame_index);

		updateScreen();

		hovered_box = null;
		hover_list = [];
		setSelectedBox(null);
	}

	function updateScreen() {
		current_image.src = project_url + 'images/' + annotation.images[frame_index - 1].file_name;

		$('#thumb-image').attr('src', current_image.src);
	}

	function updateImageCanvas() {
		img_x = Math.min(img_scale, img_x);
		img_y = Math.min(img_scale, img_y);
		img_x = Math.max(-img_scale, img_x);
		img_y = Math.max(-img_scale, img_y);

		drawMainImage(canvas_main, ctx_main, current_image, img_x, img_y, real_scale());
		drawSubImage(canvas_thumb, canvas_main, ctx_thumb, current_image, img_x, img_y, real_scale());
	}

	function updateDrawCanvas() {
		ctx_draw.clearRect(0, 0, canvas_draw.width, canvas_draw.height);
		drawGrid(canvas_draw, ctx_draw, mx, my);
		drawTracklets(annotation, selected_box, hovered_box, hovered_cp, no_link_cands,
						 canvas_draw, ctx_draw, current_image, img_x, img_y, img_scale);
		if (making_box) {
			drawMakingBox(canvas_draw, ctx_draw, mx, my, img_x, img_y, current_image, img_scale)
		}
	}

	//
	// Operation
	//
	function initializeMousePosition(event) {
		var rect = canvas_main.getBoundingClientRect();
		var canvas_width = rect.right - rect.left;
		var canvas_height = rect.bottom - rect.top;

		var mx_last = mx;
		var my_last = my;
		mx = (event.x - rect.left) / canvas_width;
		my = (event.y - rect.top) / canvas_height;
	}

	function beginMoveImage() {
		moving_image = true;
		making_box = false;
		$('#canvas-draw').css('cursor', 'grab');
	}

	function endMoveImage() {
		moving_image = false;
		$('#canvas-draw').css('cursor', 'auto');
	}

	function beginMakeTracklet() {
		making_box = true;
		sx = mx;
		sy = my;
	}

	function endMakeTracklet() {
		making_box = false;

		[x1, y1] = canvasToImage(sx, sy);
		[x2, y2] = canvasToImage(mx, my);

		x1 = Math.max(Math.min(x1, current_image.width), 0);
		x2 = Math.max(Math.min(x2, current_image.width), 0);
		y1 = Math.max(Math.min(y1, current_image.height), 0);
		y2 = Math.max(Math.min(y2, current_image.height), 0);

		if (Math.abs(x1 - x2) < 1 || Math.abs(y1 - y2) < 1) {
			return;
		}

		if (x1 > x2) {
			[x1, x2] = [x2, x1];
		}
		if (y1 > y2) {
			[y1, y2] = [y2, y1];
		}

		selecting_new_category = true;
		$('#label-dialog').modal();
	}

	function beginMoveBox() {
		moving_box = true;
	}

	function endMoveBox() {
		moving_box = false;
		propagateTracklet(selected_box);
		updateAnnotation();
	}

	function beginResizeBox() {
		resizing_box = true;
	}

	function endResizeBox() {
		resizing_box = false;
		propagateTracklet(selected_box);
		updateAnnotation();
	}

	function moveImage(mx, my, mx_last, my_last) {
		img_x += (mx - mx_last) * 2;
		img_y -= (my - my_last) * 2;
		updateImageCanvas();
	}

	function checkHover() {
		if (!annotation) {
			return;
		}

		// Check if mouse hovers on some box
		// When mouse is hovering on multiple boxes, latest box is choosed
		for (var i = 0; i < annotation.annotations.length; i++) {
			var annot = annotation.annotations[i];
			if (annot.image_id !== annotation.images[frame_index - 1].id) {
				continue;
			}
			if (mouse_in_rect(annot.bbox[0], annot.bbox[1], annot.bbox[2], annot.bbox[3])) {
				if (!hover_list.includes(annot.id)) {
					hover_list.push(annot.id);
				}
			} else {
				hover_list = hover_list.filter(id => id !== annot.id);
			}
		}

		if (hover_list.length === 0) {
			hovered_box = null;
			$('#canvas-draw').css('cursor', 'auto');
		} else {
			// Latest box
			hovered_box = hover_list.slice(-1)[0];
			$('#canvas-draw').css('cursor', 'move');
		}

		// If mouse is closer to control points than specifc threshold, hovered_box will be overriden.
		var min_d = hovered_box != null ? 0.01 : 0.03;
		var closest_box = null;
		var closest_cp = null;
		for (var i = 0; i < annotation.annotations.length; i++) {
			var annot = annotation.annotations[i];
			if (annot.image_id !== annotation.images[frame_index - 1].id) {
				continue;
			}
			
			var [x1, y1] = imageToCanvas(annot.bbox[0], annot.bbox[1]);
			var [x2, y2] = imageToCanvas(annot.bbox[2] + annot.bbox[0], annot.bbox[3] + annot.bbox[1]);
			var cpList = [[x1, y1], [x2, y1], [x1, y2], [x2, y2], [(x1+x2)/2, y1], [x1, (y1+y2)/2], [(x1+x2)/2, y2], [x2,(y1+y2)/2]];

			for (var j = 0; j < cpList.length; j++) {
				var d = Math.sqrt((cpList[j][0] - mx) ** 2 + (cpList[j][1] - my) ** 2);
				if (d < min_d) {
					min_d = d;
					closest_box = annot.id;
					closest_cp = j;
				}
			}
		}

		if (closest_box != null && closest_cp != null) {
			hovered_box = closest_box;
			hovered_cp = closest_cp;
			var resize_cursors = ['nw-resize', 'ne-resize', 'sw-resize', 'se-resize', 'n-resize', 'w-resize', 's-resize', 'e-resize'];
			$('#canvas-draw').css('cursor', resize_cursors[hovered_cp]);
		} else {
			hovered_cp = null;
		}
	}

	//
	// Events
	//
	function onMouseDown(event) {
		if (mx === null || my === null) {
			initializeMousePosition(event);
		}
		if (event.button === 0) {
			if (tracklet_linking) {
				if (hovered_box !== null) {
					linkBox(hovered_box);
				} else {
					beginMoveImage();
				}
			} else {
				setSelectedBox(hovered_box);
				if (hovered_cp !== null && !forceImageMove) {
					beginResizeBox();
				} else if (selected_box !== null && !forceImageMove) {
					beginMoveBox();
				} else {
					beginMoveImage();
				}
			}
			updateDrawCanvas();
		} else if (event.button === 2) {
			beginMakeTracklet();
		}
		event.preventDefault();
	}

	function onMouseUp(event) {
		if (event.button === 0) {
			if (moving_image) {
				endMoveImage();
			}
			if (resizing_box) {
				endResizeBox();
			}
			if (moving_box) {
				endMoveBox();
			}
		} else if (event.button === 2) {
			if (making_box) {
				endMakeTracklet();
			}
		}
		event.preventDefault();
	}

	function onMouseMove(event) {
		var rect = canvas_main.getBoundingClientRect();
		var canvas_width = rect.right - rect.left;
		var canvas_height = rect.bottom - rect.top;

		var mx_last = mx;
		var my_last = my;
		mx = (event.x - rect.left) / canvas_width;
		my = (event.y - rect.top) / canvas_height;

		if (moving_image) {
			moveImage(mx, my, mx_last, my_last);
		} else if (moving_box) {
			moveBox(mx, my, mx_last, my_last);
		} else if (resizing_box) {
			resizeBox(mx, my, mx_last, my_last);
		} else {
			checkHover();
		}

		$('#attr-tooltip').css({top: my * canvas_height + 5, left: mx * canvas_width + 5});

		updateDrawCanvas();
		event.preventDefault();
	}

	function onMouseWheel(event) {
		var rect = canvas_main.getBoundingClientRect();
		var x = (event.x - rect.left) / (rect.right - rect.left);
		var y = (event.y - rect.bottom) / (rect.top - rect.bottom);
		x = x * 2 - 1
		y = y * 2 - 1

		// x, y ~ (-1, 1)

		var delta = (typeof event.wheelDeltaY !== 'undefined') ? event.wheelDeltaY : event.deltaY;

		var scale_change = 0.8;

		if (delta > 0) {
			scale_change = 1 / scale_change;
		}

		img_scale *= scale_change;
		if (img_scale < 1) {
			scale_change /= img_scale;
			img_scale = 1;
			img_x = 0;
			img_y = 0;
		} else if (img_scale > 20) {
			scale_change *= 20 / img_scale;
			img_scale = 20;
		}
		img_x -= x;
		img_y -= y;
		img_x *= scale_change;
		img_y *= scale_change;
		img_x += x;
		img_y += y;

		updateImageCanvas();
		updateDrawCanvas();

		event.preventDefault();
	}

	function onDblClick(event) {
		if (tracklet_linking) {
			return;
		}
		if (selected_box !== null) {
			var selected_category = null;
			var selected_attr = null;
			for (var i = 0; i < annotation.annotations.length; i++) {
				if (annotation.annotations[i].id == selected_box) {
					selected_category = annotation.annotations[i].category_id;
					selected_attr = annotation.annotations[i].attribution;
				}
			}

			$('#category-selection').find('option').each(function(i, option) {
				$(option).prop('selected', $(option).val() == selected_category);
			});

			$('#attribution-selection').find('.attr-checkbox').each(function(i, attr) {
				$(attr).prop('checked', selected_attr.includes(Number(attr.id.replace('attr-', ''))));
			})
			$('#label-dialog').modal();
		}
		event.preventDefault();
	}

	$(document).ready(function() {
		if (!loadAnnotation()) {
			// Still preparing images
			location.href = '../../';
		}

		is_video = annotation.info.type === undefined || annotation.info.type === 'video';

		if (annotation.info.type === undefined ||
			annotation.info.type === 'video') {
			$('.video-control').attr('hidden', false);
		} else if (annotation.info.type === 'image') {
			$('.image-control').attr('hidden', false);
		} else {
			alert('Unknown project type: ' + annotation.info.type);
		}

		$('#max-frame-index').text('/' + annotation.images.length);
		$('#seekbar').attr('max', annotation.images.length);

		for (var i = 0; i < annotation.categories.length; i++) {
			$('#category-selection').append(
				$('<option>')
					.val(annotation.categories[i].id)
					.text(annotation.categories[i].name)
					.prop('selected', i==0));
		}

		var template = $('#attribution-template').contents();
		for (var i = 0; i < annotation.attributes.length; i++) {
			var clone = template.clone();
			clone.find('.attr-checkbox').attr('id', 'attr-' + annotation.attributes[i].id);
			clone.find('.attr-label').attr('for', 'attr-' + annotation.attributes[i].id)
				.text(annotation.attributes[i].name);
			$('#attribution-selection').append(clone);
		}

		updateFrameIndex(1);

		document.addEventListener('keydown', (event) => {
			if (event.key == 'ArrowLeft' || event.key == 'a' || event.key == 'A') {
				updateFrameIndex(frame_index - 1);
			} else if (event.key == 'ArrowRight' || event.key == 'd' || event.key == 'D') {
				updateFrameIndex(frame_index + 1);			
			} else if (event.key == 'Delete') {
				delete_at_current_frame();
			} else if (event.key == 'z' && event.ctrlKey) {
				undoAnnotation();
			}

			if (event.shiftKey) {
				forceImageMove = true;
			}
		});

		document.addEventListener('keyup', (event) => {
			if (!event.shiftKey) {
				forceImageMove = false;
			}
		});

		canvas_draw.addEventListener('mousewheel', onMouseWheel, false);
		canvas_draw.addEventListener('wheel', onMouseWheel, false);
		canvas_draw.addEventListener('mousedown', onMouseDown, false);
		canvas_draw.addEventListener('dblclick', onDblClick, false);
		document.addEventListener('mouseup', onMouseUp, false);
		document.addEventListener('mousemove', onMouseMove, false);

		setInterval(function() {
			if (($("#label-dialog").data('bs.modal') || {})._isShown || making_box || selecting_new_category || hovered_box === null) {
				$('#attr-tooltip').attr('hidden', true);
			} else {
				let hb = findTracklet(hovered_box);
				let attrs = hb.attribution;
				if (hb !== null && attrs.length > 0) {
					let text = '';
					attrs.forEach(function (attr) { 
						annotation.attributes.forEach(function (attr_name) {
							if (attr_name.id === attr) {
								text += attr_name.name + '<br>';
							}
						});
					});
					$('#attr-tooltip').html(text);
					$('#attr-tooltip').attr('hidden', false);
				} else {
					$('#attr-tooltip').attr('hidden', true);
				}
			}
		}, 100);

		// Preload images to cache
		annotation.images.forEach(img =>
			$("<img>").attr("src", project_url + 'images/' + img.file_name)
		);
	});
</script>
</body>
</html>