<?php
	/*
	Plugin Name: Simplegram
	Plugin URI: http://tba.tba
	Description: Plugin for displaying Instagram account.
	Author: J. Liu
	Version: 1.0
	Author URI: http://hiimjl.com
	*/

	function simplegram_options_page() {

	echo '<div class="wrap">';
	echo '<h2>Simplegram</h2>';
	echo 'Options relating to the custom plugin.';
	echo '<form action ="options.php" method="post">';
	settings_fields('simplegram_options');
	do_settings_sections('simplegram');
	echo '<input name="Submit" type="submit" class="button button-primary" value="Save Changes" />';
	echo '</form>';
	echo '</div>';

	}

	function simplegram_activate() {
		add_option('simplegram_token', null, false, false);
		add_option('simplegram_user', null, false, false);
		add_option('simplegram_id', null, false, false);
	}
	register_activation_hook(__FILE__, 'simplegram_activate');

	function simplegram_deactivate() {
		delete_option('simplegram_token');
		delete_option('simplegram_user');
		delete_option('simplegram_id');
	}
	register_deactivation_hook(__FILE__, 'simplegram_deactivate');

	function simplegram_admin_actions() {
		add_options_page("Simplegram Configuration", "Simplegram Configuration", 'manage_options', "simplegram", "simplegram_options_page");
	}
	add_action('admin_menu', 'simplegram_admin_actions');

	add_action('admin_init', 'simplegram_admin_init');
	function simplegram_admin_init() {
		register_setting('simplegram_options', 'simplegram_options', 'simplegram_options_validate');
		add_settings_section('simplegram_main', 'Main Settings', 'simplegram_section_text', 'simplegram');
		add_settings_field('simplegram_client_id', 'Simplegram Client ID', 'simplegram_client_id_string', 'simplegram', 'simplegram_main');
		add_settings_field('simeplgram_client_secret', 'Simplegram Client Secret', 'simplegram_client_secret_string', 'simplegram', 'simplegram_main');
		add_settings_field('simeplgram_redirect_uri', 'Simplegram Redirect URI', 'simplegram_redirect_string', 'simplegram', 'simplegram_main');
	}

	function simplegram_section_text() {
		echo '<p>Please fill in the following information:</p>';
		$options = get_option('simplegram_options');
		
		if ($_GET['code'] && !get_option('simplegram_token')) {
				simplegram_authorize($_GET['code']);
		}

		if ($options['client_id'] && $options['client_secret'] && $options['redirect_uri']) {
			echo '<p>Authorize the plugin to access your Instagram account.</p>';
			if (!get_option('simplegram_token')) {
			echo '<a class="button button-primary authorize" href="https://api.instagram.com/oauth/authorize/?client_id=' . $options['client_id'] . '&redirect_uri=' . $options['redirect_uri'] . '&response_type=code">Authorize</a>';
			}

			if (get_option('simplegram_token')) {
				echo '<h1>Access token obtained.</h1>';

				echo '<p><button class="button" disabled="disabled" href="#">Authorized</button></p>';

				echo '<a href="#ajaxthing" class="deauthorize button button-primary">Deauthorize</a>';				
			}
		}		
	}

	function simplegram_authorize($code) {
		$options = get_option('simplegram_options');
		$response = wp_remote_post('https://api.instagram.com/oauth/access_token',
			array(
				'body' => array(
					'client_id' => $options['client_id'],
					'client_secret' => $options['client_secret'],
					'grant_type' => 'authorization_code',
					'redirect_uri' => $options['redirect_uri'],
					'code' => $code,
				)
			)
		);
		if ($response['response']['code'] == 200){
			$response_body = json_decode($response['body']);
			update_option('simplegram_token', $response_body->access_token);
			update_option('simplegram_user', $response_body->user->username);
			update_option('simplegram_id', $response_body->user->id);
		}
	}

	function simplegram_client_id_string() {
		$options = get_option('simplegram_options');
		echo "<p><input id='simplegram_client_id' name='simplegram_options[client_id]' size='40' type='text' value='{$options['client_id']}' /></p>";
	}

	function simplegram_client_secret_string() {
		$options = get_option('simplegram_options');
		echo "<p><input id='simplegram_client_secret' name='simplegram_options[client_secret]' size='40' type='text' value='{$options['client_secret']}' /></p>";
	}


	function simplegram_redirect_string() {
		$options = get_option('simplegram_options');
		echo "<p><input id='simplegram_redirect_uri' name='simplegram_options[redirect_uri]' size='40' type='text' value='{$options['redirect_uri']}' /></p>";
	}

	function simplegram_options_validate($input) {
		$options = get_option('simplegram_options');
		$options['client_id'] = trim($input['client_id']);
		$options['client_secret'] = trim($input['client_secret']);
		$options['redirect_uri'] = trim($input['redirect_uri']);
		return $options;
	}

class Simplegram_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'simplegram_widget',
			__( 'Simplegram Widget' ),
			array( 'description' => __( 'Display photos from your Instagram feed.') ) 
		);
	}

	function form( $instance ) {
		if ( $instance ) {
			$title = esc_attr( $instance['title'] );
			$rows  = esc_attr( $instance['rows'] );
			$cols  = esc_attr( $instance['cols'] );
		} else {
			$title = __( 'Instagram Feed' );
			$rows  = 3;
			$cols  = 3;
		}

		echo '<p>';
		echo '<label for="' . $this->get_field_id('title') . '">' . _e( "Title:" ) . '</label>';
		echo '<input class="widefat" id="'. $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . $title . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id('rows') . '">' . _e( "Rows:" ) . '</label>';
		echo '<input class="widefat" id="'. $this->get_field_id( 'rows' ) . '" name="' . $this->get_field_name( 'rows' ) . '" type="text" value="' . $rows . '" />';
		echo '</p>';

		echo '<p>';
		echo '<label for="' . $this->get_field_id('cols') . '">' . _e( "Columns:" ) . '</label>';
		echo '<input class="widefat" id="'. $this->get_field_id( 'cols' ) . '" name="' . $this->get_field_name( 'cols' ) . '" type="text" value="' . $cols . '" />';
		echo '</p>';
	}

	function update( $new_instance, $instance ) {
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['rows'] = strip_tags( $new_instance['rows'] );
		$instance['cols'] = strip_tags( $new_instance['cols'] );
		return $instance;
	}

	function get_instagrams($instance) {
		$token = get_option('simplegram_token');
		$id = get_option('simplegram_id');
		$url = 'https://api.instagram.com/v1/users/3/media/recent/';
		$total = $instance['rows'] * $instance['cols'];
		$response = wp_remote_get('https://api.instagram.com/v1/users/' .  $id . '/media/recent?access_token=' .$token . '&count=' . $total);
		if($response['response']['code'] == 200) {
			$result = json_decode($response['body']);	
		}

		$html_output = '<div class="simplegram">'; 

		$main_data = array();
		$n = 0;
		foreach ( $result->data as $d ) {
        	$main_data[ $n ]['user']      = $d->user->username;
        	$main_data[ $n ]['thumbnail'] = $d->images->thumbnail->url;
        	$main_data[ $n ]['link']	  = $d->link;
        	$n++;
    	}
    	$spans = 12 / $instance['cols'];
    	for($i=0; $i < $instance['rows']; $i++) {
    		$html_output .= '<div class="row">';
    		for($j = 0; $j <$instance['cols']; $j++) {
	    	//foreach ( $main_data as $data ) {
	        //	$html_output .= '<a target="_blank" href="http://instagram.com/'.$data['user'].'"><img src="'.$data['thumbnail'].'" alt="'.$data['user'].' pictures"></a> ';
    		if ($i > 0) {
    			$shift = $j + $instance['cols'];
    		} else {
    			$shift = $j;
    		}

    		$html_output .= '<div class="col-sm-' . $spans . '"><a target="_blank" href="'.$main_data[$shift]['link'].'"><img src="'.$main_data[$shift]['thumbnail'].'" alt="'.$main_data[$shift]['user'].' pictures"></a><div class="mask"></div></div>';	
	    	}				
	    	$html_output .= '</div>';
    	}

    	$html_output .= "</div>";
		//return $result;		
		return $html_output;
	}

	function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'];
			echo esc_html( $instance['title'] );
			echo $args['after_title'];
		}
		$transient_key = $this->id;
		$html_output = get_transient( $transient_key );
		
		if (empty($html_output)) {
			$output = $this->get_instagrams($instance);
			set_transient( $transient_key, $output, 60*5 );			
			echo $output;
		} else {
			echo $html_output;
		}

		echo $args['after_widget'];
	}	
}

function simplegram_register_widgets() {
	register_widget( 'Simplegram_Widget' );
}

function simplegram_styles() {
	wp_register_style( 'simplegram_styles', plugins_url('simplegram.css', __FILE__));
	wp_enqueue_style('simplegram_styles');
}

add_action( 'widgets_init', 'simplegram_register_widgets');
add_action( 'widgets_init', 'simplegram_styles');

add_action('admin_footer', 'simplegram_javascript');

function simplegram_javascript() {
	?>
<script type="text/javascript">
jQuery(document).ready(function($) {

	$('.deauthorize').click(function() {
		var data = {
			action: 'my_action',
			auth: 'deauthorize'
		};

		$.post(ajaxurl, data, function(response) {
			var loc = window.location;
			var i = loc.href.indexOf('?');
			loc.href = loc.href.substring(0, i) + '?page=simplegram';

		});
	});

	$('.authorize').click(function() {
		var data = {
			action: 'my_action',
			auth: 'authorize'
		};

		$.post(ajaxurl, data, function(response) {
			location.reload();
		});
	});
});
</script>
<?php
}

add_action('wp_ajax_my_action', 'my_action_callback');

function my_action_callback() {
	global $wpdb;

	$whatever = intval($_POST['auth']);

	if($whatever == 'deauthorize') {
		update_option('simplegram_token', null, false, false);
		update_option('simplegram_user', null, false, false);
		update_option('simplegram_id', null, false, false);	
	}
	exit();
}