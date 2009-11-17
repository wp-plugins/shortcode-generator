<?php

if($_POST || $_GET['action'] == 'edit'){
//if they are posting, send them to the edit page
	require(dirname(__FILE__)."/edit.php");
}else{
	
global $shortcodeGenerator;


if($_GET['action'] == 'remove'){
	$shortcodeGenerator->remove_shortcode(intval($_GET['id']));
	$shortcodeGenerator->fade_msg('Shorcode removed');
}
$codes = $shortcodeGenerator->get_shortcodes();
?>

<script>
function confirm_delete(){
	answer = confirm('Are you sure you want to delete this shortcode?\nIt will not remove any shortcodes from your pages or posts');
	if(answer){
		return true;
	}else{
		return false;
	}
}

function add_shortcode(){
	var typeval = document.getElementById('type_to_add').value;
	if(typeval == 'wysiwyg'){
		document.location = "<?=SCG_ADMIN_PATH ?>add_new_wysiwyg.php";
		return false;
	}else if(typeval == 'html'){
		document.location = "<?=SCG_ADMIN_PATH?>add_new_html.php";
		return false;
	}else{
		return false;
	}
}
</script>
<div class="wrap">
	<h2>Shortcode Generator</h2>
	<p>
		Shortcodes are short, re-usable codes that are replaced with longer values.<br />
		Shortcodes CAN contain shortcodes, this allows you to more dynamically use one shortcode on a page, 
		made up of multiple shortcodes here.
		This should allow you to create many shortcodes here, but only use a few on your pages or posts. 
		Everything should updated once, but take effect everywhere.<br /><br />
		*Be sure not to place a shortcode inside of itself. This will rende the page or post un-viewable.
		
		<br /><br />
		You have <b><?=count($codes);?></b> shortcodes defined.
	</p>
	<form action="<?=SCG_ADMIN_PATH?>index.php" method="post">
			<div class="tablenav" style="width:700px;">
				<p class="tablenav-pages">
					<select id="type_to_add">
						<option value="0">Select a Type of Shortcode to add &nbsp; &nbsp;</option>
						<option value="wysiwyg">WYSIWYG Editor Shortcode </option>
						<option value="html">Plain Text (HTML) Shortcode </option>
					</select>
					<span class="submit"><button onclick="return add_shortcode();">Add</button> &nbsp; &nbsp;</span>
		<?php
		$max_per_page = 30;
		$total_codes = count($codes);
		if($total_codes > $max_per_page){
			$current_page = intval($_GET['pg']);
			$codes = $shortcodeGenerator->get_shortcodes($current_page*$max_per_page,$max_per_page);
			//echo ($current_page*$max_per_page)." ".$max_per_page;
			$pages = ceil($total_codes / $max_per_page);
			//Page $current_page+1 of $pages; ?>
					<?php 
					$dots = false;
					for($i=0;$i<$pages;$i++){
						//if($dots){ continue;}
						if($current_page == $i){echo "<b>" . ($current_page+1) . "</b> &nbsp;"; }else{
					?>
					<a href="<?=add_query_arg('pg',$i)?>" class="page-numbers"><?=$i+1;?></a> &nbsp;
					<?php
						}
					}
					?>

			<?php
		}
		?>
				</p>
			</div>
			<br clear="all" />
		<table class="widefat" style="width:700px;">
			<tr>
				<th>ID</th>
				<th>ShortCode</th>
				<th>Replacement Text</th>
				<th>Type</th>
				<th>Actions</th>
			</tr>
			<?php
			if(is_array($codes) && !empty($codes)){
				$cnt=0;
			
				foreach($codes as $scg){
					?>
			<tr class="<?php if($cnt%2==0){ echo "alternate";} ?>">
				<td><?php echo $scg->ID?></td>
				<td>[scg_<?php echo ($scg->type == 'wysiwyg' ? SCG_PREFIX_WYSIWYG : SCG_PREFIX_HTML) . $scg->shortcode?>]</td>
				<td>
					<?php echo substr(strip_tags(stripslashes($scg->value)),0,50) . (strlen($scg->value) > 50 ? " [...]" : ''); ?>
				</td>
				<td><?php echo $scg->type; ?></td>
				<td>
					[<a href="<?=add_query_arg(array('id'=>$scg->ID,'action'=>'edit','type'=>$scg->type),SCG_ADMIN_PATH . "index.php")?>">edit</a>] 
					[<a href="<?=add_query_arg(array('id'=>$scg->ID,'action'=>'remove'))?>" onclick="return confirm_delete();">remove</a>]
				</td>
			</tr>
					<?php
				$cnt++;
				}
			}else{
				?>
				<td colspan="5" class="empty">There are no generated shortcodes defined</td>
				<?php
			}
			?>
		</table>
	</form>
	<br /><br />
</div>
<?php
}//close the if from the top
?>