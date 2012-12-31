<?php
/*
Plugin Name: WP Bouncer
Plugin URI: http://andrewnorcross.com/plugins/
Description: Creates an admin page for storing multiple API keys.
Version: 1.0
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

    Copyright 2012 Andrew Norcross

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

	/**
	 * This is our constructor
	 *
	 * @return WP_Bouncer
	 */
	public function __construct() {

		add_action		( 'login_init', 				array( $this, 'login_track'				) 			);
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
		wp_redirect( esc_url_raw( $base.'/login-warning.php' ), 302 );
		exit();

	}

	/**
	 * run checks for a flagged login
	 *
	 * @return WP_Bouncer
	 */

	public function login_flag($user, $current, $browser) {

		// get last login data of current user
		$attempt		= $current[$user];
		$login_time		= $attempt['log-time'];
		$login_ip		= $attempt['log-ip'];
		$login_browser	= $attempt['log-browser'];
		$login_platform	= $attempt['log-platform'];

		// current variables
		$curr_time		= time();
		$curr_ip		= $_SERVER['REMOTE_ADDR'];
		$curr_browser	= $browser['name'];
		$curr_platform	= $browser['platform'];
/*
		if ( ($curr_time - $login_time) < 180 )
			$this->flag_redirect();
*/

	}

	/**
	 * track and set session data at login
	 *
	 * @return WP_Bouncer
	 */

	public function login_track() {

		if ( !isset( $_POST ) )
			return;

		if ( !isset( $_POST['wp-submit'] ) )
			return;

		if ( $_POST['wp-submit'] !== 'Log In'  )
			return;

		// get browser data from current login
		$browser	= $this->browser_data();

		$current	= get_option('login_data_test');

		$user		= $_POST['log'];

		// user is in the array. go to the checks
		if (!empty($current) && array_key_exists($user, $current)) {

			$this->login_flag($user, $current, $browser);

		}

		$updates	= array();


		$updates[$user]['log-time']		= time();
		$updates[$user]['log-ip']		= $_SERVER['REMOTE_ADDR'];
		$updates[$user]['log-browser']	= $browser['name'];
		$updates[$user]['log-platform']	= $browser['platform'];

		if (!empty($current)) {
			$data = array_merge($current, $updates);
		} else {
			$data = $updates;
		}

		update_option('login_data_test', $data);

	}

	/**
	 * load textdomain
	 *
	 * @return WP_Bouncer
	 */


	public function textdomain() {

		load_plugin_textdomain( 'lgcontrol', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}


/// end class
}


// Instantiate our class
$WP_Bouncer = new WP_Bouncer();
