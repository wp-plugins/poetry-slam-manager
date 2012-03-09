<div class="slam-results">
	<?php if(is_user_logged_in()): ?>
	<p><a id="make_new_slam" class="button nice charcoal">Create a new slam</a></p>
	<form action="#" id="new_slam" style="display:none">
		<label for="title">Slam Title</label><input type="text" name="title" value="" id="slam_title" />
		<p><a id="new_slam_submit" class="button nice charcoal" type="submit">Go!</a></p>
	</form>
	<?php endif; ?>
	
<?php
$current = true;
$slams = $this->get_all_slams();
if ($slams):
	foreach ($slams as $slam): 
	$last = $slam->id;
	?>
	
	<h2 class = 'edit_slam_title' id='edit_slam_<?php echo $slam->id; ?>_title'><?php echo stripslashes($slam->title); 	
	?></h2>
	<div class="slam-date"><?php echo $this->nice_date($slam->slam_date); ?></div>
	
	<?php
	$results = $this->get_slam_scores($slam->id);
	$rounds = $this->how_many_rounds($results); //need to write this function
	?>

	
	
	<?php if($results): ?>	
	
	<?php for ($i=1; $i <=$rounds ; $i++): ?>
		<?php print "<h4>Round $i</h4>"; ?>
		
		<table class = "slam-<?php echo $slam->id; ?> round-<?php echo $i; ?>">
		  <?php
		  $this->output_table_header();
		  ?>
		
		<?php 
		$entries = 0;
		$round = $this->get_round($results, $i); 
		foreach ($round as $entry): 
			$this->output($entry); 
			$entries++;
		endforeach;
		?>
		</table>
	<?php endfor; $i--;
	?>

	<?php endif;
	if(!$results) 
	{
			print "<p>No results have been posted yet.</p>";
			$i = 1;
	}
	?>
	
	<?php if(is_user_logged_in()): ?>
	
	<p><br /><a href="#" value="<?php echo $slam->id; ?>"class="button blue nice delete_slam">Delete this slam</a></p>
	<h2>Post a Result</h2>
	<?php if($current): 
	$current = false;
	?>
	
	<form id="submit-me">
	<input type="hidden" name="slam_id" value="<?php echo $last; ?>" id="slam_id" />
	<input type="hidden" name="round_no" value="<?php echo $i; ?>" id="round_no" />
	<div class="column">
			<p class="poet">
				<label for="poet">Poet</label><input type="text" name="poet" value="" id="poet" />
			</p>
			<p class="score">
				<label for="score_1">Score 1</label><input type="text" name="score_1" value="" id="score_1" />
			</p>
			<p class="score">
				<label for="score_2">Score 2</label><input type="text" name="score_2" value="" id="score_2" />
			</p>
			</div> <div class="column">
			<p class="score">
				<label for="score_3">Score 3</label><input type="text" name="score_3" value="" id="score_3" />
			</p>
	
			<p class="score">
				<label for="score_4">Score 4</label><input type="text" name="score_4" value="" id="score_4" />
			</p>

			<p class="score">
				<label for="score_5">Score 5</label><input type="text" name="score_5" value="" id="score_5" />
			</p>
			<div class="penalty">
				<p>Time Penalty</p>
				<select name="penalty">
					<?php
					$deduction = 0;
					$minutes = 0;
					$seconds = "00";
					for ($j=0; $j < 20; $j++) { 
						echo "<option value='".$deduction."'>".$deduction." - ".$minutes.":".$seconds." over</option>";
						$seconds += 10; 
						if(($seconds % 60) == 0) {
							$minutes++;
							$seconds = "00";
						}
						$deduction += 0.5;
					}
					?>
				</select>
			</div>
		</div>
		<div class="actions">
			<?php for($j=1;$j<=$rounds+1;$j++): ?>
			<p class="post_result"><a id="post_round_<?php echo $j; ?>_slam_<?php echo $last; ?>" class="button nice charcoal">Post in round <?php echo $j; ?></a></p>
			<?php endfor; ?></div>
	</form>
	
		
			<?php endif; //end if(current) ?>
			
<?php endif; //end if(is_current_user_logged_in) ?>
	
	
<?php endforeach;?>

		
	
<?php endif; ?>
	


</div>

