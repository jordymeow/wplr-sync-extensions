<?php
/*
	Plugin Name: Event Logger
	Plugin URI: https://meowapps.com/wplr-sync
	Description: Write logs for each action and filter that occur. This extension is a good template to create your own.
	Version: 0.1.0
	Author: Jordy Meow
	Author URI: https://meowapps.com
*/

class WPLR_Extension_Logger {

	public function __construct() {

		// Init
		add_filter( 'wplr_extensions', array( $this, 'extensions' ), 10, 1 );

		// Create / Update
		add_action( 'wplr_create_folder', array( $this, 'create_folder' ), 10, 3 );
		add_action( 'wplr_update_folder', array( $this, 'update_folder' ), 10, 2 );
		add_action( 'wplr_create_collection', array( $this, 'create_collection' ), 10, 3 );
		add_action( 'wplr_update_collection', array( $this, 'update_collection' ), 10, 2 );

		// Move
		add_action( "wplr_move_folder", array( $this, 'move_folder' ), 10, 3 );
		add_action( "wplr_move_collection", array( $this, 'move_collection' ), 10, 3 );

		// Delete
		add_action( "wplr_remove_collection", array( $this, 'remove_collection' ), 10, 1 );
		add_action( "wplr_remove_folder", array( $this, 'remove_folder' ), 10, 1 );

		// Media
		add_action( "wplr_add_media", array( $this, 'add_media' ), 10, 1 );
		add_action( "wplr_add_media_to_collection", array( $this, 'add_media_to_collection' ), 10, 3 );
		add_action( "wplr_remove_media_from_collection", array( $this, 'remove_media_from_collection' ), 10, 3 );
		add_action( "wplr_update_media", array( $this, 'update_media' ), 10, 2 );
		add_action( "wplr_remove_media", array( $this, 'remove_media' ), 10, 1 );

		// Tags
		add_action( "wplr_add_tag", array( $this, 'add_tag' ), 10, 3 );
		add_action( "wplr_update_tag", array( $this, 'update_tag' ), 10, 3 );
		add_action( "wplr_remove_tag", array( $this, 'remove_tag' ), 10, 1 );

		// Media Tags
		add_action( "wplr_add_media_tag", array( $this, 'add_media_tag' ), 10, 2 );
		add_action( "wplr_remove_media_tag", array( $this, 'remove_media_tag' ), 10, 2 );

		// Reorder
		add_action( "wplr_order_collection", array( $this, 'order_collection' ), 10, 2 );

		// Reset
		add_action( 'wplr_reset', array( $this, 'reset' ), 10, 0 );

		// Extra (probably not useful for an extension)
		add_action( "wplr_clean", array( $this, 'clean' ), 10, 1 );
	}

	// It's fairly important to add the current extension name to this in order to show to the users
	// that the extensions is available and loaded.
	function extensions( $extensions ) {
		array_push( $extensions, 'Event Logger' );
		global $wplr_log;
		$wplr_log = $this;
		return $extensions;
	}

	function log( $data ) {
		$fh = fopen( trailingslashit( plugin_dir_path( __FILE__ ) ) . '/3_logger.log', 'a' );
		$date = date( "Y-m-d H:i:s" );
		fwrite( $fh, "$date: {$data}\n" );
		fclose( $fh );
	}

	// The plugin will call all the actions to rollback whatever has been created.
	// However, if there is something you created manually you might want to remove it in this function.
	function reset() {
		$this->log( "reset." );
	}

	// This is just a cleanup function, it should only remove stuff that are not in use (garbage).
	function clean() {
		$this->log( "clean." );
	}

	// Created a new collection (ID $collectionId).
	// Placed in the folder $inFolderId, or in the root if empty.
	function create_collection( $collectionId, $inFolderId, $collection ) {
		if ( empty( $inFolderId ) )
			$inFolderId = "root";
		$this->log( sprintf( "create_collection %d (%s) in %s.", $collectionId, $collection['name'], $inFolderId ), true );
	}

	// Created a new folder (ID $folderId).
	// Placed in the folder $inFolderId, or in the root if empty.
	function create_folder( $folderId, $inFolderId, $folder ) {
		if ( empty( $inFolderId ) )
			$inFolderId = "root";
		$this->log( sprintf( "create_folder %d (%s) in %s.", $folderId, $folder['name'], $inFolderId ), true );
	}

	// Updated the collection with new information.
	// Currently, that would be only its name.
	function update_collection( $collectionId, $collection ) {
		$this->log( sprintf( "update_collection %d (%s).", $collectionId, $collection['name'] ), true );
	}

	// Updated the folder with new information.
	// Currently, that would be only its name.
	function update_folder( $folderId, $folder ) {
		$this->log( sprintf( "update_folder %d (%s).", $folderId, $folder['name'] ), true );
	}

	// Moved the collection under another folder.
	// If the folder is empty, then it is the root.
	function move_folder( $folderId, $inFolderId, $previousFolderId ) {
		if ( empty( $folderId ) )
			$folderId = "root";
		if ( empty( $previousFolderId ) )
			$previousFolderId = "root";
		$this->log( sprintf( "move_folder %d from %s to %s.", $folderId, $previousFolderId, $folderId ), true );
	}

	// Moved the collection under another folder.
	// If the folder is empty, then it is the root.
	function move_collection( $collectionId, $folderId, $previousFolderId ) {
		if ( empty( $folderId ) )
			$folderId = "root";
		if ( empty( $previousFolderId ) )
			$previousFolderId = "root";
		$this->log( sprintf( "move_collection %d from %s to %s.", $collectionId, $previousFolderId, $folderId ), true );
	}

	// Added meta to a collection.
	// The $mediaId is actually the WordPress Post/Attachment ID.
	function add_media_to_collection( $mediaId, $collectionId, $reOrder = false ) {
		$this->log( sprintf( "add_media_to_collection %d to collection %d.", $mediaId, $collectionId ), true );
	}

	// Remove media from the collection.
	function remove_media_from_collection( $mediaId, $collectionId, $reOrder = false ) {
		$this->log( sprintf( "remove_media_from_collection %d from %d.", $mediaId, $collectionId ), true );
	}

	// The media was physically added.
	function add_media( $mediaId ) {
		$this->log( sprintf( "add_media %d.", $mediaId ), true );
	}

	// The media was physically deleted.
	function remove_media( $mediaId ) {
		$this->log( sprintf( "remove_media %d.", $mediaId ), true );
	}

	// The media file was updated.
	// Since NextGEN uses its own copies, we need to delete the current one and add a new one.
	function update_media( $mediaId, $collectionIds ) {
		$this->log( sprintf( "update_media %d (found in collections %s).", $mediaId, implode( ',', $collectionIds ) ), true );
	}

	// New keyword added.
	function add_tag( $tagId, $name, $parentId ) {
		$this->log( sprintf( "add_tag %d (%s) in %s.", $tagId, $name, is_null( $parentId ) ? 'root' : $parentId ), true );
	}

	// Keyword updated.
	function update_tag( $tagId, $name, $parentId ) {
		$this->log( sprintf( "update_tag %d (%s) in %s.", $tagId, $name, is_null( $parentId ) ? 'root' : $parentId ), true );
	}

	// New keyword added.
	function remove_tag( $tagId ) {
		$this->log( sprintf( "remove_tag %d.", $tagId ), true );
	}

	// New keyword added for this media.
	function add_media_tag( $mediaId, $tag ) {
		$this->log( sprintf( "add_media_tag %s to %d.", $tag, $mediaId ), true );
	}

	// Keyword removed for this media.
	function remove_media_tag( $mediaId, $tag ) {
		$this->log( sprintf( "remove_media_tag %s from %d.", $tag, $mediaId ), true );
	}

	// The collection was deleted.
	function remove_collection( $collectionId ) {
		$this->log( sprintf( "remove_collection %d.", $collectionId ), true );
	}

	// The folder was deleted.
	function remove_folder( $folderId ) {
		$this->log( sprintf( "remove_folder %d.", $folderId ), true );
	}

	// The collection was re-ordered.
	function order_collection( $mediaIds, $collectionId ) {
		$this->log( sprintf( "order_collection %d.", $collectionId ), true );
	}

}

new WPLR_Extension_Logger;

?>
