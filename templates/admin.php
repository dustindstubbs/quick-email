<h1>Send Mail</h1>

<strong>Start sending queue...</strong>
<br><br>

<?php

global $post;

// Get options
$all_options = get_option( 'quick_email_settings_option_name' );
$adminEmail = $all_options['admin_email_0'];
$senderEmail = $all_options['admin_email_1'];

$entries = get_posts( ['post_type' => 'entry'] );

foreach ( $entries as $post ) : setup_postdata( $post );
  $status = get_post_meta( get_the_ID(), 'Entry_status', true );

  if ($status == 'Pending'){
    $email = get_post_meta( get_the_ID(), 'Entry_email', true );
    $name = get_the_title();



    $content = '<strong>Name:</strong> ' . $name . '<br><br>';
    $content .= get_post_meta( get_the_ID(), 'Entry_content', true );

    $status = sendEntries( $email, $name, $content, $adminEmail, $senderEmail );

    echo get_the_title() . "'s email → <strong>" . $status . '</strong>' . '<br><br>';

    update_post_meta( get_the_ID(), 'Entry_status', $status );
  }
  else {
    echo get_the_title() . "'s email → <strong>Skipped, Already Sent</strong>" . '<br><br>';
  }
endforeach; wp_reset_postdata();

function sendEntries( $email, $name, $content, $adminEmail, $senderEmail ) {

    $to      = $adminEmail;
    $subject = 'Website Email From ' . $name;
    $message = $content;
    $headers = 'From: ' . $senderEmail         . "\r\n" .
               "Reply-To: $email"              . "\r\n" .
               'X-Mailer: PHP/' . phpversion() . "\r\n" .
               "Content-Type: text/html; charset=ISO-8859-1\r\n";

    $retval = mail($to, $subject, $message, $headers);

    if( $retval == true ) {
      return 'Sent';
    }else {
      return 'Failed';
    }

}

?>