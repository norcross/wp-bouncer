<?php
/*
Plugin Name: WP Bouncer
Plugin URI: http://andrewnorcross.com/plugins/wp-bouncer/
Description: Only allow one device to be logged into WordPress for each user.
Version: 1.3.1
Author: Andrew Norcross, strangerstudios
Author URI: http://andrewnorcross.com

    Copyright 2012 Andrew Norcross, Stranger Studios

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Start up the engine
class WP_Bouncer
{
	/*
		Settings. These are set in constructor for now.	
		We will eventually replace these via a settings page in the dashboard.
	*/
	public $redirect;


	/**
	 * This is our constructor
	 *
	 * @return WP_Bouncer
	 */
	public function __construct() {

		//track logins
		add_action('wp_login', array($this, 'login_track'));
		
		//bounce logins
		add_action('init', array($this, 'login_flag'));
			
		//add action links to reset sessions
		add_filter('user_row_actions', array($this, 'user_row_actions'), 10, 2);				
		
		//add check for resetting sessions
		add_action('admin_init', array($this, 'reset_session'));
		add_action('admin_notices', array($this, 'admin_notices'));
		
		$this->redirect = apply_filters('wp_bouncer_redirect_url', esc_url_raw( plugin_dir_url( __FILE__ ) . 'login-warning.php' ));
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
		
		wp_redirect( $this->redirect );
		exit();

	}

	/**
	 * run checks for a flagged login
	 *
	 * @return WP_Bouncer
	 */

	public function login_flag() {

		if(is_user_logged_in())
		{	
			global $current_user;
			
			//ignore admins
			if(apply_filters('wp_bouncer_ignore_admins', true) && current_user_can("manage_options"))
				return;
			
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
				return;
						
			//if we have more than the num allowed, remove some from the top
			while(count($session_ids) > $num_allowed)
			{				
				unset($session_ids[0]);	//remove oldest id
				$session_ids = array_values($session_ids);	//fix array keys								
			}
			
			//filter since 1.3
			$session_ids = apply_filters('wp_bouncer_session_ids', $session_ids, $old_session_ids, $current_user->ID);
						
			//save session ids in case we trimmed them
			set_transient("fakesessid_" . $current_user->user_login, $session_ids, apply_filters('wp_bouncer_session_length', 3600*24*30, $current_user->ID));
						
			if(!empty($session_ids))
			{			
				if(empty($_COOKIE['fakesessid']) || !in_array($_COOKIE['fakesessid'], $session_ids))
				{
					//hook in case we want to do something different
					$logout = apply_filters('wp_bouncer_login_flag', true, $session_ids);
					
					if($logout)
					{					
						//log user out
						wp_logout();
						
						//redirect
						$this->flag_redirect();
					}
				}
			}
		}
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
		$session_ids = get_transient("fakesessid_" . $user_login, false);
				
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
	 * load textdomain
	 *
	 * @return WP_Bouncer
	 */

	public function textdomain() {

		load_plugin_textdomain( 'wp-bouncer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add link to the user action links to reset sessions
	 *
	 * Use the wp_bouncer_reset_sessions_cap to change the capability required to see this.
	 */	
	public function user_row_actions($actions, $user) {	
		$cap = apply_filters('wp_bouncer_reset_sessions_cap', 'edit_users');
		if(current_user_can($cap))
		{
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
	 *	 
	 */	
	public function reset_session()
	{
		if(!empty($_REQUEST['wpbreset']))
		{
			global $wpb_msg, $wpb_msgt;
			
			//get user id
			$user_id = intval($_REQUEST['wpbreset']);
			$user = get_userdata($user_id);
						
			//no user?
			if(empty($user))
			{
				//user not found error
				$wpb_msg = 'Could not reset sessions. User not found.';
				$wpb_msgt = 'error';
			}			
			else
			{				
				//check nonce
				check_admin_referer( 'wpbreset_'.$user_id);
				
				//check caps
				$cap = apply_filters('wp_bouncer_reset_sessions_cap', 'edit_users');
				if(!current_user_can($cap))
				{
					//show error message
					$wpb_msg = 'You do not have permission to reset user sessions.';
					$wpb_msgt = 'error';
				}
				else
				{
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
	
/// end class
}


// Instantiate our class
$WP_Bouncer = new WP_Bouncer();
