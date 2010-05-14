<?php
/**
 * The custom background script.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * The custom background class.
 *
 * @since 3.0.0
 * @package WordPress
 * @subpackage Administration
 */
class Custom_Background {

	/**
	 * Callback for administration header.
	 *
	 * @var callback
	 * @since unknown
	 * @access private
	 */
	var $admin_header_callback;

	/**
	 * Callback for header div.
	 *
	 * @var callback
	 * @since 3.0.0
	 * @access private
	 */
	var $admin_image_div_callback;

	/**
	 * PHP4 Constructor - Register administration header callback.
	 *
	 * @since 3.0.0
	 * @param callback $admin_header_callback
	 * @param callback $admin_image_div_callback Optional custom image div output callback.
	 * @return Custom_Background
	 */
	function Custom_Background($admin_header_callback = '', $admin_image_div_callback = '') {
		$this->admin_header_callback = $admin_header_callback;
		$this->admin_image_div_callback = $admin_image_div_callback;
	}

	/**
	 * Set up the hooks for the Custom Background admin page.
	 *
	 * @since 3.0.0
	 */
	function init() {
		if ( ! current_user_can('edit_theme_options') )
			return;

		$page = add_theme_page(__('Background'), __('Background'), 'edit_theme_options', 'custom-background', array(&$this, 'admin_page'));

		add_action("load-$page", array(&$this, 'admin_load'));
		add_action("load-$page", array(&$this, 'take_action'), 49);
		add_action("load-$page", array(&$this, 'handle_upload'), 49);

		if ( $this->admin_header_callback )
			add_action("admin_head-$page", $this->admin_header_callback, 51);
	}

	/**
	 * Set up the enqueue for the CSS & JavaScript files.
	 *
	 * @since 3.0.0
	 */
	function admin_load() {
		wp_enqueue_script('custom-background');
		wp_enqueue_style('farbtastic');
	}

	/**
	 * Execute custom background modification.
	 *
	 * @since 3.0.0
	 */
	function take_action() {

		if ( empty($_POST) )
			return;

		check_admin_referer('custom-background');

		if ( isset($_POST['reset-background']) ) {
			remove_theme_mod( 'background_image' );
			return;
		}
		if ( isset($_POST['remove-background']) ) {
			// @TODO: Uploaded files are not removed here.
			set_theme_mod('background_image', '');
		}

		if ( isset($_POST['background-repeat']) ) {
			if ( in_array($_POST['background-repeat'], array('repeat', 'no-repeat', 'repeat-x', 'repeat-y')) )
				$repeat = $_POST['background-repeat'];
			else
				$repeat = 'repeat';
			set_theme_mod('background_repeat', $repeat);
		}
		if ( isset($_POST['background-position']) ) {
			if ( in_array($_POST['background-position'], array('center', 'right', 'left')) )
				$position = $_POST['background-position'];
			else
				$position = 'left';
			set_theme_mod('background_position', $position);
		}
		if ( isset($_POST['background-attachment']) ) {
			if ( in_array($_POST['background-attachment'], array('fixed', 'scroll')) )
				$attachment = $_POST['background-attachment'];
			else
				$attachment = 'fixed';
			set_theme_mod('background_attachment', $attachment);
		}
		if ( isset($_POST['background-color']) ) {
			$color = preg_replace('/[^0-9a-fA-F]/', '', $_POST['background-color']);
			if ( strlen($color) == 6 || strlen($color) == 3 )
				set_theme_mod('background_color', $color);
			else
				set_theme_mod('background_color', '');
		}

		$this->updated = true;
	}

	/**
	 * Display the custom background page.
	 *
	 * @since 3.0.0
	 */
	function admin_page() {
?>
<div class="wrap" id="custom-background">
<?php screen_icon(); ?>
<h2><?php _e('Custom Background'); ?></h2>
<?php if ( !empty($this->updated) ) { ?>
<div id="message" class="updated">
<p><?php printf( __( 'Background updated. <a href="%s">Visit your site</a> to see how it looks.' ), home_url( '/' ) ); ?></p>
</div>
<?php }

	if ( $this->admin_image_div_callback ) {
		call_user_func($this->admin_image_div_callback);
	} else {
?>
<h3><?php _e('Background Preview'); ?></h3>
<table class="form-table">
<tbody>
<tr valign="top">
<th scope="row"><?php _e('Current Background'); ?></th>
<td>
<?php
$background_styles = "background-color: #" . get_background_color() . ";";

if ( get_background_image() ) { 
	$background_styles .= "
	background-image: url(" . get_theme_mod('background_image_thumb', '') . ");
	background-repeat: ". get_theme_mod('background_repeat', 'no-repeat') . ";
	background-position: top ". get_theme_mod('background_position', 'left') . ";
	background-attachment: " . get_theme_mod('background_position', 'fixed') . ";
	";
}
?>
<div id="custom-background-image" style="<?php echo $background_styles; ?>">
<?php if ( get_background_image() ) { ?>
<img class="custom-background-image" src="<?php echo get_theme_mod('background_image_thumb', ''); ?>" style="visibility:hidden;" /><br />
<img class="custom-background-image" src="<?php echo get_theme_mod('background_image_thumb', ''); ?>" style="visibility:hidden;" />
<?php } ?>
<br class="clear" />
</div>
<?php } ?>
</td>
</tr>
<?php if ( get_background_image() ) : ?>
<tr valign="top">
<th scope="row"><?php _e('Remove Image'); ?></th>
<td><p><?php _e('This will remove the background image. You will not be able to restore any customizations.') ?></p>
<form method="post" action="">
<?php wp_nonce_field('custom-background'); ?>
<input type="submit" class="button" name="remove-background" value="<?php esc_attr_e('Remove Background'); ?>" />
</form>
</td>
</tr>
<?php endif; ?>

<?php if ( defined( 'BACKGROUND_IMAGE' ) ) : // Show only if a default background image exists ?>
<tr valign="top">
<th scope="row"><?php _e('Restore Original Image'); ?></th>
<td><p><?php _e('This will restore the original background image. You will not be able to restore any customizations.') ?></p>
<form method="post" action="">
<?php wp_nonce_field('custom-background'); ?>
<input type="submit" class="button" name="reset-background" value="<?php esc_attr_e('Restore Original Image'); ?>" />
</form>
</td>
</tr>
</form>
<?php endif; ?>
<tr valign="top">
<th scope="row"><?php _e('Upload Image'); ?></th>
<td><form enctype="multipart/form-data" id="uploadForm" method="POST" action="">
<label for="upload"><?php _e('Choose an image from your computer:'); ?></label><br /><input type="file" id="upload" name="import" />
<input type="hidden" name="action" value="save" />
<?php wp_nonce_field('custom-background') ?>
<p class="submit">
<input type="submit" value="<?php esc_attr_e('Upload'); ?>" />
</p>
</form>
</td>
</tr>
</tbody>
</table>

<h3><?php _e('Display Options') ?></h3>
<form method="post" action="">
<table class="form-table">
<tbody>
<tr valign="top">
<th scope="row"><?php _e( 'Background Color' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Background Color' ); ?></span></legend>
<input type="text" name="background-color" id="background-color" value="#<?php echo esc_attr(get_background_color()) ?>" />
<input type="button" class="button" value="<?php esc_attr_e('Select a Color'); ?>" id="pickcolor" />

<div id="colorPickerDiv" style="z-index: 100; background:#eee; border:1px solid #ccc; position:absolute; display:none;"></div>
</fieldset></td>
</tr>

<tr valign="top">
<th scope="row"><?php _e( 'Background Position' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Background Position' ); ?></span></legend>
<label>
<input name="background-position" type="radio" value="left" <?php checked('left', get_theme_mod('background_position', 'left')); ?> />
<?php _e('Left') ?>
</label>
<label>
<input name="background-position" type="radio" value="center" <?php checked('center', get_theme_mod('background_position', 'left')); ?> />
<?php _e('Center') ?>
</label>
<label>
<input name="background-position" type="radio" value="right" <?php checked('right', get_theme_mod('background_position', 'left')); ?> />
<?php _e('Right') ?>
</label>
</fieldset></td>
</tr>

<tr valign="top">
<th scope="row"><?php _e( 'Repeat' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Repeat' ); ?></span></legend>
<label>
<select name="background-repeat">
	<option value="no-repeat" <?php selected('no-repeat', get_theme_mod('background_repeat', 'repeat')); ?> ><?php _e('No repeat'); ?></option>
	<option value="repeat" <?php selected('repeat', get_theme_mod('background_repeat', 'repeat')); ?>><?php _e('Tile'); ?></option>
	<option value="repeat-x" <?php selected('repeat-x', get_theme_mod('background_repeat', 'repeat')); ?>><?php _e('Tile Horizontally'); ?></option>
	<option value="repeat-y" <?php selected('repeat-y', get_theme_mod('background_repeat', 'repeat')); ?>><?php _e('Tile Vertically'); ?></option>
</select>
</label>
</fieldset></td>
</tr>

<tr valign="top">
<th scope="row"><?php _e( 'Attachment' ); ?></th>
<td><fieldset><legend class="screen-reader-text"><span><?php _e( 'Attachment' ); ?></span></legend>
<label>
<input name="background-attachment" type="radio" value="scroll" <?php checked('scroll', get_theme_mod('background_attachment', 'fixed')); ?> />
<?php _e('Scroll') ?>
</label>
<label>
<input name="background-attachment" type="radio" value="fixed" <?php checked('fixed', get_theme_mod('background_attachment', 'fixed')); ?> />
<?php _e('Fixed') ?>
</label>
</fieldset></td>
</tr>

</tbody>
</table>

<?php wp_nonce_field('custom-background'); ?>
<p class="submit"><input type="submit" class="button-primary" name="save-background-options" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
</form>

</div>
<?php
	}

	/**
	 * Handle a Image upload for the background image.
	 *
	 * @since 3.0.0
	 */
	function handle_upload() {

		if ( empty($_FILES) )
			return;

		check_admin_referer('custom-background');
		$overrides = array('test_form' => false);
		$file = wp_handle_upload($_FILES['import'], $overrides);

		if ( isset($file['error']) )
			wp_die( $file['error'] );

		$url = $file['url'];
		$type = $file['type'];
		$file = $file['file'];
		$filename = basename($file);

		// Construct the object array
		$object = array(
			'post_title' => $filename,
			'post_content' => $url,
			'post_mime_type' => $type,
			'guid' => $url
		);

		// Save the data
		$id = wp_insert_attachment($object, $file);

		// Add the meta-data
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );

		set_theme_mod('background_image', esc_url($url));

		$thumbnail = wp_get_attachment_image_src( $id, 'thumbnail' );
		set_theme_mod('background_image_thumb', esc_url( $thumbnail[0] ) );
		
		set_theme_mod('background_position', get_theme_mod('background_position', 'left') );
		set_theme_mod('background_repeat', get_theme_mod('background_repeat', 'tile') );
		set_theme_mod('background-attachment',  get_theme_mod('background_position', 'fixed') );

		do_action('wp_create_file_in_uploads', $file, $id); // For replication
		$this->updated = true;
	}

}
?>
