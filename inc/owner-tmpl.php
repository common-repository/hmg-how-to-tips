<?php

/* pull in our meta info */
$owner = get_post_meta($GLOBALS['post']->ID, 'talent_details_owner', true);
$talent_id = get_post_meta($GLOBALS['post']->ID, 'talent_details_id', true);
$title = $GLOBALS['post']->post_title;
$excerpt = get_the_excerpt();

/* set up the owner object */

$user = get_user_by('login', $owner);

$user_email = $user->user_email;

if ($this->license) {
    $to = ($this->options['email_override']) ? $this->options['email_override'] : $user_email;
} else {
    $to = $this->options['email_override'];
}

$avatar = get_avatar( $user->ID, $size = '96');

$contact_btn_text = $this->options['contact_btn_text'];

$call_to_action_text = $this->options['call_to_action_text'];
$terms = get_the_terms( $GLOBALS['post']->ID, 'type' );
$terms_text = '';

if ( $terms && ! is_wp_error( $terms ) ) {
	$terms_links = array();
	foreach ( $terms as $term ) {
		$terms_links[] = '<li><a href="' . get_term_link($term->slug, 'type') .'" class="st-btn st-btn-mini">'.$term->name.'</a></li>';
	}			
	$terms_text = join( "", $terms_links );
}

if ($terms_text) {
    $terms_label = $this->options['taxonomy_label_plural'];
    $terms_text = "<div class='st_types'><span class='st_terms_label'>$terms_label: </span><ul>$terms_text</ul></div>";
}

$contact_btn_class = ($this->has_jetpack) ? 'TContactBtn2':'TContactBtn';

$contact_form_class = ($this->has_jetpack) ? 'st_message_tmpl2':'st_message_tmpl';

$submit_btn_class = 'st-btn-info';

if ($_POST['contact-form-id']) {
    // this is after a submission from jetpack - let's make it pretty!
    $submit_btn_class = 'st-btn-success';
    $contact_btn_text = 'Message Sent!';
}

$tmpl = <<<EOD
$terms_text
<div class="st_owner_box clearfix">
<div>
<h2>$user->first_name $user->last_name</h2>
$avatar
$user->user_description 
</div>
<div style="float:right;">
    <strong>$call_to_action_text</strong>
    <a class="st-btn $submit_btn_class st-btn-large $contact_btn_class" href="javascript:void(0);" id="Tbtn$talent_id" >$contact_btn_text</a>
</div>
</div>

<div id="T$talent_id" class="$contact_form_class" style="display:none;" title="Contact $user->first_name $user->last_name">
    <div class="st_owner_box">
        <h2>RE: $title</h2>
        <em>$excerpt</em>
        <div class="validateTips"></div>
        <div>
EOD;

if ($this->has_jetpack) { 
       
    $tmpl .= <<<EOD1
            [contact-form to="$to" subject="RE: $title #$talent_id"] 
            [contact-field label="Name" type="name" required="true" /] 
            [contact-field label="Email" type="email" required="true" /] 
            [contact-field label="Message" type="textarea" required="true" /] 
            [/contact-form] 
EOD1;

} else {

    $tmpl .= <<<EOD2
            <form class="st_message_form" action="#">
                <div>
                <input type="hidden" name="talentid" id="talentid" value="$talent_id" />
                <input type="hidden" name="action" id="action" value="st_mail" />
                <input type="hidden" name="owner" id="owner" value="$owner" />
                <input type="hidden" name="subject" id="subject" value="RE: $title" />
                <label for="name">Your Name</label>
                <input type="text" name="name" id="name" class="text ui-widget-content ui-corner-all" />
                </div>
                <div>
                <label for="email">Your Email</label>
                <input type="text" name="email" id="email" value="" class="text ui-widget-content ui-corner-all" />
                </div>
                <div>
                <label for="message">Message</label>
                <textarea name="message" id="message" class="textarea ui-widget-content ui-corner-all"></textarea/>
                </div>
            </form>
EOD2;

}

$tmpl .= <<<EOD3
        </div>
    </div>
</div>
EOD3;

$tmpl = do_shortcode($tmpl);

?>