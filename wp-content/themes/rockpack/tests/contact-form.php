<?php
/** Local Contact Form 7 integration checks. All outgoing mail is intercepted. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$_SERVER['REQUEST_SCHEME']='http';$_SERVER['HTTP_HOST']=$_SERVER['SERVER_NAME']='figma-htmx-site.test';
$_SERVER['REQUEST_URI']='/contact-us/';$_SERVER['REMOTE_ADDR']='127.0.0.1';
$_SERVER['HTTP_USER_AGENT']='Rockpack local integration test';$_SERVER['REQUEST_METHOD']='GET';
require dirname(__DIR__,4).'/wp-load.php';
require_once get_template_directory().'/includes/data.php';
if(!str_ends_with((string)wp_parse_url(home_url(),PHP_URL_HOST),'.test'))exit('Local .test site required.');
if(!class_exists('WPCF7_ContactForm')||!rockpack_contact_form7_id())exit('Set up the Contact Form 7 integration first.');
global $wpdb;
foreach(array($wpdb->posts,$wpdb->postmeta,$wpdb->options) as $table){
    $status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$wpdb->esc_like($table)));
    if(!$status||'InnoDB'!==$status->Engine)exit('InnoDB rollback support required.');
}
remove_action('transition_post_status',array('Rockpack_Static_SSG','on_transition_post_status'),10);
remove_action('wp_after_insert_post',array('Rockpack_Static_SSG','on_page_structure_change'),10);
$checks=0;$mail=array();$mail_result=true;$failure=null;
$intercept=static function($pre,$args)use(&$mail,&$mail_result){$mail[]=$args;return $mail_result;};
add_filter('pre_wp_mail',$intercept,PHP_INT_MAX,2);
$check=static function($ok,$label)use(&$checks){if(!$ok)throw new RuntimeException($label);$checks++;echo 'PASS '.$label.PHP_EOL;};
$render=static function(){ob_start();rockpack_render_contact_form7();return ob_get_clean();};
$parse=static function($html){$doc=new DOMDocument();@$doc->loadHTML('<?xml encoding="UTF-8">'.$html);return new DOMXPath($doc);};
// Each submit normally runs in a fresh HTTP request. Reset only CF7's request singleton in this CLI suite.
$singleton=new ReflectionProperty(WPCF7_Submission::class,'instance');
$submit=static function($data)use($singleton){
    $singleton->setValue(null,null);
    $_SERVER['REQUEST_METHOD']='POST';$_POST=wp_slash($data);
    return WPCF7_ContactForm::get_instance(rockpack_contact_form7_id())->submit();
};
$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$wpdb->query('START TRANSACTION');
try{
    wp_set_current_user(0);
    $id=rockpack_contact_form7_id();$form=WPCF7_ContactForm::get_instance($id);$properties=$form->get_properties();
    $check('publish'===get_post_status($id),'configured Contact Form 7 form exists');
    $validator=new WPCF7_ConfigValidator($form);$validator->validate();
    $check($validator->is_valid(),'CF7 form and mail configuration pass plugin validation: '.wp_json_encode($validator->collect_error_messages()));
    $html=$render();$xpath=$parse($html);
    $check(1===$xpath->query('//form[contains(@class,"wpcf7-form") and contains(@class,"contact-enquiry__form")]')->length,'native CF7 form uses the existing design class');
    $check(1===$xpath->query('//input[@name="_wpcf7" and @value="'.$id.'"]')->length,'form includes native CF7 submission metadata');
    $check(4===$xpath->query('//div[@class="contact-enquiry__fields"]/label/input | //div[@class="contact-enquiry__fields"]/label/span/input')->length,'form keeps its four input fields');
    $check(1===$xpath->query('//label[contains(@class,"contact-enquiry__message")]//textarea[@name="your-message"]')->length,'message spans the grid with an accessible label');
    $check(!str_contains($html,'rockpack_contact_nonce')&&!has_action('admin_post_nopriv_rockpack_contact'),'retired custom submission handler is disconnected');
    $base=array('first-name'=>'Test','last-name'=>'Person','your-email'=>'test@example.com','your-phone'=>'+20 (100) 123-4567','your-message'=>"Test only\nSecond line",'_wpcf7'=>$id,'_wpcf7_unit_tag'=>'wpcf7-f'.$id.'-o1','_wpcf7_container_post'=>(int)get_page_by_path('contact-us')->ID);
    $result=$submit($base);
    $check('mail_sent'===$result['status'],'valid CF7 submission reaches the intercepted mail transport');
    $expected_mail_calls=empty($properties['mail_2']['active'])?1:2;
    $check($expected_mail_calls===count($mail)&&$properties['mail']['recipient']===$mail[0]['to'],'enquiry goes to the configured recipient, allowing an enabled CF7 automatic reply');
    $check(str_contains(implode("\n",(array)$mail[0]['headers']),'Reply-To: test@example.com'),'visitor email is used safely as Reply-To');
    $message_text=html_entity_decode(wp_strip_all_tags(preg_replace('/<br\s*\/?>/i',"\n",$mail[0]['message'])),ENT_QUOTES,'UTF-8');
    $check(preg_match('/Test\s+Person/u',$message_text)&&preg_match('/Test only\s+Second line/u',$message_text),'plain-text or HTML mail includes the submitted name and multiline message');
    $before=count($mail);$result=$submit(array_merge($base,array('recipient'=>'attacker@example.com')));
    $check('mail_sent'===$result['status']&&$mail[$before]['to']===$properties['mail']['recipient'],'posted recipient cannot change the enquiry destination');
    foreach(array('first-name','last-name','your-email','your-message') as $key){
        $before=count($mail);$result=$submit(array_merge($base,array($key=>'')));
        $check('validation_failed'===$result['status']&&count($mail)===$before,'required '.$key.' is validated before sending');
    }
    foreach(array('your-email'=>'invalid-email','your-phone'=>'not a phone','your-message'=>str_repeat('x',5001)) as $key=>$value){
        $before=count($mail);$result=$submit(array_merge($base,array($key=>$value)));
        $check('validation_failed'===$result['status']&&count($mail)===$before,'invalid or oversized '.$key.' is rejected');
    }
    $check('mail_sent'===$submit(array_merge($base,array('your-phone'=>'')))['status'],'phone is optional');
    $check('mail_sent'===$submit(array_merge($base,array('first-name'=>'محمد','last-name'=>'أحمد')))['status'],'Unicode names are supported');
    $mail_result=false;$result=$submit($base);
    $check('mail_failed'===$result['status'],'mail failure returns CF7 failure instead of success');$mail_result=true;
    $_POST=array();$_SERVER['REQUEST_METHOD']='GET';$singleton->setValue(null,null);
    $job=wp_insert_post(array('post_type'=>'rp_job','post_status'=>'publish','post_title'=>'Packaging & Quality Specialist'));
    $_GET['job']=$job;$xpath=$parse($render());
    $check(rockpack_job_enquiry_message()===$xpath->query('//textarea[@name="your-message"]')->item(0)->textContent&&str_contains(rockpack_job_enquiry_message(),'Packaging'),'open career enquiry prefills the CF7 message safely');
    update_post_meta($job,'_rp_job_status','closed');$xpath=$parse($render());
    $check(''===$xpath->query('//textarea[@name="your-message"]')->item(0)->textContent,'closed job does not prefill a public enquiry');
    $_GET=array();
    $settings=get_option('rockpack_settings');$settings['contact_page_form_id']='0';update_option('rockpack_settings',$settings);
    $fallback=$render();$check(str_contains($fallback,'temporarily unavailable')&&!str_contains($fallback,'[contact-form-7'),'missing form shows contact guidance instead of a broken shortcode');
    $settings['contact_page_form_id']=(string)$id;update_option('rockpack_settings',$settings);
    wp_set_current_user($admins[0]);
    $form=WPCF7_ContactForm::get_instance($id);$edited=$form->get_properties();$edited['messages']['mail_sent_ok']='Edited success message';$form->set_properties($edited);$form->save();
    $check($id===rockpack_setup_contact_form7()&&'Edited success message'===WPCF7_ContactForm::get_instance($id)->prop('messages')['mail_sent_ok'],'setup preserves later Contact Form 7 edits');
    $fields=array_column(rockpack_contact_page_fields(),'key');
    $check(in_array('contact_page_form_id',$fields,true)&&!in_array('contact_page_recipient',$fields,true)&&!in_array('contact_page_first_name',$fields,true),'page editor offers form selection and removes obsolete field controls');
    $sanitized=rp_sanitize_settings(array('contact_page_form_id'=>(string)$id));
    $check((string)$id===$sanitized['contact_page_form_id'],'legacy settings accept an available CF7 form');
    $sanitized=rp_sanitize_settings(array('contact_page_form_id'=>'invalid-form'));
    $check((string)$id===$sanitized['contact_page_form_id'],'invalid form selection cannot replace the configured form');
}catch(Throwable $error){$failure=$error->getMessage();}
finally{
    $singleton->setValue(null,null);$wpdb->query('ROLLBACK');wp_cache_flush();$_POST=$_GET=array();
    remove_filter('pre_wp_mail',$intercept,PHP_INT_MAX);unset($GLOBALS['rockpack_refresh_content']);
}
echo $checks.' checks passed; '.count($mail).' intercepted mail calls; zero emails sent. Fixture changes rolled back.'.PHP_EOL;
if($failure){fwrite(STDERR,$failure.PHP_EOL);exit(1);}
