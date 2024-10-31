<?php
/**
 * Plugin Name: MYFUNDBOX - Recurring payments for Donation Form
 * Plugin URI: http://www.myfundbox.com/
 * Description: This plugin will embed MYFUNDBOX Donation Form to your site for the multiple Projects with the simple shortcode for each project. The settings is very simple. No embed code needed. We will generate the embed code for you!
 * Version: 1.0
 * Author: MYFUNDBOX
 * Author URI: http://www.myfundbox.com
 * Text Domain: mfb_donations
 * Domain Path: /languages
 */
 
function myfundbox_load_plugin_textdomain() {
    load_plugin_textdomain( 'mfb_donations', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'myfundbox_load_plugin_textdomain' );

function myfundbox_register_settings() {
    add_menu_page(__('MYFUNDBOX cause Settings', 'mfb_donations'), 'MYFUNDBOX Donation Form', 'manage_options', 'myfundbox_cause_settings', 'myfundbox_settings_fn', '', 22); 
    add_submenu_page('myfundbox_cause_settings', 'Setting', 'Setting', 'manage_options', 'myfundbox_cause_settings' );
    $myfundbox_cpt_link = 'edit.php?post_type=project';
    add_submenu_page('myfundbox_cause_settings', 'MYFUNDBOX Projects', 'MYFUNDBOX Projects', 'manage_options', $myfundbox_cpt_link);
    remove_menu_page( 'edit.php?post_type=project' );
}
add_action('admin_menu', 'myfundbox_register_settings');

function myfundbox_set_custom_sc_columns($columns) {
    $columns['shortcodeforp'] = __( 'Shortcode', 'your_text_domain' );
    $columns['webhookurl'] = __( 'Web Hook URL', 'your_text_domain' );
    return $columns;
}
add_filter( 'manage_project_posts_columns', 'myfundbox_set_custom_sc_columns' );

function myfundbox_custom_sc_column( $column, $post_id ) {
    switch ( $column ) {
        case 'shortcodeforp' :
            echo "[MYFUNDBOX_cause_status post_id='".$post_id."']";
            break;
        case 'webhookurl' :
             $get_all_meta_values = get_post_custom($post_id);
             if($get_all_meta_values)
             {
                $web_hook=$get_all_meta_values["web_hook"][0];    
             }
            echo $web_hook;
            break;
        default:
            break;
    }
}
add_action( 'manage_project_posts_custom_column' , 'myfundbox_custom_sc_column', 10, 2 );

function myfundbox_cpt() {
	register_post_type( 'project', array(
		'labels' => array(
		'name' => 'Projects',
		'singular_name' => 'Project',
	),
	'public' => false,
	'show_ui' => true,
	'supports' => array( 'title', 'editor' )
	));
}
add_action( 'init', 'myfundbox_cpt' );

function myfundbox_add_my_web_hook() {
	add_meta_box('my_fav_team_id', 'Web Hook', 'myfundbox_web_hook', 'project', 'normal', 'high');
	add_meta_box('my_project_causes_id', 'Project ID', 'myfundbox_project_causes', 'causes', 'normal', 'high');
}
function myfundbox_project_causes() {
    global $post;
    $get_all_meta_values = get_post_custom($post->ID);
    $project_id='';
    $cause_id='';
    if(isset($get_all_meta_values["project_s_id"][0]))
    {
        $project_id=$get_all_meta_values["project_s_id"][0];
    }
    if(isset($get_all_meta_values["s_project_id"][0]))
    {
        $cause_id=$get_all_meta_values["s_project_id"][0];
    }
    echo '<label>Project:</label>
    <input type="text" name="project_s_id" size=100  value=" '.$project_id.'" disabled />';
    echo '<br/><label>Cause:</label>
    <input type="text" name="s_project_id" size=100  value=" '.$cause_id.'" disabled />';   
}
add_action('admin_init', 'myfundbox_add_my_web_hook' );

function myfundbox_web_hook() {
   global $post;
   $web_hook='';
   $get_all_meta_values = get_post_custom($post->ID);
   if($get_all_meta_values)
   {
    $web_hook=$get_all_meta_values["web_hook"][0];    
   }
   echo '
        <label>Web Hook:</label>
        <input type="text" name="web_hook" size=100  value="'.$web_hook.'" />';
    if(!empty($web_hook))
    {
        $get_all_meta_values = get_post_custom($post->ID);
        $webhook=$get_all_meta_values["web_hook"][0];
        $stream_opts = [
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ]
        ];
        $filen=json_decode(file_get_contents($webhook,false, stream_context_create($stream_opts)));
        $i=1;
        if(!empty($filen->prodList))
        {
            echo "<table>";
			foreach($filen->prodList as $value) {
				//echo "<input type='file' name='".$value->{'ProjProdId'}."' id='".$value->{'ProjProdId'}."'>";
				echo "<tr>";
				echo "<td><label>".$value->{'Product Name'}."</label></td>";
				$meta_key = $value->{'ProjProdId'};
				echo "<td>".myfundbox_image_uploader_field( $meta_key, get_post_meta($post->ID, $meta_key, true) );
				echo "</td></tr>";
			}
			echo "</table>";
        }
        else
        {
            echo "<br/>Please check your web hook URL";
        }
    }
}

function myfundbox_image_uploader_field( $name, $value = '') {
    $image = ' button">Upload image';
    $image_size = 'full'; // it would be better to use thumbnail size here (150x150 or so)
    $display = 'none'; // display state ot the "Remove image" button
    if( $image_attributes = wp_get_attachment_image_src( $value, $image_size ) ) {
        $image = '"><img src="' . $image_attributes[0] . '" style="max-width:95%;display:block;width:100px;" />';
        $display = 'inline-block';
    } 
    return '
    <div>
        <a href="#" class="myfundbox_upload_image_button ' . $image . '</a>
        <input type="hidden" name="' . $name . '" id="' . $name . '" value="' . $value . '" />
        <a href="#" class="myfundbox_remove_image_button" style="display:'.$display.';float: right;margin-top: -21px;margin-left: 119px;">Remove image</a>
    </div>';
}

function myfundbox_include_myuploadscript() {
    if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
    wp_enqueue_script( 'myuploadscript', plugin_dir_url(__FILE__)  . 'js/myfundbox-admin.js', array('jquery'), null, false );
}
add_action( 'admin_enqueue_scripts', 'myfundbox_include_myuploadscript' );

function myfundbox_save_my_web_hook($post_ID) {
	if ( get_post_type($post_ID) != 'project' ) 
	{
		return;
	}
	global $post;
	
	if(isset($_POST["web_hook"]))
	{
		$web_hooks=sanitize_text_field($_POST["web_hook"]);
		update_post_meta($post->ID, "web_hook", $web_hooks);
		$p_id=$post->ID;
	
		$get_all_meta_values = get_post_custom($post->ID);
		$webhook=$get_all_meta_values["web_hook"][0];
		
		$stream_opts = [
			"ssl" => [
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			]
		];  
		
		$filen=json_decode(file_get_contents($webhook,false, stream_context_create($stream_opts)));
	
		$mycauses = get_posts( array(
			'post_type' => 'causes',
			'meta_key' => 'project_s_id',
			'meta_value' => $p_id
			)
		);
		foreach ( $mycauses as $mycause ) {
			// Delete all products.
			wp_delete_post( $mycause->ID, true); // Set to False if you want to send them to Trash.
		} 
	
		foreach($filen->prodList as $value) {
			$ProjProdId = sanitize_text_field($_POST[$value->{'ProjProdId'}]);
			update_post_meta( $post_ID, $value->{'ProjProdId'}, $ProjProdId );
			$my_post = array(
				'post_title'    => $value->{'Product Name'},
				'post_type' => 'causes',
				'post_status'   => 'publish',
				'meta_input' => array(
				   'project_s_id' => $p_id,
				   's_project_id' =>$value->{'ProjProdId'}				   
				)
			);
			$post_id = wp_insert_post( $my_post );
		}
	}
}
add_action('save_post', 'myfundbox_save_my_web_hook',1,1);

function myfundbox_name_scripts() {
    wp_enqueue_style( 'myfundbox_bootstrapcss', plugin_dir_url(__FILE__) . 'bootstrap/bootstrap.min.css',false,NULL,'all'); 
    wp_enqueue_style( 'myfunbox_style', plugin_dir_url(__FILE__) . 'css/myfunbox_style.css',false,NULL,'all'); 
    wp_enqueue_script( 'myfundbox_bootstrapjs', plugin_dir_url(__FILE__) . 'bootstrap/bootstrap.min.js', array(), NULL, true );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'myfundbox_custom', plugin_dir_url(__FILE__) . 'js/custom.js', array(), NULL, true );
	wp_enqueue_style( 'myfundbox');
	wp_register_script('myfundbox_ajax',plugin_dir_url(__FILE__) . 'js/customajax.js',array(),false,true);
    wp_localize_script('myfundbox_ajax','ajax_object',array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ));
	wp_enqueue_script( 'myfundbox_ajax' );
}
add_action( 'wp_enqueue_scripts', 'myfundbox_name_scripts' );

function myfundbox_admin_scripts() {
	
    if(isset($_GET["page"])) {
		$getpage = sanitize_text_field($_GET["page"]);
		if($getpage == "myfundbox_cause_settings")
		{
			//wp_enqueue_script( 'angular', plugin_dir_url(__FILE__) . 'js/angular.min.js', array(), NULL, false );
			wp_enqueue_script( 'custom', plugin_dir_url(__FILE__) . 'js/custom.js', array(), NULL, true );
			wp_enqueue_style( 'bootstrapcss', plugin_dir_url(__FILE__) . 'bootstrap/bootstrap.min.css',false,NULL,'all');     
		}
	}        
}
add_action( 'admin_enqueue_scripts', 'myfundbox_admin_scripts' );

function myfundbox_settings_fn() {
    if (isset($_REQUEST['submit'])) {
        if (isset($_REQUEST['hsize'])) {
			$hsize=sanitize_text_field($_REQUEST['hsize']);
            update_option('hsize',$hsize );
        } else {
            $hsize = esc_attr(get_option('hsize'));
        }
        if (isset($_REQUEST['curSign'])) {
			$curSign=sanitize_text_field($_REQUEST['curSign']);
            update_option('curSign', $curSign);            
        } else {
            $curSign = esc_attr(get_option('curSign'));
        }
        if (isset($_REQUEST['pColor'])) {
			$pColor=sanitize_hex_color($_REQUEST['pColor']);
            update_option('pColor', $pColor);
        } else {
            $pColor = esc_attr(get_option('pColor'));
        }
        if (isset($_REQUEST['tsize'])) {
			$tsize=sanitize_text_field($_REQUEST['tsize']);
            update_option('tsize', $tsize);
        } else {
            $tsize = esc_attr(get_option('tsize'));
        }
        if (isset($_REQUEST['tcolor'])) {
            $tcolor=sanitize_hex_color($_REQUEST['tcolor']);
			update_option('tcolor', $tcolor);
        } else {
            $tcolor = esc_attr(get_option('tcolor'));
        }
        if (isset($_REQUEST['donateUrl'])) {
			$donateUrl=esc_url($_REQUEST['donateUrl']);
            update_option('donateUrl', $donateUrl);
        } else {
            $donateUrl = esc_attr(get_option('donateUrl'));
        }
        if (isset($_REQUEST['btn_txt'])) {
			$btn_txt=sanitize_text_field($_REQUEST['btn_txt']);
            update_option('btn_txt', $btn_txt);
        } else {
            $btn_txt = esc_attr(get_option('btn_txt'));
        }
        if (isset($_REQUEST['interval'])) {
			$interval=sanitize_text_field($_REQUEST['interval']);
            update_option('interval', $interval);
        } else {
            $interval = esc_attr(get_option('interval'));
        }
        if ($pColor == '')
            $pColor = "#28a745";
        if ($hsize == '')
            $hsize = "28px";
        if ($tsize == '')
            $tsize = "14px";
        if ($tcolor == '')
            $tcolor = "#000";
        if ($interval == '')
            $interval = "60000";
    } else {
        $hsize     = esc_attr(get_option('hsize'));
        $curSign   = esc_attr(get_option('curSign'));
        $pColor    = esc_attr(get_option('pColor'));
        $tsize     = esc_attr(get_option('tsize'));
        $tcolor    = esc_attr(get_option('tcolor'));
        $donateUrl = esc_attr(get_option('donateUrl'));
        $interval  = esc_attr(get_option('interval'));
        
        $btn_txt = esc_attr(get_option('btn_txt'));
	}
	?>
	<div class="container pt-3">
		<div>
		    <form method="post" class="row mt-3">
				<div class="col-sm-6">
					<h2 class="text-center">Save webhook at below</h2>
					<div class="form-group">
						<label for="curSign">Currency Sign</label>
						<select name="curSign" id="curSign" class="form-control">
							<option value="€" <?php	if ($curSign == '€') echo 'selected';?>>€</option>
							<option value="$" <?php	if ($curSign == '$') echo 'selected';?>>$</option>
						</select>
					</div>
					<div class="form-group">
						<label for="interval">Interval</label>
						<select name="interval" id="interval" class="form-control">
							<option value="30000" 	<?php if ($interval == '30000') 	echo 'selected';?>>30 sec</option>
							<option value="60000" 	<?php if ($interval == '60000') 	echo 'selected';?>>60 sec</option>
							<option value="90000" 	<?php if ($interval == '90000') 	echo 'selected';?>>90 sec</option>
							<option value="120000" 	<?php if ($interval == '120000')	echo 'selected';?>>120 sec</option>
						</select>
					</div>
					<div class="form-group">
						<label for="btn_txt">Button Text</label>
						<input type="text" id="btn_txt" name="btn_txt" class="form-control" value="<?php echo $btn_txt; ?>">
						
					</div>
					<div class="form-group" style="display:none">
						<label for="donateUrl">Donation URL</label>
						<input type="text" id="donateUrl" name="donateUrl" class="form-control" value="<?php echo $donateUrl;?>">
					</div>
				</div>
				<div class="col-sm-6">
					<h3>Styles</h3>
					<div class="form-group">
						<label for="pColor">Progress Color</label>
						<input type="text" id="pColor" name="pColor" class="form-control" value="<?php echo $pColor; ?>">
					</div>
					<div class="form-group">
						<label for="">Heading Size</label>
						<input type="text" name="hsize" class="form-control" value="<?php echo $hsize; ?>">
					</div>
					<div class="form-group">
							<label for="">Text Size	</label>
							<input type="text" name="tsize" class="form-control" value="<?php echo $tsize; ?>">
					</div>
					<div class="form-group">
							<label for="">Text Color </label>
							<input type="text" name="tcolor" class="form-control" value="<?php echo $tcolor; ?>">
					</div>
					<div class="form-group">
							<?php submit_button(); ?>
					</div>
				</div>
			</form>
		</div>
	</div>
<?php
}
/* ajax function code start*/
add_action( "wp_ajax_myfundbox_ajax_shortcode", "myfundbox_ajax_shortcode" );
add_action( "wp_ajax_nopriv_myfundbox_ajax_shortcode", "myfundbox_ajax_shortcode" );
function myfundbox_ajax_shortcode(){
    $out = "";
	$npostid = sanitize_text_field($_POST['npostid']);
	if(isset($npostid))
    {
	   $out = myfundbox_get_data_for_shortcode($npostid);    
    }
	echo $out;
	exit(); 
}
/* ajax function code end*/
function myfundbox_get_data_for_shortcode($npost_id)
{
    $out = "";
	$default_img = plugin_dir_url(__FILE__) . '/demo-placehold.jpg';
	$post_id 	= 	$npost_id;
    $webhook 	= 	get_post_meta($post_id,'web_hook');
    
	$filec		=	json_decode(file_get_contents($webhook[0]));
	if(empty($webhook) || empty($filec->prodList)){
		$out .= '<div class="myfundbox_wrapper admin_view" class="grid-container">
					<div class="no-causes text-center text-danger">
						<strong> Please check your Web Hook URL</strong>
					</div>
				</div>';
		return $out;
	}
	
	$pColor    = esc_attr(get_option('pColor'));
    $hsize     = esc_attr(get_option('hsize'));
    $tsize     = esc_attr(get_option('tsize'));
    $tcolor    = esc_attr(get_option('tcolor'));
    
	$out .= '<style>';
	
	if ($pColor != '') {
		$out .= '
			.myfundboxContainer progress::-webkit-progress-value {
			background:'. $pColor .'!important;
		  }
		';
	}
	if ($hsize != '') {
		$out .= '
			.myfundboxContainer h2 {
				font-size: ' . $hsize . '!important;
		  }
		';
	}
	if ($tsize != '') {
		$out .= '
		  .myfundboxContainer p {
			font-size: ' . $tsize . '!important;
		  }	';
	}
	
	if ($tcolor != '') {
	    $out .= '
	      .myfundboxContainer p,
		  .myfundboxContainer h2 {
			color: '.  $tcolor . ' !important;
		}
		';
	}
	
	$out .= '</style>';
    
    $curSign   = esc_attr(get_option('curSign'));
    $donateUrl = esc_attr(get_option('donateUrl'));
    $interval  = esc_attr(get_option('interval'));
    $btn_txt  = esc_attr(get_option('btn_txt'));
	
    if ($interval == '')
        $interval = 60000;

    $currencySign  = $curSign;


	$out .= '<div class="myfundbox_wrapper admin_view" class="grid-container">';
	
	
	/* Using PHP */ 
		
		$out .= '<div class="row">';
      
		foreach($filec->prodList as $value)	{
			
			$project_name = esc_attr($value->{'Product Name'});
			
			$target_amount = esc_attr($value->{'TargetAmount'});
			
			$collected_amount = esc_attr($value->{'CollectedAmount'});
			
			$project_link = esc_attr($value->{'DBUrl'});
			
			if($target_amount == 0) {
				$target_amount = $collected_amount;
			}
			
			$percentage_collected =  myfundbox_get_percentage($target_amount,$collected_amount);
		
            $image_size ="full";
           
			//$image_attributes = wp_get_attachment_image_src( $value->{'ProjProdId'}, $image_size );
            $v= get_post_meta($npost_id, $value->{'ProjProdId'}, true);
            $image_attributes = wp_get_attachment_image_src( $v, $image_size );    
            if($image_attributes){
                $project_image=esc_attr($image_attributes[0]);
            }
            else{
                $project_image = esc_attr($default_img);
            }
            
         
			$out .= '<div class="grid-item col-sm-4 causes-wrapper">
				<div  class="inner-item">
					<div class="inner-img-wrap">
						<img src="'.$project_image.'" height="300" width="320" class="image-src" >
					</div>
					<div class="wrapper-item myfundboxContainer">
						<h2 class="text-left">'.$project_name.'</h2>
						<div id="innterContent">';
												
							$out .= '<div class="percentage_val">'. $percentage_collected.'%</div>
							
							<progress value="'. $percentage_collected.'" max="100">'.
								$percentage_collected.'%
							</progress> 
							<p class="raised text-left">';
								if ($curSign == '€') {
									//$out .= '<strong>'.$curSign . $collected_amount.' raised </strong> of '.$curSign.$target_amount;
									$out .= '<strong>'. $collected_amount. $curSign.' raised </strong> of '.$target_amount.$curSign;
								} else {                                             
									$out .= '<strong>'.$curSign . $collected_amount .' raised </strong> of '.$curSign.$target_amount; 
								}
							$out .= ' </p>
						</div>
						<div class="popup-wrap">
							<a href="'.$project_link.'" target="_blank" class="btn btn-primary btn_pop_up" data-toggle="modal" data-target="#myModal"';
								if($percentage_collected >= 100){
									$out .= "disable";
								}
								
								$out .= '> '.$btn_txt.'
							</a>
						</div>
					</div>	
				</div>
			</div>';
		}
		$out .= '</div>';
		$out.='<input type="hidden" id="npostid" value="'.$npost_id.'">';
		
		$out.='<input type="hidden" id="nmyinterval" value="'.$interval.'">';
		$out.='<!--  Html For popUP-->
				<div class="modal fade" id="myModal" role="dialog">
					<div class="modal-dialog">
						<!-- Modal content-->
						<div class="modal-content">
							<div class="modal-body">
								<iframe id="modal_frm_btn" src=""></iframe>
							</div>
						</div>
					</div>
				</div>
			<!--  Html For popUP-->';
		$out .= '</div>';
  
	return $out;
}
add_shortcode('MYFUNDBOX_cause_status', 'myfundbox_shortcode_fn');
function myfundbox_shortcode_fn($atts){
    $out= "<div id='mainwrapperfundbox'>";
    $out.= myfundbox_get_data_for_shortcode($atts['post_id']);
    $out.= "</div>";
    return $out;
}
function myfundbox_get_percentage($total, $number) {
  if ( $total > 0 ) {
   return round($number / ($total / 100),2);
  } else {
    return 0;
  }
}
function myfundbox_post_type_news() {
    $supports = array(
        'title', // post title
        'editor', // post content
        'author', // post author
        'thumbnail', // featured images
        'excerpt', // post excerpt
        'custom-fields', // custom fields
        'comments', // post comments
        'revisions', // post revisions
        'post-formats' // post formats
    );
    $labels   = array(
        'name' => _x('Causes', 'plural'),
        'singular_name' => _x('Causes', 'singular'),
        'menu_name' => _x('causes', 'admin menu'),
        'name_admin_bar' => _x('causes', 'admin bar'),
        'add_new' => _x('Add New', 'add new'),
        'add_new_item' => __('Add New causes'),
        'new_item' => __('New causes'),
        'edit_item' => __('Edit causes'),
        'view_item' => __('View causes'),
        'all_items' => __('All causes'),
        'search_items' => __('Search causes'),
        'not_found' => __('No causes found.')
    );
    $args     = array(
        'supports' => $supports,
        'labels' => $labels,
        'public' => true,
        'query_var' => true,
        'rewrite' => array(
            'slug' => 'causes'
        ),
        'has_archive' => true,
        'hierarchical' => false
    );
  //  register_post_type('causes', $args);
}
add_action('init', 'myfundbox_post_type_news');