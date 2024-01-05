<?php 

//privatechat.php

session_start();

if(!isset($_SESSION['user_data']))
{
	header('location:index.php');
}

require('database/ChatUser.php');

require('database/ChatRooms.php');

?>

<!DOCTYPE html>
<html>
<head>
	<title>Chat application in php using web scocket programming</title>
	<!-- Bootstrap core CSS -->
    <link href="vendor-front/bootstrap/bootstrap.min.css" rel="stylesheet">

    <link href="vendor-front/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

    <link rel="stylesheet" type="text/css" href="vendor-front/parsley/parsley.css"/>

    <!-- Bootstrap core JavaScript -->
    <script src="vendor-front/jquery/jquery.min.js"></script>
    <script src="vendor-front/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor-front/jquery-easing/jquery.easing.min.js"></script>

    <script type="text/javascript" src="vendor-front/parsley/dist/parsley.min.js"></script>
	<style type="text/css">
		html,
		body {
		  height: 100%;
		  width: 100%;
		  margin: 0;
		}
		#wrapper
		{
			display: flex;
		  	flex-flow: column;
		  	height: 100%;
		}
		#remaining
		{
			flex-grow : 1;
		}
		#messages {
			height: 200px;
			background: whitesmoke;
			overflow: auto;
		}
		#chat-room-frm {
			margin-top: 10px;
		}
		#user_list
		{
			height:450px;
			overflow-y: auto;
		}

		#messages_area
		{
			height: 75vh;
			overflow-y: auto;
			/*background-color:#e6e6e6;*/
			background-color: #ece5dd;
		}
		.me-sender{
			color: #504c4c;
            background-color: #dcf8c6;
            border-color: #cdeab6;
		}
		.bg-light{
			color:#504c4c;
		}

	</style>
</head>
<body>
	<div class="container-fluid">
		
		<div class="row">

			<div class="col-lg-3 col-md-4 col-sm-5" style="background-color: #f1f1f1; height: 100vh; border-right:1px solid #ccc;">
				<?php
				
				$login_user_id = '';

				$token = '';

				foreach($_SESSION['user_data'] as $key => $value)
				{
					$login_user_id = $value['id'];

					$token = $value['token'];

				?>
				<input type="hidden" name="login_user_id" id="login_user_id" value="<?php echo $login_user_id; ?>" />

				<input type="hidden" name="is_active_chat" id="is_active_chat" value="No" />

				<div class="mt-3 mb-3 text-center">
					<img src="<?php echo $value['profile']; ?>" class="img-fluid rounded-circle img-thumbnail" width="150" />
					<h3 class="mt-2"><?php echo $value['name']; ?></h3>
					<a href="profile.php" class="btn btn-secondary mt-2 mb-2">Edit</a>
					<input type="button" class="btn btn-primary mt-2 mb-2" id="logout" name="logout" value="Logout" />
				</div>
				<?php
				}

				$user_object = new ChatUser;

				$user_object->setUserId($login_user_id);

				$user_data = $user_object->get_user_all_data_with_status_count();

				?>
				<div class="list-group" style=" max-height: 100vh; margin-bottom: 10px; overflow-y:scroll; -webkit-overflow-scrolling: touch;">
					<?php
					
					foreach($user_data as $key => $user)
					{
						$icon = '<i class="fa fa-circle text-danger"></i>';

						if($user['user_login_status'] == 'Login')
						{
							$icon = '<i class="fa fa-circle text-success"></i>';
						}

						if($user['user_id'] != $login_user_id)
						{
							if($user['count_status'] > 0)
							{
								$total_unread_message = '<span class="badge badge-danger badge-pill">' . $user['count_status'] . '</span>';
							}
							else
							{
								$total_unread_message = '';
							}

							echo "
							<a class='list-group-item list-group-item-action select_user' style='cursor:pointer' data-userid = '".$user['user_id']."'>
								<img src='".$user["user_profile"]."' class='img-fluid rounded-circle img-thumbnail' width='50' />
								<span class='ml-1'>
									<strong>
										<span id='list_user_name_".$user["user_id"]."'>".$user['user_name']."</span>
										<span id='userid_".$user['user_id']."'>".$total_unread_message."</span>
									</strong>
								</span>
								<span class='mt-2 float-right' id='userstatus_".$user['user_id']."'>".$icon."</span>
							</a>
							";
						}
					}


					?>
				</div>
			</div>
			
			<div class="col-lg-9 col-md-8 col-sm-7">
				<br />
		        <h3 class="text-center">Realtime One to One Chat App using Ratchet WebSockets with PHP Mysql - Online Offline Status - 8</h3>
		        <hr />
		        <br />
		        <div id="chat_area" style="display:none">


				<div class="card">
				<div class="card-header">
					<div class="row">
						<div class="col col-sm-6">
							<b>Chat with <span class="text-danger" id="chat_user_name">Username</span></b>
							<span class="text-success" id="user_activity"></span>
						</div>
						<div class="col col-sm-6 text-right">
							<a href="chatroom.php" class="btn btn-success btn-sm">Private Chat</a>&nbsp;&nbsp;&nbsp;
							<button type="button" class="close" id="close_chat_area" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
					</div>
				</div>
				<div class="card-body" id="messages_area">

				</div>
			</div>

			<form id="chat_form" method="POST" data-parsley-errors-container="#validation_error">
				<div class="input-group mb-3" style="height:7vh">
					<textarea class="form-control" id="chat_message" name="chat_message" placeholder="Type Message Here" data-parsley-maxlength="1000" data-parsley-pattern="/^[a-zA-Z0-9?_. ]+$/" required></textarea>
					<div class="input-group-append">
						<button type="submit" name="send" id="send" class="btn btn-primary"><i class="fa fa-paper-plane"></i></button>
					</div>
				</div>
				<div id="validation_error"></div>
				<br />
			</form>



				</div>
			</div>
			
		</div>
	</div>
</body>
<script type="text/javascript">
	$(document).ready(function(){

		var receiver_id = '';
		const login_user_id = $('#login_user_id').val();

		var messagesArea = $('#messages_area');
/*
       messagesArea.on('scroll', function() {
        // var scrollPosition = messagesArea.scrollTop();
        // console.log('Scroll Position: ' + scrollPosition);
		if ($(this).scrollTop() > 546.66) {
						console.log(get_msg_token('Yes'));
				}
		
      });
	*/		

		


		var conn = new WebSocket('ws://localhost:8080?token=<?php echo $token; ?>&user_id=<?php echo $login_user_id; ?>');

		conn.onopen = function(event){
			console.log('Connection Established');
		
		};


		conn.onmessage = function(event){
			
			var data = JSON.parse(event.data);
			// var data = event.data;

			console.log(data);
			$('#user_activity').text('');
			if(data.status_type == 'Online'){
				$('#userstatus_'+data.user_id_status).html('<i class="fa fa-circle text-success"></i>');
			}else if(data.status_type == 'Offline'){
				$('#userstatus_'+data.user_id_status).html('<i class="fa fa-circle text-danger"></i>');
			}else if(data.command == 'typing'){

		            const typingTimeoutDuration = 2000; // 2 seconds
					if (data.to != login_user_id) {
						// Check if there's an existing typingTimeout and clear it
							$('#user_activity').text('typing...');
						

						// Set a timeout to clear typing status after 2 seconds
						setTimeout(function () {
							typingCount = 0; // Reset the typing count
							$('#user_activity').text('');
						}, typingTimeoutDuration);
					}
				


			}else if(data.command == 'private'){
				
				var row_class = '';
				var background_class = '';

				if(data.from == 'Me'){
					row_class = 'row justify-content-end';
					background_class = 'me-sender';
				}else{
					row_class = 'row justify-content-start';
					background_class = 'bg-light';
				}

				if(receiver_id == data.user_id || data.from == 'Me'){
					
					if($('#is_active_chat').val() == 'Yes'){
						var html_data = `
						<div class="`+row_class+`">
							<div class="col-sm-10" token="`+data.token+`"  target_id="`+data.user_id+`" status="1">
								<div class="shadow-sm alert `+background_class+`">
									<b>`+data.from+` - </b>`+data.msg+`<br />
									<div class="text-right">
										<small><i>`+data.datetime+`</i></small>
									</div>
								</div>
							</div>
						</div>
						`;

						$('#messages_area').append(html_data);

						$('#messages_area').scrollTop($('#messages_area')[0].scrollHeight);

						$('#chat_message').val("");
					}
				}else{
					var count_chat = $('#userid'+data.user_id).text();

					if(count_chat == ''){
						count_chat = 0;
					}

					count_chat++;

					$('#userid_'+data.user_id).html('<span class="badge badge-danger badge-pill">'+count_chat+'</span>');
				}



				var messagesArea = $('#messages_area');
				messagesArea.on('scroll', function() {
					if ($(this).scrollTop() > 546.66) {
						msg_token = get_msg_token(data.user_id,1);
						console.log(msg_token);
						send_seen(msg_token);
				}
				});
			}
		};

		conn.onclose = function(event){
			console.log('connection close');
		};

		function send_seen(token){
			var data = {
					command:'msg_seen',
					token: token
					}
				 conn.send(JSON.stringify(data));
		}

		function make_chat_area(user_name){
			$('#chat_user_name').text(user_name);
			$('#chat_area').css('display', 'block');
			$('#chat_form').parsley();
		}
		
		$(document).on('click', '.select_user', function(){

			receiver_id = $(this).data('userid');

			var from_user_id = $('#login_user_id').val();

			var receiver_user_name = $('#list_user_name_'+receiver_id).text();

			$('.select_user.active').removeClass('active');

			$(this).addClass('active');

			make_chat_area(receiver_user_name);

			$('#is_active_chat').val('Yes');

			$.ajax({
				url:"action.php",
				method:"POST",
				data:{action:'fetch_chat', to_user_id:receiver_id, from_user_id:from_user_id},
				dataType:"JSON",
				success:function(data){
					if(data.length > 0)
					{
						var html_data = '';

						for(var count = 0; count < data.length; count++)
						{
							var row_class= ''; 
							var background_class = '';
							var user_name = '';

							if(data[count].from_user_id == from_user_id){
								row_class = 'row justify-content-end';

								background_class = 'me-sender';

								user_name = 'Me';
							}
							else{
								row_class = 'row justify-content-start';

								background_class = 'bg-light';

								user_name = data[count].from_user_name;
							}

							if(data[count].status == '1'){
								status_color = "#34b7f1";
							}else{
								status_color = "#959b9d";
							}

							html_data += `
							<div class="`+row_class+`">
								<div class="col-sm-10">
									<div class="shadow alert `+background_class+`"  token="`+data[count].token+`" target_id="`+data[count].f_id+`" status="`+data[count].status+`">
										<b>`+user_name+` - </b>
										`+data[count].chat_message+`<br />
										<div class="text-right">
											<small><i>`+data[count].timestamp+`</i></small>
											<i class="fas fa-check-double" style="color: `+status_color+`;"></i>
										</div>
										
									</div>
								</div>
							</div>
							`;
						}

						$('#userid_'+receiver_id).html('');

						$('#messages_area').html(html_data);

						$('#messages_area').scrollTop($('#messages_area')[0].scrollHeight);
					}
				}
			})

		});

		$(document).on('click', '#close_chat_area', function(){

			$('#chat_area').html('');

			$('.select_user.active').removeClass('active');

			$('#is_active_chat').val('No');

			receiver_id = '';

		});

		$(document).on('submit', '#chat_form', function(event){

			event.preventDefault();

			if($('#chat_form').parsley().isValid())
			{
				var user_id = parseInt($('#login_user_id').val());

				var message = $('#chat_message').val();

				var data = {
					user_id: user_id,
					msg: message,
					receiver_id:receiver_id,
					token: generateToken(),
					command:'private'
				};

				conn.send(JSON.stringify(data));
			}

		});

		$(document).on('keydown', '#chat_message', function() {
			var user_id = parseInt($('#login_user_id').val());
			
               var isTyping = $(this).val() !== '';
			   
				var data = {
					
				command:'typing',
                typing: isTyping,
               
			    }
             conn.send(JSON.stringify(data));
        });

		$('#logout').click(function(){

			user_id = $('#login_user_id').val();

			$.ajax({
				url:"action.php",
				method:"POST",
				data:{user_id:user_id, action:'leave'},
				success:function(data)
				{
					var response = JSON.parse(data);
					if(response.status == 1)
					{
						conn.close();

						location = 'index.php';
					}
				}
			})

		});

	})


	function doneTyping() {
    $('#user_activity').text('');
    }
	function generateToken() {
	const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	let token = '';
	for (let i = 0; i < 20; i++) {
		const randomIndex = Math.floor(Math.random() * characters.length);
		token += characters.charAt(randomIndex);
	}
  return token;
}
function get_msg_token(id,status) {
  const divElements = document.querySelectorAll('.me-sender');
  const tokens = [];

  divElements.forEach((div) => {
    const token = div.getAttribute('token');
    const divStatus = div.getAttribute('status');
    const divid = div.getAttribute('target_id');

    if (divStatus == status && id == divid) {

      tokens.push(token);
    }
  });

  return tokens;
}





</script>
</html>