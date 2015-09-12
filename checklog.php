<!DOCTYPE html>
<html>
	<body>
		<div style="background-color: #ffffff;color: #000000">
			<?php echo file_get_contents('call.log') ?>
		</div>
		<div style="background-color: #1b6d85;color: #ffffff">
			<?php echo file_get_contents('curlToken.log') ?>
		</div>
		<div style="background-color: green;color: #ffffff">
			<?php echo file_get_contents('curlJSApi.log') ?>
		</div>
	</body>
</html>