<?php
/** Contact Form 7 integration for the Contact Us page. */
defined( 'ABSPATH' ) || exit;

function rockpack_contact_form7_choices() {
    $choices = array( '0' => 'Select a Contact Form 7 form' );
    if ( class_exists( 'WPCF7_ContactForm' ) ) {
        foreach ( WPCF7_ContactForm::find() as $form ) $choices[ $form->id() ] = $form->title();
    }
    return $choices;
}

function rockpack_contact_form7_id() {
    return absint( rp_option( 'contact_page_form_id', 0 ) );
}

function rockpack_contact_form7_is_current() {
    $form = class_exists( 'WPCF7_ContactForm' ) ? WPCF7_ContactForm::get_current() : null;
    return $form && $form->id() === rockpack_contact_form7_id();
}

/** Provision once, preserving other forms and subsequent edits in Contact Form 7. */
function rockpack_setup_contact_form7() {
    if ( ! class_exists( 'WPCF7_ContactForm' ) || ! current_user_can( 'manage_options' ) ) return;
    $options = get_option( 'rockpack_settings', array() );
    if ( ! is_array( $options ) ) $options = array();
    if ( array_key_exists( 'contact_page_form_id', $options ) ) return rockpack_contact_form7_id();
    $existing = get_posts( array( 'post_type'=>'wpcf7_contact_form', 'post_status'=>'publish', 'numberposts'=>1, 'meta_key'=>'_rockpack_contact_form', 'meta_value'=>'1' ) );
    if ( $existing ) {
        $id = $existing[0]->ID;
    } else {
        $form = WPCF7_ContactForm::get_template( array( 'title'=>'Contact Us - Rock Pack' ) );
        $properties = $form->get_properties();
        $definitions = array(
            'first_name'=>array('First Name','text* first-name maxlength:100 autocomplete:given-name'),
            'last_name'=>array('Last Name','text* last-name maxlength:100 autocomplete:family-name'),
            'email_label'=>array('Email Address','email* your-email maxlength:254 autocomplete:email'),
            'phone_label'=>array('Phone','tel your-phone maxlength:40 autocomplete:tel'),
            'message_label'=>array('Message Write Here','textarea* your-message maxlength:5000 40x4'),
        );
        $markup = '<div class="contact-enquiry__fields">' . "\n";
        foreach ( $definitions as $key => $definition ) {
            $label = rp_option( 'contact_page_' . $key, $definition[0] );
            $placeholder = str_replace( array('"','[',']',"\r","\n"), ' ', wp_strip_all_tags($label) );
            $message = 'message_label' === $key;
            $markup .= '<label' . ( $message ? ' class="contact-enquiry__message"' : '' ) . '><span class="contact-page__sr-only">' . esc_html($label) . ( 'phone_label' === $key ? ' (optional)' : ' (required)' ) . '</span>[' . $definition[1] . ' placeholder "' . esc_attr($placeholder) . '"]</label>' . "\n";
        }
        $submit = str_replace( array('"','[',']',"\r","\n"), ' ', wp_strip_all_tags(rp_option('contact_page_submit','Send Message')) );
        $markup .= '</div><div class="contact-enquiry__actions">[submit "' . esc_attr($submit) . '"]</div>';
        $properties['form'] = $markup;
        $properties['mail']['recipient'] = sanitize_email( rp_option('contact_page_recipient','fadyrafat999@gmail.com') ) ?: get_option('admin_email');
        $properties['mail']['subject'] = 'The Rock Pack - Website enquiry';
        $properties['mail']['body'] = "New website enquiry\n\nName: [first-name] [last-name]\nEmail: [your-email]\nPhone: [your-phone]\n\nMessage:\n[your-message]\n\nSent from [_site_title] ([_site_url]).";
        $properties['mail']['additional_headers'] = 'Reply-To: [your-email]';
        $properties['mail_2']['active'] = false;
        $form->set_properties( $properties );
        $id = $form->save();
        if ( ! $id ) return new WP_Error('contact_form','Contact Form 7 could not save the Contact Us form.');
        update_post_meta( $id, '_rockpack_contact_form', '1' );
    }
    $options['contact_page_form_id'] = (string) $id;
    update_option( 'rockpack_settings', $options );
    return $id;
}
add_action( 'admin_init', 'rockpack_setup_contact_form7', 20 );

function rockpack_render_contact_form7() {
    $id = rockpack_contact_form7_id();
    if ( class_exists('WPCF7_ContactForm') && $id && 'publish' === get_post_status($id) && WPCF7_ContactForm::get_instance($id) ) {
        echo do_shortcode( '[contact-form-7 id="' . $id . '" html_class="contact-enquiry__form"]' );
    } else {
        echo '<div class="contact-enquiry__form"><p>' . esc_html__('The contact form is temporarily unavailable. Please contact us using the email address or phone number above.','rockpack') . '</p></div>';
    }
}

// Keep the saved grid markup intact; other Contact Form 7 forms retain their own formatting.
add_filter( 'wpcf7_autop_or_not', static function ( $autop, $options ) {
    return 'form' === ($options['for'] ?? 'form') && rockpack_contact_form7_is_current() ? false : $autop;
}, 10, 2 );

// CF7 reads the textarea default after its placeholder; preserve validated career enquiries.
add_filter( 'wpcf7_form_tag', static function ( $tag, $replace ) {
    if ( $replace && rockpack_contact_form7_is_current() && 'your-message' === ($tag['name'] ?? '') && 'textarea' === ($tag['basetype'] ?? '') ) {
        $message = rockpack_job_enquiry_message();
        if ( $message ) {
            $tag['values'][] = $message;
            $tag['options'] = array_values(array_filter($tag['options'],static fn($option)=>!str_starts_with($option,'default:')));
            $tag['options'][] = 'default:' . count($tag['values']);
        }
    }
    return $tag;
}, 10, 2 );
