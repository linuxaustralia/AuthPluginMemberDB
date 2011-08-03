== Documentation ===

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
