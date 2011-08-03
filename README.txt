== WTF IS THIS ===

This is a class library that allows MediaWiki to use a MemberDB database
as authentication backend. In addition, new MW users will have their name
fields populated from the information in MemberDB.


== INSTALLATION ==

Edit the database credentials in the PHP class and put the file in
the includes/ directory.

Then add in includes/Setup.php:

if( !is_object( $wgAuth ) ) {
  $wgAuth = new StubObject( 'wgAuth', 'AuthPluginMemberDB' );
  wfRunHooks( 'AuthPluginSetup', array( &$wgAuth ) );
}

and in includes/AutoLoader.php add after AuthPlugin to $localClasses array
in the __autoload() function:

  'AuthPluginMemberDB' => 'includes/AuthPluginMemberDB.php',


== BUGS ==

None. Suck it up. If you find any, please fix them and send me a pull request.
