<?php
/**
 * Plugin Name: WP Bouncer - Limit Simultaneous Logins
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/wp-bouncer/
 * Description: Deter members from sharing login credentials: restrict simultaneous logins for the same user.
 * Version: 1.5.1
 * Author: Stranger Studios
 * Author URI: https://www.strangerstudios.com
 * Text Domain: wp-bouncer
 * Domain Path: /languages
 */

define( 'WP_BOUNCER_VERSION', '1.5.1' );

// Start up the engine
class WP_Bouncer {
	/**
	 * This is our constructor
	 *
	 * @return WP_Bouncer
	 */
	public function __construct() {
		//support translations
		add_action( 'plugins_loaded', array( $this, 'textdomain' ) );

		//track logins
		add_action( 'wp_login', array( $this, 'login_track' ) );

		//bounce logins
		add_action( 'init', array( $this, 'login_flag' ), 10, 0 );

		//add action links to reset sessions
		add_filter( 'user_row_actions', array($this, 'user_row_actions' ), 10, 2 );
		
		//add check for resetting sessions
		add_action( 'admin_init', array( $this, 'reset_session' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		//JS checks
		add_action( 'wp_ajax_wp_bouncer_check', array( $this, 'ajax_check' ) );
		add_action( 'wp_ajax_nopriv_wp_bouncer_check', array( $this, 'ajax_check' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

		// Show error message on login.
		add_action( 'login_head', array( $this, 'user_bounced_error' ) );
	}
	
	public function wp_enqueue_scripts() {
		// Check for WP_BOUNCER_HEARTBEAT_CHECK constant first
		if ( defined( 'WP_BOUNCER_HEARTBEAT_CHECK' ) 
			&& WP_BOUNCER_HEARTBEAT_CHECK == true 
			&& is_user_logged_in() ) {
			wp_enqueue_script(
				'wp_bouncer', 
				plugins_url( 'js/wp-bouncer.js', __FILE__ ),
				array( 'jquery' ),
				WP_BOUNCER_VERSION
			);
			wp_localize_script(
				'wp_bouncer',
				'wp_bouncer',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'wp_bouncer_ajax_timeout' => apply_filters( 'wp_bouncer_ajax_timeout', 5000 )
				)
			);
		}
	}

	/**
	 * Support translations and tie into GlotPress
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wp-bouncer', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Get the URL to redirect dupe logins to
	 */
	public function get_redirect_url() {
		return apply_filters('wp_bouncer_redirect_url', esc_url( add_query_arg( 'bounced', '1', wp_login_url() ) ) );
	}

	/**
	 * Show error message on login page if bounced.
	 * @since 1.5
	 */
	public function user_bounced_error() {
		global $error;

		if( isset( $_REQUEST['bounced'] ) && '1' == $_REQUEST['bounced'] ) {
			$error  = esc_html__( 'There was an issue with your log in. Your user account has logged in recently from a different location.', 'wp-bouncer' );
		}
  
	}
	
	/**
	 * helper function to get browser data at login
	 *
	 * @return WP_Bouncer
	 */
	private function browser_data() {
		// grab base user agent and parse out
	    $u_agent	= $_SERVER['HTTP_USER_AGENT'];
	    $bname		= 'Unknown';
	    $platform	= 'Unknown';
	    $version	= '';
	    $ub			= '';
		
	    // determine platform
	    if (preg_match('/linux/i', $u_agent))
	        $platform = 'linux';

	    if (preg_match('/macintosh|mac os x/i', $u_agent))
	        $platform = 'mac';

	    if (preg_match('/windows|win32/i', $u_agent))
	        $platform = 'windows';


	    // get browser info
	    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
	        $bname	= 'Internet Explorer';
	        $ub		= 'MSIE';
	    }

	    if(preg_match('/Firefox/i',$u_agent)) {
	        $bname	= 'Mozilla Firefox';
	        $ub		= 'Firefox';
	    }

	    if(preg_match('/Chrome/i',$u_agent)) {
	        $bname	= 'Google Chrome';
	        $ub		= 'Chrome';
	    }

		if(preg_match('/Safari/i',$u_agent) && !preg_match('/Chrome/i',$u_agent)) {
	        $bname	= 'Apple Safari';
	        $ub		= 'Safari';
	    }

	    if(preg_match('/Opera/i',$u_agent)) {
	        $bname	= 'Opera';
	        $ub		= 'Opera';
	    }

	    if(preg_match('/Netscape/i',$u_agent)) {
	        $bname	= 'Netscape';
	        $ub		= 'Netscape';
	    }

	    // finally get the correct version number
	    $known		= array('Version', $ub, 'other');
	    $pattern	= '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';

	    if (!preg_match_all($pattern, $u_agent, $matches)) {
	        // we have no matching number just continue
	    }

	    // see how many we have
	    $i = count( $matches['browser'] );
	    if ($i != 1) {
	        //we will have two since we are not using 'other' argument yet
	        //see if version is before or after the name
	        if (strripos( $u_agent, 'Version' ) < strripos($u_agent,$ub)){
	            $version= $matches['version'][0];
	        }
	        else {
	            $version= $matches['version'][1];
	        }
	    }
	    else {
	        $version= $matches['version'][0];
	    }

	    // check if we have a number
	    if ($version == null || $version == '' )
	    	$version = '?';

	    return array(
	        'userAgent'	=> $u_agent,
	        'name'		=> $bname,
	        'version'	=> $version,
	        'platform'	=> $platform,
	        'pattern'	=> $pattern
	    );
	}
	
	/**
	 * redirect function for flagged logins
	 *
	 * @return WP_Bouncer
	 */
	public function flag_redirect() {		
		wp_redirect( $this->get_redirect_url() );
		exit();
	}

	/**
	 * run checks for a flagged login
	 *
	 * @return WP_Bouncer
	 */
	public function login_flag( $redirect = true ) {
		if(is_user_logged_in()) {	
			global $current_user;
			
			//ignore admins
			if(apply_filters('wp_bouncer_ignore_admins', true) && current_user_can("manage_options"))
				return false;
			
			//check the session ids
			$session_ids = get_transient("fakesessid_" . $current_user->user_login);			
			$old_session_ids = $session_ids;
						
			//make sure it's an array
			if(empty($session_ids))
				$session_ids = array();
			elseif(!is_array($session_ids))
				$session_ids = array($session_ids);

			//how many logins are allowed
			$num_allowed = apply_filters('wp_bouncer_number_simultaneous_logins', 1);
			
			//0 means do nothing
			if(empty($num_allowed))
				return false;
						
			//if we have more than the num allowed, remove some from the top
			while(count($session_ids) > $num_allowed) {				
				unset($session_ids[0]);	//remove oldest id
				$session_ids = array_values($session_ids);	//fix array keys								
			}
			
			//filter since 1.3
			$session_ids = apply_filters('wp_bouncer_session_ids', $session_ids, $old_session_ids, $current_user->ID);
						
			//save session ids in case we trimmed them
			set_transient("fakesessid_" . $current_user->user_login, $session_ids, apply_filters('wp_bouncer_session_length', 3600*24*30, $current_user->ID));
						
			if(!empty($session_ids)) {			
				if(empty($_COOKIE['fakesessid']) || !in_array($_COOKIE['fakesessid'], $session_ids)) {
					//hook in case we want to do something different
					$logout = apply_filters('wp_bouncer_login_flag', true, $session_ids);
					
					if($logout) {
						//log user out
						wp_logout();
						
						//redirect
						if ( $redirect ) {
							$this->flag_redirect();
						}
					}
					
					return true;
				}
			}
		}
		
		// if we get here the login is not a dupe
		return false;
	}

	/**
	 * track and set session data at login
	 *
	 * @return WP_Bouncer
	 */
	public function login_track($user_login) {		
		// get browser data from current login
		$browser	= $this->browser_data();
				
		//generate a new session id
		$new_session_id = md5($browser['name'] . $browser['platform'] . $_SERVER['REMOTE_ADDR'] . time());
		
		//save it in a list in a transient
		$session_ids = get_transient("fakesessid_" . $user_login);
				
		if(empty($session_ids))
			$session_ids = array();
		elseif(!is_array($session_ids))
			$session_ids = array($session_ids);
				
		$session_ids[] = $new_session_id;			
				
		set_transient("fakesessid_" . $user_login, $session_ids, 3600*24*30);		
		
		//and save it in a cookie		
		setcookie("fakesessid", $new_session_id, time()+3600*24*30, COOKIEPATH, COOKIE_DOMAIN, false);	
	}

	/**
	 * Add link to the user action links to reset sessions
	 *
	 * Use the wp_bouncer_reset_sessions_cap to change the capability required to see this.
	 */	
	public function user_row_actions($actions, $user) {	
		$cap = apply_filters('wp_bouncer_reset_sessions_cap', 'edit_users');
		if(current_user_can($cap)) {
			$url = admin_url("users.php?wpbreset=" . $user->ID);
			if(!empty($_REQUEST['s']))
				$url .= "&s=" . esc_attr($_REQUEST['s']);
			if(!empty($_REQUEST['paged']))
				$url .= "&paged=" . intval($_REQUEST['paged']);
			$url = wp_nonce_url($url, 'wpbreset_' . $user->ID);
			$actions[] = '<a href="' . $url . '">Reset Sessions</a>';
		}
		
		return $actions;
	}
	
	/**
	 * Reset sessions. Runs on admin init. Checks for wpbreset and nonce and resets sessions for that user.
	 */	
	public function reset_session() {
		if(!empty($_REQUEST['wpbreset'])) {
			global $wpb_msg, $wpb_msgt;
			
			//get user id
			$user_id = intval($_REQUEST['wpbreset']);
			$user = get_userdata($user_id);
						
			//no user?
			if(empty($user)) {
				//user not found error
				$wpb_msg = 'Could not reset sessions. User not found.';
				$wpb_msgt = 'error';
			} else {				
				//check nonce
				check_admin_referer( 'wpbreset_'.$user_id);
				
				//check caps
				$cap = apply_filters('wp_bouncer_reset_sessions_cap', 'edit_users');
				if(!current_user_can($cap)) {
					//show error message
					$wpb_msg = 'You do not have permission to reset user sessions.';
					$wpb_msgt = 'error';
				} else {
					//all good, delete this user's sessions
					delete_transient('fakesessid_'. $user->user_login);				
					
					//show success message
					$wpb_msg = 'Sessions reset for ' . $user->user_login . '.';
					$wpb_msgt = 'updated';
				}
			}						
		}
	}
	
	/**
	 * Show any messages generated by WP Bouncer.
	 */	
	public function admin_notices() {
		global $wpb_msg, $wpb_msgt;
		if(!empty($wpb_msg))
			echo "<div class=\"$wpb_msgt\"><p>$wpb_msg</p></div>"; 
	}
	
	/**
	 * Check login_flag via heartbeat API
	 */
	public function ajax_check() {
		$r = array();
		
		if( $this->login_flag( false ) ) {	
			$r['redirect_url'] = esc_url( $this->get_redirect_url() );
			$r['flagged'] = true;
		} else {
			$r['redirect_url'] = '';
			$r['flagged'] = false;
		}
		
		echo json_encode( $r );
		
		exit;
	}
	
/// end class
}


// Instantiate our class
$WP_Bouncer = new WP_Bouncer();
