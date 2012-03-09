jQuery(document).ready(function($) {
	

	$('.slam-results form').submit(function(){
		return false;
	});
	//disable standard form submission so weird things don't happen when 'return' is pressed
	
	/**
	 * HELPER FUNCTIONS
	 */
	
	
	updateTotal = function(theID) {
		
		//show results of edits to the scores in the 'Total' box
		var intRegex = /[0-9 -()+]+$/;
		id = theID.match(intRegex)[0];
		$.get(
			SlamManager.ajax_url,
			{
				action:'requestTotal',
				id: id
				},
			function(data){
				$('#' + theID + ' .total_score').html(data);
			}
			);
	};
	queryStrToJSON = function(str){
		
		//obviously, convert a '?param=val' string to a JSON object
		
		var arr = str.split('&');
		var obj = {};
		for(var i = 0; i < arr.length; i++) {
		    var bits = arr[i].split('=');
		    obj[bits[0]] = bits[1];
		}
		return obj;
	}
	
	/*
		EDIT-IN-PLACE FUNCTIONS
	*/
	
	$(".editable").editInPlace(
		{	
			url: SlamManager.ajax_url,
			params:"&action=editResults&nonce=" + SlamManager.nonce,
			success: function(e)
			{
				theID = this.parentNode.id;
				updateTotal(theID);
			}
		}
	);
	$(".edit_penalty").editInPlace(
		{	
			url: SlamManager.ajax_url,
			params:"&action=editResults&nonce=" + SlamManager.nonce,
			field_type: "select",
			select_options: "0, 0.5 (10+ seconds), 1.0 (20+ seconds), 1.5 (30+ seconds), 2.0 (40+ seconds), 2.5 (50+ seconds), 3.0 (60+ seconds), 3.5 (1 minute 10 seconds), 4.0 (1 minute 20 seconds), 4.5 (1 minute 30 seconds), 5.0 (1 minute 40 seconds), 5.5 (1 minute 50 seconds), 6.0 (2 minutes)",
			success: function(e)
			{
				theID = this.parentNode.id;
				updateTotal(theID);
			}
		}
	);
	$('.edit_slam_title').editInPlace(
		{
			url: SlamManager.ajax_url,
			params: "&action=editSlamTitle&nonce=" + SlamManager.nonce,
			success: function(e){
			}
		}
	);
	
	
	/*
		FORM ACTIONS
	*/
	
	

	
	/*
		Delete
	*/
	$('.delete').click(function(){
		if (confirm('Are you sure you want to delete this entry?')==true){
		theID = this;
		while(!theID.id) {
			theID = theID.parentNode;
		}
		theID=theID.id;
		var intRegex = /[0-9 -()+]+$/;
		id = theID.match(intRegex)[0];
		$.post(SlamManager.ajax_url,
			{
				action: 'deleteResult',
				id: id,
				nonce: SlamManager.nonce
			},
			function(){
				location.reload();
			}
		
		);
	}
		return false;
	});
	
	/*
		Save
	*/
	
	$('.post_result a').click(function()
	{	
		var r = this;
		while(!r.id){
			r = r.parentNode;
		}
		var arr = r.id.split('_');
		
		for(el in arr){	
			if (arr[el]=="round"){
				roundNo = arr[parseInt(el)+1];
			}
			if(arr[el]=="slam") {
				slamID = arr[parseInt(el)+1];
			}
		}
	$('#submit-me input[name=round_no]').attr('value', roundNo);
	$('#submit-me input[name=slam_id]').attr('value', slamID);
	var formData = $('#submit-me').serialize();
	str = formData + "&action=postNewEntry&nonce=" + SlamManager.nonce;
	obj = queryStrToJSON(str);
	$.post(SlamManager.ajax_url,
		obj,
		function()
		{
			location.reload();
		}
	);
	return false;
	
	}
	);
	
	
	/*
		Delete slam
	*/
	
	$('.delete_slam').click(function(){
		if(confirm('Are you sure you want to delete this slam?  That\'s crazy!')==true){
		var slam_id = $(this).attr('value');
		$.post(SlamManager.ajax_url,
			{
				action: 'deleteSlam',
				id: slam_id,
				nonce: SlamManager.nonce
			},
			function(data){
				setTimeout(1000);
				location.reload();
			}
		);
		return false;
		}
		
	});
	
	/*
		New slam
	*/
	$('#make_new_slam').click(function(){
		$('#new_slam').toggle();
	});
	$('#new_slam input').change(function(){
		data = $('#new_slam').serialize() + "&action=makeNewSlam&nonce=" + SlamManager.nonce;
		data = queryStrToJSON(data);
		$.post(
			SlamManager.ajax_url, 
			data, 
			function(response) {
				setTimeout(1000);
				location.reload();
			}
		);
		return false;
	});
});
