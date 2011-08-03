<?php
/**
 * @package MediaWiki
 */
# Copyright (C) 2004 Brion Vibber <brion@pobox.com>
# http://www.mediawiki.org/
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html

/**
 * Authentication plugin interface. Instantiate a subclass of AuthPlugin
 * and set $wgAuth to it to authenticate against some external tool.
 *
 * The default behavior is not to do anything, and use the local user
 * database for all authentication. A subclass can require that all
 * accounts authenticate externally, or use it only as a fallback; also
 * you can transparently create internal wiki accounts the first time
 * someone logs in who can be authenticated externally.
 *
 * This interface is new, and might change a bit before 1.4.0 final is
 * done...
 *
 * @package MediaWiki
 */

require_once('AuthPlugin.php');

class AuthPluginMemberDB extends AuthPlugin {

	/**
	 * We authenticate against the MemberDB accounts table.
	 */

	var $server = "localhost";
	var $dbname = "memberdb";
	var $dbuser = "memberdb";
	var $dbpass = "secret";
	var $dblink = null;

	function AuthPluginMemberDB() {
		$this->dblink = mysql_connect($this->server, $this->dbuser, $this->dbpass) or die ('Cannot connect to authentication database');
		mysql_select_db($this->dbname, $this->dblink) or die('Cannot connect to authentication database');
	}

	/**
	 * Check whether there exists a user account with the given name.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param string $username
	 * @return bool
	 * @access public
	 */
	function userExists( $username ) {
		$username_esc = mysql_escape_string($username);
		$q = "SELECT p.member_id FROM passwd AS p LEFT JOIN members AS m ON(p.member_id = m.id) WHERE m.email LIKE '{$username_esc}' LIMIT 1";
		$r = mysql_query($q, $this->dblink);
		if (!$r) die(mysql_error($this->dblink));

		if( mysql_num_rows( $r ) == 1 )
			return true;
		return false;
	}

	/**
	 * Check if a username+password pair is a valid login.
	 * The name will be normalized to MediaWiki's requirements, so
	 * you might need to munge it (for instance, for lowercase initial
	 * letters).
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 * @access public
	 */
	function authenticate( $username, $password ) {
		$username_esc = mysql_escape_string($username);
		$q = "SELECT p.member_id, p.salt, p.password, m.email, m.first_name, m.last_name FROM passwd AS p LEFT JOIN members AS m ON(p.member_id = m.id) WHERE m.email LIKE '{$username_esc}' LIMIT 1";
		$r = mysql_query($q, $this->dblink);
		if(mysql_num_rows($r) != 1)
			return false;
		$row = mysql_fetch_object($r);

		ereg("^[0-9a-z]+", $row->password, $dbpass);
		ereg("^[0-9a-zA-Z]+", $row->salt, $dbsalt);

		// Check if login is correct.
		if (md5($dbsalt[0].$password) == $dbpass[0])
			return true;
		return false;
	}

	/**
	 * Modify options in the login template.
	 *
	 * @param UserLoginTemplate $template
	 * @access public
	 */
	function modifyUITemplate( &$template ) {
		# Override this!
		$template->set( 'usedomain', false );
	}

	/**
	 * Set the domain this plugin is supposed to use when authenticating.
	 *
	 * @param string $domain
	 * @access public
	 */
	function setDomain( $domain ) {
		$this->domain = $domain;
	}

	/**
	 * Check to see if the specific domain is a valid domain.
	 *
	 * @param string $domain
	 * @return bool
	 * @access public
	 */
	function validDomain( $domain ) {
		# Override this!
		return true;
	}

	/**
	 * When a user logs in, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user
	 * @access public
	 */
	function updateUser( &$user ) {

		// Fetch email & name, insert into user.
		$username_esc = mysql_escape_string($user->mName);
		$q = "SELECT p.member_id, m.email, m.first_name, m.last_name FROM passwd AS p LEFT JOIN members AS m ON(p.member_id = m.id) WHERE m.email LIKE '{$username_esc}' LIMIT 1";
		$r = mysql_query($q, $this->dblink);
		if (mysql_num_rows($r) != 1)
			return false;
		$row = mysql_fetch_object($r);

		$user->mEmail = $row->email;
		$user->mRealName = "{$row->first_name} {$row->last_name}";

		return true;
	}


	/**
	 * Return true if the wiki should create a new local account automatically
	 * when asked to login a user who doesn't exist locally but does in the
	 * external auth database.
	 *
	 * If you don't automatically create accounts, you must still create
	 * accounts in some way. It's not possible to authenticate without
	 * a local account.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool
	 * @access public
	 */
	function autoCreate() {
		return true;
	}

	/**
	 * Set the given password in the authentication database.
	 * Return true if successful.
	 *
	 * @param string $password
	 * @return bool
	 * @access public
	 */
	function setPassword( $password ) {
		return true;
	}

	/**
	 * Update user information in the external authentication database.
	 * Return true if successful.
	 *
	 * @param User $user
	 * @return bool
	 * @access public
	 */
	function updateExternalDB( $user ) {
		// No, update in MemberDB and not via the wiki.
		return true;
	}

	/**
	 * Check to see if external accounts can be created.
	 * Return true if external accounts can be created.
	 * @return bool
	 * @access public
	 */
	function canCreateAccounts() {
		return false;
	}

	/**
	 * Add a user to the external authentication database.
	 * Return true if successful.
	 *
	 * @param User $user
	 * @param string $password
	 * @return bool
	 * @access public
	 */
	function addUser( $user, $password ) {
		return false;
	}


	/**
	 * Return true to prevent logins that don't authenticate here from being
	 * checked against the local database's password fields.
	 *
	 * This is just a question, and shouldn't perform any actions.
	 *
	 * @return bool
	 * @access public
	 */
	function strict() {
		return false;
	}

	/**
	 * When creating a user account, optionally fill in preferences and such.
	 * For instance, you might pull the email address or real name from the
	 * external user database.
	 *
	 * The User object is passed by reference so it can be modified; don't
	 * forget the & on your function declaration.
	 *
	 * @param User $user
	 * @access public
	 */
	function initUser( &$user ) {

		// Fetch email & name
		$username_esc = mysql_escape_string($user->mName);
		$q = "SELECT p.member_id, m.email, m.first_name, m.last_name FROM passwd AS p LEFT JOIN members AS m ON(p.member_id = m.id) WHERE m.email LIKE '{$username_esc}' LIMIT 1";
		$r = mysql_query($q, $this->dblink);
		if (mysql_num_rows($r) != 1)
			return false;
		$row = mysql_fetch_object($r);

		$user->mEmail = $row->email;

		$user->mRealName = "{$row->first_name} {$row->last_name}";
		$user->mOptions['nickname'] = $user->mRealName;

		return true;
	}

	/**
	 * If you want to munge the case of an account name before the final
	 * check, now is your chance.
	 */
	function getCanonicalName( $username ) {
		return $username;
	}
}

?>
