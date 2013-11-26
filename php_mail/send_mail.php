<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<title>回复</title>
</head>
</html>
<?php
require '/phpmailer/class.phpmailer.php';
function smtp_mail($sendto, $subject, $body) {
	require 'mail.config.php';
	$mail = new PHPMailer ();
	$mail->IsSMTP (); // send via SMTP
	$mail->Host = $config ['host']; // SMTP servers
	$mail->Username = $config ['username'];
	$mail->SMTPAuth = true; // turn on SMTP authentication
	$mail->Password = $config ['password'];
	$mail->From = $config ['from'];
	$mail->FromName = $config ['fromName'];
	$mail->Subject = $subject;
	$mail->CharSet = $config ['charset'];
	$mail->AddAddress ( $sendto );
	$mail->WordWrap = 8;
	$mail->IsHTML ( true ); // send as HTML
	$mail->MsgHTML ( $body );
	
	if (! $mail->Send ()) {
		echo "error <p>";
		echo "error: " . $mail->ErrorInfo;
		exit ();
	} else {
		echo "success!";
	}
}
if (isset ( $_POST ['comm_submit'] )) {
	$sendto=$_POST['sendto_email'];
	$content=$_POST['content'];
	if(empty($sendto)||empty($content)){
		echo '邮箱地址和邮件内容是必填内容!';
	}else{
		smtp_mail ( $_POST ['sendto_email'], $_POST ['subject'], $_POST ['content'] );
	}
}
?>
<body>
	<form name="commentForm" method="post">
		<ul>
			<li><span class="name">接收邮箱</span> <input type="email" name="sendto_email" placeholder="对方的邮箱(xxx@163.com)" required/></li>
			<li><span class="name">邮件主题</span>
			<input type="text" name="subject" placeholder="邮件主题" /></li>
			<li><span class="name">回复内容</span>
			<textarea type="text" name="content" cols="60" rows=8></textarea></li>
		</ul>
		<input type="submit" name="comm_submit" />
	</form>
</body>
</html>