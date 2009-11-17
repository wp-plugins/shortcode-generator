<?php
global $shortcodeGenerator;
//$action = 'new'; //default
if(!isset($type) && !$_POST){
	$type = $_GET['type'];
}
//$type = 'html';
//
if($_POST){
	$id = $_POST['id'];
	$type = $_POST['type'];
	if(($_POST['action']) == 'new'){
		$code = $shortcodeGenerator->clean_shortcode($_POST['code']);
		if(!$shortcodeGenerator->code_exists($code,$type) && !empty($code)){
			$id = $shortcodeGenerator->add_shortcode($code,$_POST['value'],$type);
			$action = 'update';//been stored, now we are updating
			$shortcodeGenerator->fade_msg('Shortcode [scg_'.($type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML).$code.'] added');
			$shortcode = $shortcodeGenerator->get_shortcode($id); //load up the data
		}else{
			if(empty($code)){//gotta fill it in
				$shortcodeGenerator->fade_msg('Shortcode was empty, please enter a shortcode');
			}else{//it must be already in use
				$shortcodeGenerator->fade_msg('Shortcode [sgc_'.($type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML).$code.'] Already exists, please use another shortcode, or edit the existing one.');
			}
			
			$shortcode = new stdClass;//empty
			$shortcode->shortcode = $code;
			$shortcode->type = $type;
			$shortcode->value = $_POST['value'];
		}
	}else{ //update
		$code = $shortcodeGenerator->clean_shortcode($_POST['code']);
		$shortcodeGenerator->update_shortcode($id,$code,$_POST['value'],$type);
		$shortcodeGenerator->fade_msg('Shortcode [scg_'.($type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML).$code.'] updated');
		$shortcode = $shortcodeGenerator->get_shortcode($id); //load up the data
	}
}else{
	$id = intval($_GET['id']);
	if($id == 0){
		$action == 'new';
		$shortcode = new stdClass;//empty
		$shortcode->type = $type;//fill in type
	}else{
		$action = "update";
		$shortcode = $shortcodeGenerator->get_shortcode($id); //load up the data
	}
}
?>
<script type="text/javascript" src="<?=get_option('siteurl')?>/wp-includes/js/tinymce/tiny_mce.js"></script>
<div class="wrap">
	<h2>Shortcode Generator</h2>
	<p>
	<?php if($id != 0){ ?>
		To use this shortode place 
		<code>[scg_<?php echo ($shortcode->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML) . $shortcode->shortcode?>]</code> into a page, post, or another generated shortcode.
		If your placing another shortcode inside of this one, be sure that the shortcode you've included here does not include this shortcode.  
		<br /><b>If a shortcode contains itself, you will have created an endless loop and any page or post using this shortcode will not load.</b>
	
	<?php }else{
		echo "Enter the shortcode and the content you'd like associated with it. A shortcode is a quick and easy code that will automatically be replaced with text, images, or any other content you wish.";
	} ?>
	</p>
	<form action="<?=SCG_ADMIN_PATH?>index.php" method="post">
		<input type="hidden" name="id" value="<?=$id?>" />
		<input type="hidden" name="action" value="<?=$action?>" />
		<input type="hidden" name="type" value="<?=$type?>" />
		<table class="widefat" style="width:85%">
			<tr>
				<th>ShortCode</th>
				<th>Replacement Text</th>
				<th>Action</th>
			</tr>
			<tr>
				<td>
					scg_<?php echo ($shortcode->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML); ?><input type="text" name="code" value="<?=$shortcode->shortcode?>" /><br /><br />
					<?php if($action != 'new'){?>
					In a page or post:<br /><code>[scg_<?php echo ($shortcode->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML). $shortcode->shortcode; ?>]</code><br />
					In a template:<br /><code>do_shortcode('[scg_<?php echo ($shortcode->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML). $shortcode->shortcode; ?>]');</code>
					<?php } ?>
				</td>
				<td>
					<?php
						if($type == 'wysiwyg'){
							echo shortcodeGenerator::get_WYSIWYG('replace','value',stripslashes($shortcode->value));
						}else{
							?>
							<textarea name="value" id="replace" rows="6" cols="55"><?=stripslashes($shortcode->value)?></textarea>
							<?php
						}
					?>					
				</td>
				<td class="submit">
					<input type="submit" name="submit" value="Save" />
				</td>
			</tr>
		</table>
	</form>
	<br />
	<a href="<?=SCG_ADMIN_PATH?>index.php">&lt;&lt; Back to Generated Shortcodes</a>
</div>