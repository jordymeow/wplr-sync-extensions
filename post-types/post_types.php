<?php
/*
Hyphens are added in the header below to avoid WordPress to recognize this extension as a real plugin. It's actually not a big problem but it displays an error while installing the plugin. If you want to use this extension as a standalone plugin, you can remove the hyphens ;)

- Plugin Name: <b>Post Types</b> (Recommended)
- Plugin URI: http://meowapps.com
- Plugin Key: 1
- Description: Gives you option to pick the post type used by your theme (often called Collection, Album, Portfolio...) to synchronize your LR collections with it. You can also pick a taxonomy (often called Folder, Set...). That way, this extension can integrate with most themes. Read the tutorial here: <a href="https://meowapps.com/wplr-sync/post-types-extension/">Post Types extension</a>.
- Version: 0.4.0
- Author: Jordy Meow
- Author URI: http://meowapps.com
*/

class WPLR_Extension_PostTypes {

  protected $hide_ads;


  public function __construct() {

    // Init
    add_filter( 'wplr_extensions', array( $this, 'extensions' ), 10, 1 );
    add_action( 'init', array( $this, 'init' ), 10, 0 );

    // Collection
    add_action( 'wplr_create_collection', array( $this, 'create_collection' ), 10, 3 );
    add_action( 'wplr_update_collection', array( $this, 'update_collection' ), 10, 2 );
    add_action( "wplr_remove_collection", array( $this, 'remove_collection' ), 10, 1 );
    add_action( "wplr_move_collection", array( $this, 'move_collection' ), 10, 3 );

    // Folder
    if ( $this->is_hierarchical() ) {
      add_action( 'wplr_create_folder', array( $this, 'create_folder' ), 10, 3 );
      add_action( 'wplr_update_folder', array( $this, 'update_collection' ), 10, 2 );
      add_action( "wplr_move_folder", array( $this, 'move_collection' ), 10, 3 ); // same as for collection
      add_action( "wplr_remove_folder", array( $this, 'remove_collection' ), 10, 1 ); // same as for collection
    }
    else {
      if ( $this->get_taxonomy() ) {
        add_action( 'wplr_create_folder', array( $this, 'create_folder' ), 10, 3 );
        add_action( 'wplr_update_folder', array( $this, 'update_folder' ), 10, 2 );
        add_action( "wplr_move_folder", array( $this, 'move_folder' ), 10, 3 );
        add_action( "wplr_remove_folder", array( $this, 'remove_folder' ), 10, 1 );
      }
    }

    if ( $this->get_taxonomy_tags() ) {
      add_action( "wplr_add_tag", array( $this, 'add_tag' ), 10, 3 );
      add_action( "wplr_update_tag", array( $this, 'update_tag' ), 10, 3 );
      add_action( "wplr_move_tag", array( $this, 'move_tag' ), 10, 3 );
      add_action( "wplr_remove_tag", array( $this, 'remove_tag' ), 10, 1 );
      add_action( "wplr_add_media_tag", array( $this, 'add_media_tag' ), 10, 2 );
      add_action( "wplr_remove_media_tag", array( $this, 'remove_media_tag' ), 10, 2 );
    }

    // Media
    add_action( "wplr_add_media_to_collection", array( $this, 'add_media_to_collection' ), 10, 3 );
    add_action( "wplr_remove_media_from_collection", array( $this, 'remove_media_from_collection' ), 10, 3 );

    // Post Types List
    $posttype = get_option( 'wplr_posttype' );
    if ( !empty( $posttype ) ) {
      add_filter( 'manage_' . $posttype . '_posts_columns', array( $this, 'manage_posts_columns' ) );
      add_action( 'manage_' . $posttype . '_posts_custom_column', array( $this, 'manage_posts_custom_column' ), 10, 2 );
    }

    // Extra
    $this->hide_ads = get_option( 'wplr_hide_ads', false );
    //add_action( 'wplr_reset', array( $this, 'reset' ), 10, 0 );
  }

  function extensions( $extensions ) {
    array_push( $extensions, 'Post Types' );
    return $extensions;
  }

	function manage_posts_columns( $cols ) {
		$cols["WPLRSync_PostTypes"] = "LR Sync";
		return $cols;
	}

	function manage_posts_custom_column( $column_name, $id ) {
		global $wpdb, $wplr;
		if ( $column_name != 'WPLRSync_PostTypes' )
			return;
    echo "<div class='wplr-sync-info wplrsync-media-" . $id . "'>";
    $res = $wplr->get_meta_from_value( 'wplr_pt_posttype', $id );
    echo $wplr->html_for_collection( $res );
    echo "</div>";
	}

  /*
    INIT / ADMIN MENU
  */

  function init() {
    if ( get_option( 'wplr_hide_posttypes' ) && !get_option( 'wplr_debugging_enabled' ) )
      return;
    add_action( 'admin_menu', array( $this, 'admin_menu' ) );
  }

  function admin_menu() {
    add_submenu_page( 'wplr-main-menu', 'Post Types', '&#8674; Post Types',
      'manage_options', 'wplr-post_types-menu', array( $this, 'admin_settings' ) );

    add_settings_section( 'wplr-post_types-settings', null,
      array( $this, 'admin_settings_intro' ),'wplr-post_types-menu' );

    $mode = get_option( 'wplr_posttype_mode' );
    $posttypes = get_post_types( '', 'names' );
    $posttype = get_option( 'wplr_posttype' );
    $taxonomy = get_option( 'wplr_taxonomy' );
    $taxonomy_tags = get_option( 'wplr_taxonomy_tags' );
    $posttypes = array_diff( $posttypes, array( 'attachment', 'revision', 'nav_menu_item' ) );

    array_unshift( $posttypes, "" );
    $taxonomies = get_object_taxonomies( $posttype );
    array_unshift( $taxonomies, "" );

    if ( $this->is_hierarchical() && !is_post_type_hierarchical( $this->get_posttype() ) )
      update_option( 'wplr_posttype_hierarchical', null );
    if ( $this->is_posttype_reuse() && empty( $posttype ) )
      update_option( 'wplr_posttype_reuse', null );
    if ( $this->is_taxonomy_reuse() && empty( $taxonomy ) )
      update_option( 'wplr_taxonomy_reuse', null );
    if ( $this->is_taxonomy_tags_reuse() && empty( $taxonomy_tags ) )
      update_option( 'wplr_taxonomy_tags_reuse', null );

      // POST TYPE SECTION
    add_settings_field( 'wplr_posttype', __( "Post Type", 'wplr-sync' ),
      array( $this, 'admin_posttype_callback' ), 'wplr-post_types-menu',
      'wplr-post_types-settings', $posttypes );
    add_settings_field( 'wplr_posttype_status', __( "Status", 'wplr-sync' ),
      array( $this, 'admin_posttype_status_callback' ), 'wplr-post_types-menu',
      'wplr-post_types-settings', array( 'publish', 'draft' ) );
    add_settings_field( 'wplr_posttype_reuse', __( "Reuse", 'wplr-sync' ),
      array( $this, 'admin_posttypes_reuse_callback' ), 'wplr-post_types-menu',
      'wplr-post_types-settings', array( "Enable" ) );
    add_settings_field( 'wplr_posttype_hierarchical', __( "Hierarchical", 'wplr-sync' ),
      array( $this, 'admin_posttypes_hierarchical_callback' ), 'wplr-post_types-menu',
      'wplr-post_types-settings', array( "Enable" ) );
    add_settings_field( 'wplr_posttype_mode', __( "Mode", 'wplr-sync' ),
      array( $this, 'admin_posttype_mode_callback' ), 'wplr-post_types-menu',
      'wplr-post_types-settings', array( 'WP Gallery', 'Array in Post Meta', 'Array in Post Meta (Imploded)',
        'Array of (ID -> FullSize) in Post Meta' ) );


    if ( ( $mode == 'Array in Post Meta'
      || $mode == 'Array of (ID -> FullSize) in Post Meta'
      || $mode == 'Array in Post Meta (Imploded)' ) ) {
      add_settings_field( 'wplr_posttype_meta', "Post Meta",
        array( $this, 'admin_posttype_meta_callback' ), 'wplr-post_types-menu',
        'wplr-post_types-settings', "" );
    }

    // TAXONOMY SECTION
    add_settings_section( 'wplr-post_types-taxonomy-settings', null,
      array( $this, 'admin_settings_intro_taxonomy' ),'wplr-post_types-taxonomy-menu' );
    add_settings_field( 'wplr_taxonomy', __( "Taxonomy", 'wplr-sync' ),
      array( $this, 'admin_posttype_taxonomy_callback' ), 'wplr-post_types-taxonomy-menu',
      'wplr-post_types-taxonomy-settings', $taxonomies );
    add_settings_field( 'wplr_taxonomy_reuse', __( "Reuse", 'wplr-sync' ),
      array( $this, 'admin_posttypes_taxonomy_reuse_callback' ), 'wplr-post_types-taxonomy-menu',
      'wplr-post_types-taxonomy-settings', array( "Enable" ) );

    // TAXONOMY TAGS
    add_settings_section( 'wplr-post_types-taxonomy-tags-settings', null,
      array( $this, 'admin_settings_intro_taxonomy_tags' ),'wplr-post_types-taxonomy-tags-menu' );
    add_settings_field( 'wplr_taxonomy_tags', __( "Taxonomy", 'wplr-sync' ),
      array( $this, 'admin_posttype_taxonomy_tags_callback' ), 'wplr-post_types-taxonomy-tags-menu',
      'wplr-post_types-taxonomy-tags-settings', $taxonomies );
    add_settings_field( 'wplr_taxonomy_tags_reuse', __( "Reuse", 'wplr-sync' ),
      array( $this, 'admin_posttypes_taxonomy_tags_reuse_callback' ), 'wplr-post_types-taxonomy-tags-menu',
      'wplr-post_types-taxonomy-tags-settings', array( "Enable" ) );

    register_setting( 'wplr-post_types-settings', 'wplr_posttype' );
    register_setting( 'wplr-post_types-settings', 'wplr_posttype_status' );
    register_setting( 'wplr-post_types-settings', 'wplr_posttype_hierarchical' );
    register_setting( 'wplr-post_types-settings', 'wplr_posttype_reuse' );
    register_setting( 'wplr-post_types-settings', 'wplr_posttype_mode' );
    register_setting( 'wplr-post_types-settings', 'wplr_posttype_meta' );
    register_setting( 'wplr-post_types-taxonomy-settings', 'wplr_taxonomy' );
    register_setting( 'wplr-post_types-taxonomy-settings', 'wplr_taxonomy_reuse' );
    register_setting( 'wplr-post_types-taxonomy-tags-settings', 'wplr_taxonomy_tags' );
    register_setting( 'wplr-post_types-taxonomy-tags-settings', 'wplr_taxonomy_tags_reuse' );
  }

  function is_posttype_reuse() {
    return get_option( 'wplr_posttype_reuse' );
  }

  function is_taxonomy_reuse() {
    return get_option( 'wplr_taxonomy_reuse' );
  }

  function is_taxonomy_tags_reuse() {
    return get_option( 'wplr_taxonomy_tags_reuse' );
  }

  function is_hierarchical() {
    return get_option( 'wplr_posttype_hierarchical' );
  }

  function get_posttype() {
    $posttype = get_option( 'wplr_posttype' );
    if ( empty( $posttype ) || $posttype == 'none' )
      return null;
    return $posttype;
  }

  function get_posttype_status() {
    $posttype = get_option( 'wplr_posttype_status' );
    if ( empty( $posttype ) || $posttype == 'none' )
      return 'draft';
    return $posttype;
  }

  function get_posttype_mode() {
    $mode = get_option( 'wplr_posttype_mode' );
    if ( empty( $mode ) || $mode == 'none' )
      return 'WP Gallery';
    return $mode;
  }

  function get_posttype_meta() {
    $meta = get_option( 'wplr_posttype_meta' );
    if ( empty( $meta ) || $meta == 'none' )
      return '';
    return $meta;
  }

  function get_taxonomy() {
    $taxonomy = get_option( 'wplr_taxonomy' );
    if ( empty( $taxonomy ) || $taxonomy == 'none' )
      return null;
    return $taxonomy;
  }

  function get_taxonomy_tags() {
    $taxonomy = get_option( 'wplr_taxonomy_tags' );
    if ( empty( $taxonomy ) || $taxonomy == 'none' )
      return null;
    return $taxonomy;
  }

  function admin_settings_intro() {
    $taxonomy = $this->get_taxonomy();
    $taxonomy_tags = $this->get_taxonomy_tags();
    $posttype = $this->get_posttype();
    $is_found = false;
    if ( !empty( $taxonomy ) ) {
      $taxonomies = get_object_taxonomies( $posttype );
      foreach ( $taxonomies as $t ) {
        if ( $t == $taxonomy )
          $is_found = true;
      }
      if ( !$is_found ) {
        update_option( 'wplr_taxonomy', null );
        if ( !empty( $posttype ) ) {
          echo "<div class='notice notice-error is-dismissible'><p>";
          echo sprintf( __( "Taxonomy (for folders) was reset since '%s' could not be found in '%s'.", 'wplr-sync' ),
            $taxonomy, $posttype );
          echo "</p></div>";
        }
      }
    }
    if ( !empty( $taxonomy_tags ) ) {
      $taxonomies = get_object_taxonomies( $posttype );
      foreach ( $taxonomies as $t ) {
        if ( $t == $taxonomy_tags )
          $is_found = true;
      }
      if ( !$is_found ) {
        update_option( 'wplr_taxonomy_tags', null );
        if ( !empty( $posttype ) ) {
          echo "<div class='notice notice-error is-dismissible'><p>";
          echo sprintf( __( "Taxonomy (for tags) was reset since '%s' could not be found in '%s'.", 'wplr-sync' ),
            $taxonomy_tags, $posttype );
          echo "</p></div>";
        }
      }
    }
    $mode = $this->get_posttype_mode();
    $meta = $this->get_posttype_meta();
    if ( ( $mode == 'Array in Post Meta' ||
      $mode == 'Array of (ID -> FullSize) in Post Meta' ||
      $mode == 'Array in Post Meta (Imploded)' ) && empty( $meta ) ) {
        echo "<div class='notice notice-error is-dismissible'><p>";
        _e( "A Post Meta is required by the current mode.", 'wplr-sync' );
        echo "</p></div>";
    }
  }

  function admin_settings_intro_taxonomy() {
    echo '<h2>' . __( "Folder (LR) &#x2192; Taxonomy (WP)", 'wplr-sync' ) . '</h2>';
  }

  function admin_settings_intro_taxonomy_tags() {
    echo '<h2>' . __( "Keywords (LR) &#x2192; Taxonomy (WP)", 'wplr-sync' ) . '</h2>';
  }

  function admin_posttype_callback( $args ) {
    $html = '<select id="wplr_posttype" name="wplr_posttype" style="width: 100%;">';
    foreach ( $args as $arg )
      $html .= '<option value="' . $arg . '"' . selected( $arg, get_option( 'wplr_posttype' ), false ) . ' > '  .
        ( empty( $arg ) ? 'none' : $arg ) . '</option><br />';
    $html .= '</select><br />';

    $html .= '<span class="description">';
    $html .= __( 'Your collections in LR will be synchronized with this post type.<br /><b>Please click "Save Changes" every time you modify the "Post Type"</b> so that the taxonomies can be properly reloaded.', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttype_status_callback( $args ) {
    $html = '<select id="wplr_posttype_status" name="wplr_posttype_status" style="width: 100%;">';
    foreach ( $args as $arg )
      $html .= '<option value="' . $arg . '"' . selected( $arg, $this->get_posttype_status(), false ) . ' > '  .
        ( empty( $arg ) ? 'none' : $arg ) . '</option><br />';
    $html .= '</select><br />';

    $html .= '<span class="description">';
    $html .= __( 'Status of your post-type when it is created.', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttype_mode_callback( $args ) {
    $html = '<select id="wplr_posttype_mode" name="wplr_posttype_mode" style="width: 100%;">';
    foreach ( $args as $arg )
      $html .= '<option value="' . $arg . '"' . selected( $arg, $this->get_posttype_mode(), false ) . ' > '  .
        ( empty( $arg ) ? 'none' : $arg ) . '</option><br />';
    $html .= '</select><br />';

    $html .= '<span class="description">';
    $html .= __( 'By default, it should be WP Gallery and native galleries will be created and maintained in your posts. For other modes, check the <a href="https://meowapps.com/wplr-sync/post-types-extension/">tutorial</a>. When switching to a different mode, click on "Save Changes". More settings might be available after you "Save Changes".', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttype_meta_callback( $args ) {
    $meta = $this->get_posttype_meta();
    $html = '<input type="text" style="width: 260px;" id="wplr_posttype_meta" name="wplr_posttype_meta" value="' . $meta . '" />';
    $html .= '<br />';

    $html .= '<span class="description">';
    $html .= __( 'The current chosen mode require the key of the <b>Post Meta</b> you would like the extension to update.', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttypes_hierarchical_callback( $args ) {
    $posttype = $this->get_posttype();
    $html = '<input type="checkbox" id="wplr_posttype_hierarchical" name="wplr_posttype_hierarchical" value="1" ' .
      ( is_post_type_hierarchical( $this->get_posttype() ) ? '' : 'disabled ' ) .
      checked( 1, get_option( 'wplr_posttype_hierarchical' ), false ) . '/>';
    $html .= '<label for="wplr_posttype_hierarchical"> '  . $args[0] . '</label><br>';

    $html .= '<span class="description">';
    $html .= sprintf( __( 'If your post type is hierarchical, with this option the hierarchy of collections will be made using the Post Type "%s".<br />Usage of taxonomies will be disabled.', 'wplr-sync' ), $posttype );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttypes_reuse_callback( $args ) {
    $posttype = $this->get_posttype();
    $html = '<input type="checkbox" id="wplr_posttype_reuse" name="wplr_posttype_reuse" value="1" ' .
      ( empty( $posttype ) ? 'disabled ' : '' ) .
      checked( 1, get_option( 'wplr_posttype_reuse' ), false ) . '/>';
    $html .= '<label for="wplr_posttype_reuse"> '  . $args[0] . '</label><br>';

    $html .= '<span class="description">';
    $html .= __( 'If the name of your collection (LR) already matches the name of an existing post type, it will become associated with it instead of creating a new one.', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttype_taxonomy_callback( $args ) {
    $taxonomy = $this->get_taxonomy();
    $html = '<select id="wplr_taxonomy" name="wplr_taxonomy" ' .
      ( $this->is_hierarchical() ? 'disabled ' : '' ) . ' style="width: 100%;">';
    foreach ( $args as $arg )
      $html .= '<option value="' . $arg . '"' .
        selected( $arg, get_option( 'wplr_taxonomy' ), false ) . ' > '  .
        ( empty( $arg ) ? 'none' : $arg ) . '</option><br />';
    $html .= '</select><br />';
    $html .= '<span class="description">' . __( 'Your folders (LR) will be synchronized with the terms in this taxonomy.', 'wplr-sync' ) . '</label>';
    echo $html;
  }

  function admin_posttypes_taxonomy_reuse_callback( $args ) {
    $taxonomy = $this->get_taxonomy();
    $html = '<input type="checkbox" id="wplr_taxonomy_reuse" name="wplr_taxonomy_reuse" value="1" ' .
      ( empty( $taxonomy ) ? 'disabled ' : '' ) .
      checked( 1, get_option( 'wplr_taxonomy_reuse' ), false ) . '/>';
    $html .= '<label for="wplr_taxonomy_reuse"> '  . $args[0] . '</label><br>';

    $html .= '<span class="description">';
    $html .= __( 'If the name of your folder (LR) already matches the name of an existing term (of your taxonomy), it will become associated with it instead of creating a new one.', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttype_taxonomy_tags_callback( $args ) {
    $taxonomy = $this->get_taxonomy_tags();
    $html = '<select id="wplr_taxonomy_tags" name="wplr_taxonomy_tags" ' .
      ( $this->is_hierarchical() ? 'disabled ' : '' ) . ' style="width: 100%;">';
    foreach ( $args as $arg )
      $html .= '<option value="' . $arg . '"' .
        selected( $arg, get_option( 'wplr_taxonomy_tags' ), false ) . ' > '  .
        ( empty( $arg ) ? 'none' : $arg ) . '</option><br />';
    $html .= '</select><br />';

    $html .= '<span class="description">';
    $html .= __( 'Your keywords (LR) will be synchronized with the terms in this taxonomy.', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_posttypes_taxonomy_tags_reuse_callback( $args ) {
    $taxonomy = $this->get_taxonomy_tags();
    $html = '<input type="checkbox" id="wplr_taxonomy_tags_reuse" name="wplr_taxonomy_tags_reuse" value="1" ' .
      ( empty( $taxonomy ) ? 'disabled ' : '' ) .
      checked( 1, get_option( 'wplr_taxonomy_tags_reuse' ), false ) . '/>';
    $html .= '<label for="wplr_taxonomy_tags_reuse"> '  . $args[0] . '</label><br>';

    $html .= '<span class="description">';
    $html .= __( 'If the name of your keyword (LR) already matches the name of an existing term (of your taxonomy), it will become associated with it instead of creating a new one.', 'wplr-sync' );
    $html .= '</span>';

    echo $html;
  }

  function admin_settings() {
    global $wplr_admin;
    ?>
    <div class="wrap">

      <?php echo $wplr_admin->display_title( "Post Types Extension | WP/LR Sync" );  ?>

      <p><?php _e( "There is a tutorial about this extension:", 'wplr-sync' ) ?> <a target="_blank" href="https://meowapps.com/wplr-sync/post-types-extension/">Post Types Extension</a>.</p>

      <div class="meow-section meow-group">

				<div class="meow-col meow-span_1_of_2">
					<div class="meow-box">
						<h3><?php _e( "Collection (LR) → Post Type (WP)", 'wplr-sync' ) ?></h3>
						<div class="inside">
							<form method="post" action="options.php">
                <?php settings_fields( 'wplr-post_types-settings' ); ?>
                <?php do_settings_sections( 'wplr-post_types-menu' ); ?>
                <?php submit_button(); ?>
              </form>
						</div>
					</div>
				</div>

				<div class="meow-col meow-span_1_of_2">

          <?php if ( get_option( 'wplr_posttype' ) ): ?>

          <div class="meow-box">
						<h3><?php _e( "Folder (LR) → Taxonomy (WP)", 'wplr-sync' ) ?></h3>
						<div class="inside">
							<form method="post" action="options.php">
                <?php settings_fields( 'wplr-post_types-taxonomy-settings' ); ?>
                <?php do_settings_sections( 'wplr-post_types-taxonomy-menu' ); ?>
                <?php submit_button(); ?>
              </form>
						</div>
					</div>

          <div class="meow-box">
						<h3><?php _e( "Keywords (LR) → Taxonomy (WP)", 'wplr-sync' ) ?></h3>
						<div class="inside">
							<form method="post" action="options.php">
                <?php settings_fields( 'wplr-post_types-taxonomy-tags-settings' ); ?>
                <?php do_settings_sections( 'wplr-post_types-taxonomy-tags-menu' ); ?>
                <?php submit_button(); ?>
              </form>
						</div>
					</div>

          <?php endif; ?>

				</div>

			</div>

    </div>
    <?php
  }

  /*
    COLLECTIONS AND FOLDERS
  */

  function create_collection( $collectionId, $inFolderId, $collection, $isFolder = false ) {
    global $wplr;
    $posttype = $this->get_posttype();

    if ( empty( $posttype ) )
      return;

    $id = $wplr->get_meta( "wplr_pt_posttype", $collectionId );
    if ( !get_post( $id ) ) {
      $name = $collection['name'];
      error_log( "WP/LR Sync: Collection $name ($id) has to be re-created." );
      $id = null;
    }

    // Check if the entry with same name exist already
    if ( empty( $id ) && $this->is_posttype_reuse() ) {
      global $wpdb;
      $id = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = '".$posttype."' and post_title = '" . $name . "'" );
      if ( !empty( $id ) )
        $wplr->set_meta( "wplr_pt_posttype", $collectionId, $id, true );
    }

    // If doesn't exit, create new entry
    if ( empty( $id ) ) {

      // Get the ID of the parent collection (if any) - check the end of this function for more explanation.
      $post_parent = null;
      if ( $this->is_hierarchical() && !empty( $inFolderId ) )
        $post_parent = $wplr->get_meta( "wplr_pt_posttype", $inFolderId );

      $mode = $this->get_posttype_mode();

      // Create the collection.
      $post = array(
        'post_title'    => wp_strip_all_tags( $collection['name'] ),
        'post_content'  => ( $isFolder || $mode != 'WP Gallery' ) ? '' :
        '[gallery link="file" size="large" ids=""]', // if folder, nothing, if collection, let's start a gallery
        'post_status'   => $this->get_posttype_status(),
        'post_type'     => $posttype,
        'post_parent'   => $this->is_hierarchical() ? $post_parent : null
      );
      $id = wp_insert_post( $post );
      $wplr->set_meta( "wplr_pt_posttype", $collectionId, $id, true );

      // Add taxonomy information
      $taxonomy = $this->get_taxonomy();
      if ( !$this->is_hierarchical() && !empty( $taxonomy ) && !empty( $inFolderId ) )
        $wplr->add_taxonomy_to_posttype( $inFolderId, $collectionId, $taxonomy, 'wplr_pt_posttype', 'wplr_pt_term_id' );
    }
  }

  function create_folder( $folderId, $inFolderId, $folder ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy();

    // Create a collection (post type) that will act as a container for real collections
    if ( $this->is_hierarchical() )
      $this->create_collection( $folderId, $inFolderId, $folder, true );

    // Create a tax for that folder
    else if ( !empty( $taxonomy ) ) {
      $wplr->create_taxonomy( $folderId, $inFolderId, $folder, $taxonomy, 'wplr_pt_term_id' );
    }
  }

  // Updated the folder with new information.
  // Currently, that would be only its name.
  function update_folder( $folderId, $folder ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy();
    $wplr->update_taxonomy( $folderId, $folder, $taxonomy, 'wplr_pt_term_id' );
  }

  // Updated the collection with new information.
  // Currently, that would be only its name.
  function update_collection( $collectionId, $collection ) {
    global $wplr;
    $id = $wplr->get_meta( "wplr_pt_posttype", $collectionId );
    $post = array( 'ID' => $id, 'post_title' => wp_strip_all_tags( $collection['name'] ) );
    wp_update_post( $post );
  }

  // Updated the folder with new information (currently, only its name)
  function wplr_keyword_tax_id( $folderId, $folder ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy();

    // Hierarchical
    if ( $this->is_hierarchical() )
      $this->update_collection( $folderId, $folder );

    // Taxonomy
    else if ( !empty( $taxonomy ) ) {
      $wplr->update_taxonomy( $folderId, $folder, $taxonomy, 'wplr_pt_posttype' );
    }
  }

  // Moved the collection under another folder.
  // If the folder is empty, then it is the root.
  function move_collection( $collectionId, $folderId, $previousFolderId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy();

    // Hierarchical
    if ( $this->is_hierarchical() ) {
      $post_parent = null;
      if ( !empty( $folderId ) )
        $post_parent = $wplr->get_meta( "wplr_pt_posttype", $folderId );
      $id = $wplr->get_meta( "wplr_pt_posttype", $collectionId );
      $post = array( 'ID' => $id, 'post_parent' => $post_parent );
      wp_update_post( $post );
    }

    // Taxonomy
    else if ( !empty( $taxonomy ) ) {
      $wplr->remove_taxonomy_from_posttype( $previousFolderId, $collectionId,
        $taxonomy, 'wplr_pt_posttype', 'wplr_pt_term_id' );
      $wplr->add_taxonomy_to_posttype( $folderId, $collectionId, $taxonomy, 'wplr_pt_posttype', 'wplr_pt_term_id' );
    }
  }

  // Move the folder (category) under another one.
  // If the folder is empty, then it is the root.
  function move_folder( $folderId, $inFolderId, $previousFolderId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy();
    $wplr->move_taxonomy( $folderId, $inFolderId, $taxonomy, 'wplr_pt_term_id' );
  }

  // Added meta to a collection.
  // The $mediaId is actually the WordPress Post/Attachment ID.
  function add_media_to_collection( $mediaId, $collectionId, $reOrder = false, $isRemove = false ) {
    global $wplr;
    $id = $wplr->get_meta( "wplr_pt_posttype", $collectionId );
    $mode = $this->get_posttype_mode();
    $ids = array();

    // In the case it is a WP Gallery
    if ( $mode == 'WP Gallery' ) {
      $content = get_post_field( 'post_content', $id );
      preg_match_all( '/\[gallery.*ids="([0-9,]*)".*\]/', $content, $results );
      if ( !empty( $results ) && !empty( $results[1] ) ) {
        $str = $results[1][0];
        $ids = !empty( $str ) ? explode( ',', $str ) : array();
        $index = array_search( $mediaId, $ids, false );
        if ( $isRemove ) {
          if ( $index !== FALSE )
            unset( $ids[$index] );
        }
        else {
          // If mediaId already there then exit.
          if ( $index !== FALSE )
            return;
          array_push( $ids, $mediaId );
        }

        // Replace the array within the gallery shortcode.
        $content = str_replace( 'ids="' . $str, 'ids="' . implode( ',', $ids ), $content );
        $post = array( 'ID' => $id, 'post_content' => $content );
        wp_update_post( $post );
      }
      else {
        error_log( "Cannot find gallery in the post $collectionId." );
      }
    }
    // In the case the meta is an array, or an imploded array (a string that needs to be exploded)
    else if ( $mode == 'Array in Post Meta' || $mode == 'Array in Post Meta (Imploded)' ) {
      $meta = $this->get_posttype_meta();
      $ids = get_post_meta( $id, $meta, true );
      if ( $mode == 'Array in Post Meta (Imploded)' )
        $ids = explode( ',', $ids );
      if ( empty( $ids ) )
        $ids = array();
      $index = array_search( $mediaId, $ids, false );
      if ( $isRemove ) {
        if ( $index !== FALSE )
          unset( $ids[$index] );
      }
      else {
        // If mediaId already there then exit.
        if ( $index !== FALSE )
          return;
        array_push( $ids, $mediaId );
      }
      if ( $mode == 'Array in Post Meta (Imploded)' ) {
        $idsForUpdate =  implode( $ids, ',' );
        $idsForUpdate =  trim( $idsForUpdate, ',' );
        update_post_meta( $id, $meta, $idsForUpdate );
      }
      else
        update_post_meta( $id, $meta, $ids );
    }
    // In the case the meta is an array containing directly the url to the FullSize image
    else if ( $mode == 'Array of (ID -> FullSize) in Post Meta' ) {
      $meta = $this->get_posttype_meta();
      $ids = get_post_meta( $id, $meta, true );
      if ( empty( $ids ) )
        $ids = array();
      if ( $isRemove ) {
        if ( !empty( $ids[$mediaId] ) )
          unset( $ids[$mediaId] );
      }
      else {
        // If mediaId already there then exit.
        if ( !empty( $ids[$mediaId] ) )
          return;
        $ids[$mediaId] = get_attached_file( $mediaId );
      }
      update_post_meta( $id, $meta, $ids );
    }

    if ( $isRemove ) {
      // Need to delete the featured image if it was this media
      $thumbId = get_post_meta( $id, '_thumbnail_id', true );
      if ( $thumbId == $mediaId ) {
        if ( count( $ids ) > 0 )
          update_post_meta( $id, '_thumbnail_id', reset( $ids ) );
        else
          delete_post_meta( $id, '_thumbnail_id' );
      }
    }
    else {
      // Add a default featured image if none
      add_post_meta( $id, '_thumbnail_id', $mediaId, true );
    }

    // Attach the media to the collection
    wp_update_post( array( 'ID' => $mediaId, 'post_parent' => $id ) );

    // Update keywords
    $taxotag = $this->get_taxonomy_tags();
    if ( !empty( $taxotag ) ) {
      $tags = $wplr->get_tags_from_media( $mediaId );
      foreach ( $tags as $tagId )
        $wplr->add_taxonomy_to_posttype( $tagId, $collectionId, $taxotag, 'wplr_pt_posttype', 'wplr_pt_term_id' );
    }
  }

  // Remove media from the collection.
  function remove_media_from_collection( $mediaId, $collectionId, $reOrder = false ) {
    global $wplr;
    $this->add_media_to_collection( $mediaId, $collectionId, $reOrder, true );

    // No need to do more if it's a re-order
    if ( $reOrder )
      return;

    // Attach the media to the collection
    wp_update_post( array( 'ID' => $mediaId, 'post_parent' => 0 ) );

    // Update keywords

    // Count the number of time the tags are used in the collection
    $taxotag = $this->get_taxonomy_tags();
    if ( !empty( $taxotag ) ) {
      $tagsCount = array();
      $mediaIds = $wplr->get_media_from_collection( $collectionId );
      foreach ( $mediaIds as $m ) {
        $tags = $wplr->get_tags_from_media( $m );
        foreach ( $tags as $tagId ) {
          if ( isset( $tagsCount[$tagId] ) )
            $tagsCount[$tagId]++;
          else
            $tagsCount[$tagId] = 1;
        }
      }
      //error_log( "TAGSCOUNT: ", print_r( $tagsCount, 1 ) );

      $tags = $wplr->get_tags_from_media( $mediaId );
      //error_log( "TAGS FROM MEDIA $mediaId (to remove maybe): ", print_r( $tags, 1 ) );
      $taxotag = $this->get_taxonomy_tags();
      foreach ( $tags as $tagId ) {
        if ( !isset( $tagsCount[$tagId] ) )
          $wplr->remove_taxonomy_from_posttype( $tagId, $collectionId, $taxotag, 'wplr_pt_posttype', 'wplr_pt_term_id' );
      }
    }
  }

  // The collection was deleted.
  function remove_collection( $collectionId ) {
    global $wplr;
    $id = $wplr->get_meta( "wplr_pt_posttype", $collectionId );
    wp_delete_post( $id, true );
    $wplr->delete_meta( "wplr_pt_posttype", $collectionId );
  }

  // Delete the folder.
  function remove_folder( $folderId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy();
    $wplr->remove_taxonomy( $folderId, $taxonomy, $this->get_posttype(), 'wplr_pt_term_id' );
  }

  /*
    TAGS
  */

  // New keyword added.
  function add_tag( $tagId, $name, $parentId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy_tags();
    $wplr->create_taxonomy( $tagId, $parentId, array( 'name' => $name ), $taxonomy, 'wplr_pt_term_id' );
  }

  // Keyword updated.
  function update_tag( $tagId, $name ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy_tags();
    $wplr->update_taxonomy( $tagId, array( 'name' => $name ), $taxonomy, 'wplr_pt_posttype' );
  }

  function move_tag( $folderId, $inFolderId, $previousFolderId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy_tags();
    $wplr->move_taxonomy( $folderId, $inFolderId, $taxonomy, 'wplr_pt_term_id' );
  }

  // New keyword added.
  function remove_tag( $tagId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy_tags();
    $wplr->remove_taxonomy( $tagId, $taxonomy, $this->get_posttype(), 'wplr_pt_term_id' );
  }

  // New keyword added for this media.
  function add_media_tag( $mediaId, $tagId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy_tags();
    $collections = $wplr->get_collections_from_media( $mediaId );
    foreach ( $collections as $collectionId )
      $wplr->add_taxonomy_to_posttype( $tagId, $collectionId, $taxonomy, 'wplr_pt_posttype', 'wplr_pt_term_id' );
  }

  // Keyword removed for this media.
  function remove_media_tag( $mediaId, $tagId ) {
    global $wplr;
    $taxonomy = $this->get_taxonomy_tags();
    $collections = $wplr->get_collections_from_media( $mediaId );
    foreach ( $collections as $collectionId )
      $wplr->remove_taxonomy_from_posttype( $tagId, $collectionId,
        $taxonomy, 'wplr_pt_posttype', 'wplr_pt_term_id' );
  }

}

new WPLR_Extension_PostTypes;

?>
