<?php
class slam_manager 
{
	function __construct()
	{
		global $wpdb;
		$this->score_table = $wpdb->prefix . "slam_scores";
		$this->slam_table = $wpdb->prefix . "slams";
		$this->plugin_url = plugins_url('',__FILE__);
	}
	function install()
	{
		global $wpdb;
		$slams_sql = 
				"CREATE TABLE $this->slam_table (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					slam_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					title tinytext NOT NULL,
					UNIQUE KEY id (id)
				);";
		$scores_sql = 	
				"CREATE TABLE $this->score_table (
					id mediumint(9) NOT NULL AUTO_INCREMENT, 
					slam_id mediumint(9) NOT NULL,
					round_no smallint(2) NOT NULL,
					poet tinytext NOT NULL,
					score_1 float(3,1) NOT NULL,
					score_2 float(3,1) NOT NULL,
					score_3 float(3,1) NOT NULL,
					score_4 float(3,1) NOT NULL,
					score_5 float(3,1) NOT NULL,
					over_time tinytext NOT NULL,
					time_over smallint(2),
					penalty float(3,1),
					total_score float(4,1) NOT NULL,
					time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
					UNIQUE KEY id (id)
				);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($slams_sql);
			dbDelta($scores_sql);
	}
	
	/*
		SQL queries
	*/
	
	function get_slam_scores($id)
	{
		global $wpdb;
		$sql = "SELECT * FROM $this->score_table WHERE slam_id = $id ORDER BY time ASC";
		return $wpdb->get_results($sql);
	}
	
	
	function get_all_slams()
	{
		global $wpdb;
		$sql = "SELECT * FROM $this->slam_table ORDER BY slam_date DESC";
		return $wpdb->get_results($sql);
	}
	function include_css() 
	{
	    // Check if shortcode exists in page or post content
	    global $post;

	    if ( $post && strstr( $post->post_content, '[slam-display-results]' ) ) {
	        echo '<link rel="stylesheet" href="' .$this->plugin_url . '/slam-manager.css" />';
	    }
	}
	function register_js()
	{
		wp_register_script('slam-manager',$this->plugin_url."/slam-manager.js", array('jquery'),'0.1',true);
		wp_register_script('edit-in-place',$this->plugin_url.'/includes/jquery.editinplace.js', array('jquery'),'0.1',true);
		if(is_user_logged_in())
			$this->include_js();
	}
	function include_js() 
	{
	    global $post;

	    // Check if shortcode exists in page or post content
	    if ( strstr( $post->post_content, '[slam-display-results]' ) ) {

		//if so, load the AJAX editing scripts.
			wp_enqueue_script('edit-in-place');			
			wp_enqueue_script('slam-manager');
			$nonce = wp_create_nonce('slam-manager-nonce');
			wp_localize_script('slam-manager','SlamManager',array('ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce'=>$nonce));
			
	    }
	}	
	function calculate_penalty($input)
	{
		$penalty = explode(' ', $input);
		return $penalty[0];
	}
	function make_new_slam()
	{	
		global $wpdb;
		if(isset($_POST['title'])){
			$title = str_replace('+',' ',stripslashes($_POST['title']));
			$sql = "INSERT INTO $this->slam_table (title, slam_date)
			VALUES ('$title', NOW());";
			$wpdb->query($sql);
		}
		header('Content-Type: text/html');
		echo $wpdb->insert_id;
		die();
	}
	
	function spit_total_score()
	{
		global $wpdb;
		$id = $_GET['id'];
		$total = $wpdb->get_results(
			"	SELECT total_score 
				FROM $this->score_table 
				WHERE id = $id;
				"
			);
		header("Content-Type: text/html");
		echo $total[0]->total_score;
		die();
	}
	function update_score($result_id,$column_id, $update_value)
	{
		global $wpdb;
		$result = $wpdb->update(
			$this->score_table,
			array(
				$column_id => $update_value
			),
			array(
				'id' => $result_id
			),
			array(
				'%s'
			),
			array('%f')
			);
		if ($result) 
		{
			$this->update_total($result_id);
			return $update_value;
		}
		else
		{
			return false;
		}
		
	}
	function update_total($result_id)
	{
		global $wpdb;
		$data = $this->get_entry($result_id);
		$total = $this->calculate_total(
			array(
				$data->score_1,
				$data->score_2,
				$data->score_3,
				$data->score_4,
				$data->score_5
				),
				$data->penalty
		);
		
		$result = $wpdb->update(
			$this->score_table,
			array(
				'total_score' => $total
			),
			array(
				'id' => $result_id
			),
			array(
				'%s'
			),
			array('%f')
			);
		
		
	}
	function calculate_total($scores, $penalty)
	{	
		$total = 0;
		$max = 0; $min = 10;
		foreach ($scores as $score) {
			$total += $score;
			$max = max($score,$max);
			$min = min($score, $min);
		}
		return $total - $max - $min - $penalty;
	}
	function output_table_header()
	{
		?>
			<tr><th scope="col">Poet </th><th scope="col">Scores</th><th scope="col"></th>
			    <th scope="col"></th>
			    <th scope="col"></th>
			    <th scope="col"></th>
			    <th scope="col">Time Penalty</th>
			    <th scope="col">TOTAL</th>
				<?php
				if(is_user_logged_in()):
				?>
				<th scope="col">Admin </th>
				<?php endif; 
				?>
			  </tr>
			
		<?php
	}
	
	
	/*
		AJAX Handlers
	*/
	function handle_ajax_edit()
	{
			

		    // identify which entry we're updating
		
			$elementID = $_POST['element_id']; //expects format 'result_x_score_y'
			$entry_values = explode('_',$elementID);
		    
		
			//prepare values for posting
			$update_value = $_POST['update_value'];
			$result_id = $entry_values[1];
			$column_id = ($entry_values[2]=='score') ? $entry_values[2] ."_".$entry_values[3] : $entry_values[2]; //score_x needs to be concatenated!
			
			//post the results
			if($column_id == 'penalty')
				$update_value = $this->calculate_penalty($update_value);
			
			if(($column_id=='poet') || (is_numeric($update_value) && (($update_value <= 10) && ($update_value >= 0))))
				$new_value = $this->update_score($result_id,$column_id, $update_value);
			else
				$new_value = 'Please enter a valid number between 1 and 10.';
		    // response output
		    header( "Content-Type: text/html" );
			echo stripslashes($new_value);

		    die();
		
	}
	
	function edit_slam_title()
	{
		global $wpdb;
		if(!($the_nonce = $_POST['nonce']) || !(wp_verify_nonce($the_nonce,'slam-manager-nonce')))
			{
				die("Couldn't complete the edit.");
			}
		$message = "Try again.";
		if(isset($_POST['element_id'])&&isset($_POST['update_value'])) {
			$message = "element_id is ".$_POST['element_id'];
			$pattern = '/[0-9]+/';
			$id = array();
			$matches = preg_match($pattern, $_POST['element_id'],$id);
			$id = $id[0];
			$update_value = $_POST['update_value'];
			$wpdb->update(
				$this->slam_table,
				array(
					'title'=> $update_value
				),
				array("id"=>"$id"),
				array('%s')
			);
		}
		
		header("Content-Type:text/html");
		echo stripslashes($update_value);
		die();
	}
	function post_new_entry()
	{	
		if(!($nonce = $_POST['nonce']) || !(wp_verify_nonce($nonce,'slam-manager-nonce')))
		{
			die("Couldn't complete the edit.");
		}
		
		if(isset($_POST['poet'])) 
		{	
			$data = array();
			$data['slam_id'] = $slam_id = $_POST['slam_id'];
			$data['round_no'] = $round_no = $_POST['round_no'];
			$data['poet'] = $poet = str_replace('+',' ',$_POST['poet']);
			$data['score_1'] = $score_1 = $_POST['score_1'];
			$data['score_2'] = $score_2 = $_POST['score_2'];
			$data['score_3'] = $score_3 = $_POST['score_3'];
			$data['score_4'] = $score_4 = $_POST['score_4'];
			$data['score_5'] = $score_5 = $_POST['score_5'];
			$data['penalty'] = $penalty = $_POST['penalty'];
			$result_id = $this->insert_result($data);
			$this->update_total($result_id);
			$message = $result_id;
			
		}
		else 
		{
			$message = 0;
		}
		header("Content-Type: text/html");
		echo $message;
		die();
	}
	function delete_result() 
	{
		if(!($nonce = $_POST['nonce']) || !(wp_verify_nonce($nonce,'slam-manager-nonce')))
		{
			die("Couldn't complete the edit.");
		}
		$result = 0;
		global $wpdb;
		if(isset($_POST['id'])) {
			$id = $_POST['id'];
			$sql = "DELETE from $this->score_table WHERE id = $id";
			$result = $wpdb->query($sql);
		}
		header("Content-Type: text/html");
		echo $result;
		die();
	}
	function delete_slam()
	{
		global $wpdb;
		if(!($nonce = $_POST['nonce']) || !(wp_verify_nonce($nonce,'slam-manager-nonce')))
		{
			die("Couldn't complete the edit.");
		}
		$result = "No id.";
		
		if(isset($_POST['id'])) {
			$id = $_POST['id'];
			$sql1 = "DELETE FROM $this->slam_table WHERE id = $id;";
			$result1 = $wpdb->query($sql1);
			$sql2 = "DELETE FROM $this->score_table WHERE slam_id = $id;";
			$result2 = $wpdb->query($sql2);
		}
		header("Content-Type:text/html");
		echo $result1;
		die();
	}
	function insert_result($data)
	{	
		if(!($nonce = $_POST['nonce']) || !(wp_verify_nonce($nonce,'slam-manager-nonce')))
		{
			die("Couldn't complete the edit.");
		}
		global $wpdb;
		$wpdb->insert($this->score_table,
			$data,
			array(
				'%f','%f','%s','%f','%f','%f','%f','%f','%function'
			)
		);
		return $wpdb->insert_id;		
	}

	
	/*
		Content tags
	*/
	
	function get_round($results, $round_no)
	{
		$round = array();
		foreach ($results as $result) {
			if($result->round_no == $round_no)
				$round[] = $result;
		}
		return $round;
	}
	function nice_date($datetime)
	{
		//format the date and make it nice. :)
		//I should really be using the SQL formatting function. 
		//I probably will, eventually.
		
		$array = explode('-', $datetime);
		$year = $array[0];
		$month = $array[1];
		$temp = $array[2];
		$temp = explode(' ',$temp);
		$day = $temp[0];
		$date = $year.'-'.$month.'-'.$day;
		return $date;
		
	}
	function total_scores($scores, $penalty)
	{	
		
		/*
		takes an array of scores in an entry, 
		and totals them up using the magic slam formula.  
		Highest and lowest scores are dropped, and the time penalty is subtracted.
		*/
		
		$min = 10.0;
		$max = $total = 0;
		foreach ($scores as $score) {
			if($min > $score) 
				$min = $score;
			if($max < $score)
				$max = $score;
			$total += $score;
		}
		$total -= $penalty;
		$total -= ($min + $max);
		return $total;
	}
	function display_results()
	{
		include("display-results.php");
	}
	
	function how_many_rounds($results)
	{
		$count = count($results);
		$rounds = 0;
		foreach ($results as $result) {
			$curr = $result->round_no;
			if ($curr > $rounds) 
				$rounds = $curr;
		}
		return $rounds;
	}
	
	function output($result) 
	{
		if($result) {
			print $this->get_output($result);
		}
	}
	function get_entry($id)
	{
		global $wpdb;
		$query = "SELECT * FROM $this->score_table WHERE id = $id";
		$result = $wpdb->get_results($query);
		return $result[0];
	}
	function get_output($result)
	{
		
		//To do: separate this out as a new template.
		
		if(!$result) 
			return NULL;
		$output = "<tr id='result-".$result->id."'><td class='".( is_user_logged_in() ? "editable " : "")."poet' id='result_".$result->id."_poet'>".stripslashes($result->poet)."</td>";
		$output .= "<td class='".( is_user_logged_in() ? "editable " : "")."' id='result_".$result->id."_score_1'>".$result->score_1."</td>";
		$output .= "<td class='".( is_user_logged_in() ? "editable " : "")."' id='result_".$result->id."_score_2'>".$result->score_2."</td>";
		$output .= "<td class='".( is_user_logged_in() ? "editable " : "")."' id='result_".$result->id."_score_3'>".$result->score_3."</td>";
		$output .= "<td class='".( is_user_logged_in() ? "editable " : "")."' id='result_".$result->id."_score_4'>".$result->score_4."</td>";
		$output .= "<td class='".( is_user_logged_in() ? "editable " : "")."' id='result_".$result->id."_score_5'>".$result->score_5."</td>";
		$output .= "<td class='penalty ".( is_user_logged_in() ? "edit_penalty " : "")."' id='result_".$result->id."_penalty''>".$result->penalty."</td>";
		$output .= "<td class='total_score'>".$result->total_score."</td>
		".( is_user_logged_in() ? "<td><a href='#' class='delete button nice charcoal'>Delete</a></td>" : "")."</tr>";
		return $output;
	}
}
	

	
?>