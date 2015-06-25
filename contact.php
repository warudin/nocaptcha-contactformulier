<!--

benodigde stappen:

1.	in header plaatsen:
	<script src='https://www.google.com/recaptcha/api.js'></script>


2.	recaptchalib.php in map van contact.php plaatsen


3.	in functions.php toevoegen voor return-path van mail:

	class email_return_path {
	  	function __construct() {
			add_action( 'phpmailer_init', array( $this, 'fix' ) );    
	  	}
	 
		function fix( $phpmailer ) {
		  	$phpmailer->Sender = $phpmailer->From;
		}
	}
	new email_return_path();


4.	domein toevoegen en secret-code ophalen op:
	https://www.google.com/recaptcha/admin#list

	$secret vullen met secret-code van nocaptcha
-->

<?php

// nocaptcha inladen
require_once "recaptchalib.php";
$secret = " ??? ";
$recapresp = null;
$reCaptcha = new ReCaptcha($secret);

// nocaptcha respons
if ($_POST["g-recaptcha-response"]) {
    $recapresp = $reCaptcha->verifyResponse(
        $_SERVER["REMOTE_ADDR"],
        $_POST["g-recaptcha-response"]
    );
}

//function to generate response
$response = "";
function my_contact_form_generate_response($type, $message){
	global $response;
	if($type == "success") $response = "<mark>{$message}</mark>";
	else $response = "<mark class='error'>{$message}</mark>";
}

//response messages
$not_human			= "Verificatie niet voltooid!";
$missing_content	= "Vul alstublieft alle vereiste velden in.";
$email_invalid		= "Het opgegeven mailadres lijkt niet te kloppen.";
$message_unsent		= "Het bericht kon niet verzonden worden, probeer het alsjeblieft opnieuw.";
$message_sent		= "Het bericht is verzonden en er wordt spoedig contact met u opgenomen.";
$recap_error		= "Verificatie niet voltooid!";
 
//waarden uit formulier ophalen
$name = $_POST['message_name'];
$telephone = $_POST['message_telephone'];
$email = $_POST['message_email'];
$website = $_POST['message_website'];
$message = $_POST['message_text'];
 
//php mailer variables
$subject = "Bericht via xxx"
$to = get_option('admin_email');
$headers = 'From: '. $email . "\r\n" . 'Reply-To: ' . $email . "\r\n";

//afhandeling formulier
if ($_POST['submitted']) {
	if ($recapresp->success) //verificatie voltooid
	{
		if (filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE) {
		  my_contact_form_generate_response("error", $email_invalid);
		}
		else //mailadres lijkt te kloppen
		{
			//naam, telefoonnummer, website & bericht ingevuld?
			if (empty($name) || empty($telephone) || empty($website) || empty($message))
			{
				my_contact_form_generate_response("error", $missing_content);
			}
			//klaar om mail te versturen!
			else {
				add_filter( 'wp_mail_content_type', 'set_content_type' );
				function set_content_type( $content_type ) {
					return 'text/html';
				}
				$messageText =  $message .'
				<br />
				<br />
				Bericht afkomstig van: '. $name .'<br />
				Mailadres: '. $email .'<br />
				Telefoon: '. $telephone .'<br />
				Website: '.$website;

				$sent = wp_mail($to, $subject, $messageText, $headers);

				if($sent) my_contact_form_generate_response("success", $message_sent); //message sent!
				unset ($name, $telephone, $email, $website, $message); //variabelen leeggooien
			}
		}
	} 
	else if ($recapresp == null) //verificatie niet voltooid
	{
		my_contact_form_generate_response("error", $recap_error);
	}
	else //ergens is iets mis gegaan..
	{
		my_contact_form_generate_response("error", $message_unsent);
	}
}

?>

<?php 
  /* foreach ($_POST as $key => $value) {
    echo '<p><strong>' . $key.':</strong> '.$value.'</p>';
  } */
?>

<form method="post" action="<?php the_permalink(); ?>">
	<fieldset>
		<?php echo $response; ?>
		<div class="form-group">
			<div class="input-group">
				<div class="input-group-addon"><i class="fa fa-user fa-fw"></i></div>
				<input type="text" name="message_name" class="form-control" placeholder="voor- & achternaam" value="<?php if (!$sent) echo esc_attr($_POST['message_name']); ?>" required>
			</div>
			<div class="input-group">
				<div class="input-group-addon"><i class="fa fa-phone fa-fw"></i></div>
				<input type="text" name="message_telephone" class="form-control" placeholder="telefoonnummer" value="<?php if (!$sent) echo esc_attr($_POST['message_telephone']); ?>" required>
			</div>
			<div class="input-group">
				<div class="input-group-addon"><i class="fa fa-envelope-o fa-fw"></i></div>
				<input type="text" name="message_email" class="form-control" placeholder="e-mail" value="<?php if (!$sent) echo esc_attr($_POST['message_email']); ?>" required>
			</div>
			<div class="input-group">
				<div class="input-group-addon"><i class="fa fa-desktop fa-fw"></i></div>
				<input type="text" name="message_website" class="form-control" placeholder="website" value="<?php if (!$sent) echo esc_attr($_POST['message_website']); ?>" required>
			</div>
			<div class="input-group">
				<div class="input-group-addon"><i class="fa fa-font fa-fw"></i></div>
				<textarea class="form-control" name="message_text" rows="18" placeholder="Probleemomschrijving" required><?php if (!$sent) echo htmlspecialchars($_POST['message_text']); ?></textarea>
			</div>
			<div class="g-recaptcha" data-sitekey="6LeTfggTAAAAACs0mOzf2hKS47-IatFmLMhHPTgn"></div>
			<input type="hidden" name="submitted" value="1">
			<input type="submit" value="Verzenden" class="btn btn-default" />
		</div>
	</fieldset>
</form>