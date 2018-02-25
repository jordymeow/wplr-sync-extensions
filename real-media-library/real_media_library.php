<?php
/*
Hyphens are added in the header below to avoid WordPress to recognize this extension as a real plugin. It's actually not a big problem but it displays an error while installing the plugin. If you want to use this extension as a standalone plugin, you can remove the hyphens ;)

- Plugin Name: Real Media Library (RML v2.8.3 and higher)
- Plugin URI: http://meowapps.com
- Description: Displays the hierarchy of folders and collections nicely on the left side of your Media Library by syncing with the RML plugin. It requires Real Media Library (from version 2.8). You can get it from here: <a target="_blank" href='https://codecanyon.net/item/wp-real-media-library-media-categories-folders/13155134?ref=TigrouMeow'>Real Media Library</a>.
- Version: 2.8.0
- Author: Jordy Meow
- Author URI: http://www.meow.fr
*/

class WPLR_Extension_RealMediaLibrary
{

	private $root_folder = "WP&#47;LR Sync";
	private $rootRestrictions = array("ren>","cre>","ins>","del>","mov>");
	private $restrictions = array("par>");

    public function __construct()
	{

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if ( !is_plugin_active( 'real-media-library/real-media-library.php' )
                || !defined('RML_VERSION') ||  version_compare(RML_VERSION, "2.8.3", "<")
                || !class_exists('\\MatthiasWeb\\RealMediaLibrary\\general\\Base')) {
            if ( is_admin() ) {
                add_action( 'admin_notices', array( $this, 'admin_notices' ), 10, 3 );
            }
            return;
        }

				// Improve the naming for the RML version because version > 3.3 had the following changelog:
				// - Improved the way of generating unique slugs
				if ( version_compare( RML_VERSION, "3.3.0", ">=" ) ) {
					$this->root_folder = "WP/LR Sync";
				}

        // Init
        add_filter( 'wplr_extensions', array( $this, 'extensions' ), 10, 1 );

        // Collection
        add_action( 'wplr_create_collection', array( $this, 'create_collection' ), 10, 3 );
        add_action( 'wplr_update_collection', array( $this, 'update_collection' ), 10, 2 );
        add_action( "wplr_remove_collection", array( $this, 'remove_collection' ), 10, 1 );
        add_action( "wplr_move_collection", array( $this, 'move_collection' ), 10, 3 );

        // Folder
        add_action( 'wplr_create_folder', array( $this, 'create_folder' ), 10, 3 );
        add_action( 'wplr_update_folder', array( $this, 'update_folder' ), 10, 2 );
        add_action( "wplr_move_folder", array( $this, 'move_folder' ), 10, 3 );
        add_action( "wplr_remove_folder", array( $this, 'remove_folder' ), 10, 1 );

        // Media
        //add_action( "wplr_add_media", array( $this, 'add_media' ), 10, 1 );
        add_action( "wplr_add_media_to_collection", array( $this, 'add_media_to_collection' ), 10, 2 );
        add_action( "wplr_remove_media_from_collection", array( $this, 'remove_media_from_collection' ), 10, 2 );

        // Extra
        add_action( 'wplr_reset', array( $this, 'reset' ), 10, 0 );

        // RML
        add_filter( 'RML/Folder/TreeNodeLi/Class', array( $this, 'rml_tree_node' ), 10, 2 );
        add_action( 'admin_footer', array( $this, 'footer' ) );
	}

	function extensions($extensions)
	{
		array_push($extensions, 'Real Media Library');
		return $extensions;
	}

	function admin_notices()
	{
?>
<div class="notice notice-error is-dismissible">
    <p><?php
			_e( 'The plugin <a href="https://codecanyon.net/item/wordpress-real-media-library-media-categories-folders/13155134?ref=TigrouMeow" target="_blank"><b>Real Media Library</b></a> is not active (maybe not installed neither) or the version of Real Media Library is < 2.8.3 (please update). However, you activated the WP/LR Sync extension for it. Until the plugin is installed, the extension will not be loaded.', 'lrsync' );
?></p>
</div>
<?php
	}

	/*
	INIT / ADMIN MENU
	*/

	function reset()
	{
		$this->remove_folder(-1);
	}

	function rml_tree_node($classes, $folder)
	{
		global $wplr;
		$id = $wplr->get_meta("wplr_rml_folder", -1);
		if ($folder->getId() == $id) {
				$classes[] = "rml-lightroom-folder";
		}
		return $classes;
	}

	function footer()
	{

?>
<style>
.aio-list-standard .rml-lightroom-folder > a > i:after {
  display: none;
}
.aio-list-standard .rml-lightroom-folder > a > i:before {
  font-family: 'Arial Black', 'Arial Bold', Gadget, sans-serif;
  content: "LR" !important;
  font-size: 10px;
  letter-spacing: -1px;
}
.aio-list-standard .rml-lightroom-folder > ul .mwf-collection:before {
  content: "\E801";
}
</style>
	<script>
	(function($) {
  window.rml.hooks.register("restrictionText", function(args, obj) {
    if (obj.parents(".rml-lightroom-folder").size() > 0) {
      args.text = "<b>This folder is synchronized with the WP/LR plugin.</b> " + args.text;
    }
  });
})( jQuery );
</script>
<?php
	}

	/*
	COLLECTIONS AND FOLDERS
	*/

	function create_lightroom_folder()
	{
		global $wplr;

		// Exists already?
		$id = $wplr->get_meta("wplr_rml_folder", -1);
		if (!empty($id)) {
		    return $id;
		}

		$id = wp_rml_create_or_return_existing_id($this->root_folder, _wp_rml_root(), RML_TYPE_COLLECTION, $this->rootRestrictions);
		if (is_array($id)) {
			error_log("Error while creating LR folder: " . $id[0]);
			exit;
		}
		wp_rml_structure_reset();
		$wplr->set_meta("wplr_rml_folder", -1, $id, true);
		return $id;
	}

	function create_folder($folderId, $inFolderId, $folder)
	{
		global $wplr;

		// Exists already?
		$id = $wplr->get_meta("wplr_rml_folder", $folderId);
		if (!empty($id)) {
			return $id;
		}

		// Default InFolder should be "WPLR Sync"
		if ($folderId != -1 && empty($inFolderId)) {
			$parent     = $this->create_lightroom_folder();
			$inFolderId = -1;
		} else {
			$parent = $wplr->get_meta("wplr_rml_folder", $inFolderId);
		}

		$id = wp_rml_create_or_return_existing_id($folder['name'], $parent, RML_TYPE_COLLECTION, $this->restrictions, true);
		if (is_array($id)) {
			error_log("Error while creating folder: " . $id[0]);
			exit;
		}
		wp_rml_structure_reset();
		$wplr->set_meta("wplr_rml_folder", $folderId, $id, true);
		return $id;
	}

	function create_collection($collectionId, $inFolderId, $collection, $isFolder = false)
	{
		global $wplr;
		if (empty($inFolderId)) {
			$this->create_lightroom_folder();
			$inFolderId = -1;
		}
		$parent = $wplr->get_meta("wplr_rml_folder", $inFolderId);
		$id     = wp_rml_create_or_return_existing_id($collection['name'], $parent, RML_TYPE_GALLERY, $this->restrictions, true);
		if (is_array($id)) {
		    error_log("Error while creating collection: " . $id[0]);
			exit;
		}
		wp_rml_structure_reset();
		$wplr->set_meta("wplr_rml_folder", $collectionId, $id, true);
	}

	// Updated the collection with new information.
	// Currently, that would be only its name.
	function update_collection($collectionId, $collection)
	{
		global $wplr;
		$id = $wplr->get_meta("wplr_rml_folder", $collectionId);
		wp_rml_rename($collection['name'], $id, true);
	}

	// Updated the folder with new information (currently, only its name)
	function update_folder($folderId, $folder)
	{
		global $wplr;
		$id = $wplr->get_meta("wplr_rml_folder", $folderId);
		wp_rml_rename($folder['name'], $id, true);
	}

	// Moved the collection under another folder.
	// If the folder is empty, then it is the root.
	function move_collection($collectionId, $folderId, $previousFolderId)
	{
		global $wplr;

		// Default InFolder should be "WPLR Sync"
		if ($collectionId != -1 && empty($folderId)) {
			$parent     = $this->create_lightroom_folder();
			$inFolderId = -1;
		} else {
			$parent = $wplr->get_meta("wplr_rml_folder", $folderId);
		}

		$id     = $wplr->get_meta("wplr_rml_folder", $collectionId);
		$folder = wp_rml_get_object_by_id($id);
		$folder->setParent($parent);
		wp_rml_structure_reset();
	}

	// Move the folder (category) under another one.
	// If the folder is empty, then it is the root.
	function move_folder($folderId, $inFolderId, $previousFolderId)
	{
		global $wplr;

		// Default InFolder should be "WPLR Sync"
		if ($folderId != -1 && empty($inFolderId)) {
				$parent     = $this->create_lightroom_folder();
				$inFolderId = -1;
		} else
				$parent = $wplr->get_meta("wplr_rml_folder", $inFolderId);

		$id     = $wplr->get_meta("wplr_rml_folder", $folderId);
		$folder = wp_rml_get_by_id($id, null, true);
		$folder->setParent(empty($parent) ? -1 : $parent);
		RML_Structure::getInstance()->resetData();
	}

	// function add_media( $mediaId ) {
	//   global $wplr;
	//   $this->create_collection( -2, null, array( "name" => "Photos" ) );
	//   $this->add_media_to_collection( $mediaId, -2 );
	// }

	// Added meta to a collection.
	// The $mediaId is actually the WordPress Post/Attachment ID.
	function add_media_to_collection($mediaId, $collectionId, $isRemove = false)
	{
		global $wplr;
		$id   = $wplr->get_meta("wplr_rml_folder", $collectionId);
		$resp = wp_rml_move($id, array( $mediaId ), true);
		if (is_array($resp)) {
			error_log("Error while adding media to collection: " . $resp[0]);
			exit;
		}
	}

	// Remove media from the collection.
	function remove_media_from_collection($mediaId, $collectionId)
	{
		wp_rml_move(-1, array( $mediaId ), true);
	}

	// The collection was deleted.
	function remove_collection($collectionId)
	{
		global $wplr;
		$id = $wplr->get_meta("wplr_rml_folder", $collectionId);
		wp_rml_delete($id, true);
		$wplr->delete_meta("wplr_rml_folder", $collectionId);
	}

	// Delete the folder.
	function remove_folder($folderId)
	{
		global $wplr;
		$id = $wplr->get_meta("wplr_rml_folder", $folderId);
		wp_rml_delete($id, true);
		$wplr->delete_meta("wplr_rml_folder", $folderId);
	}
}

new WPLR_Extension_RealMediaLibrary;

?>
