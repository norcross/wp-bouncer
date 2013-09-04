<?php
/*
Plugin Name: WP Bouncer
Plugin URI: http://andrewnorcross.com/plugins/wp-bouncer/
Description: Only allow one device to be logged into WordPress for each user.
Version: 1.1
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

		add_action('wp_login', array($this, 'login_track'));
		add_action('init', array($this, 'login_flag'));
		
		$this->redirect = esc_url_raw( plugin_dir_url( __FILE__ ) . 'login-warning.php' );
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

		$base = plugin_dir_url( __FILE__ );
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
			if(current_user_can("manage_options"))
				return;
			
			//check the fakesessid
			$fakesessid = get_transient("fakesessid_" . $current_user->user_login);		
			
			//krumo("fakesessid_" . $current_user->user_login);
			//krumo($fakesessid);
			
			if(!empty($fakesessid))
			{			
				if(empty($_COOKIE['fakesessid']) || $fakesessid != $_COOKIE['fakesessid'])
				{
					//log user out
					wp_logout();
					
					//redirect
					$this->flag_redirect();
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
				
		//store a "fake" session id in transient and cookie
		$fakesessid = md5($browser['name'] . $broser['platform'] . $_SERVER['REMOVE_ADDR'] . time());
		set_transient("fakesessid_" . $user_login, $fakesessid, 3600*24*30);
		setcookie("fakesessid", $fakesessid, time()+3600*24*30, COOKIEPATH, COOKIE_DOMAIN, false);	
	}

	/**
	 * load textdomain
	 *
	 * @return WP_Bouncer
	 */


	public function textdomain() {

		load_plugin_textdomain( 'wp-bouncer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


/// end class
}


// Instantiate our class
$WP_Bouncer = new WP_Bouncer();
