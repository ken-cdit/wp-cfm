<?php

$configuration = $this->registry->get_configuration_items();
$configuration = $this->helper->group_items( $configuration );

?>

<div class="wrap">
	<h2>WP-CFM</h2>

	<?php if ( defined( 'WPCFM_CURRENT_ENV' ) && ! empty( WPCFM_CURRENT_ENV ) ) : ?>
	<div class="wpcfm-info">
		<?php _e( 'Current environment', 'wp-cfm' ); ?>: <code><?php echo ucfirst( WPCFM_CURRENT_ENV ); ?></code>
	</div>

	<!-- Environment Switcher -->
	<div class="wpcfm-env-switch">
		<?php _e( 'Switch config to', 'wp-cfm' ); ?>: <select name="wpcfm_env_switch" id="wpcfm_env_switch">
			<?php foreach ( WPCFM_REGISTER_MULTI_ENV as $env ) : ?>
				<option value="<?php echo $env; ?>"
					<?php if ( ( WPCFM_CURRENT_ENV == $env && ! defined( 'WPCFM_COMPARE_ENV' ) ) || ( defined( 'WPCFM_COMPARE_ENV' ) && WPCFM_COMPARE_ENV == $env ) ) : ?>
						<?php echo 'selected="selected"'; ?>
				<?php endif ?>
				><?php echo ucfirst( $env ); ?></option>
			<?php endforeach ?>
		</select>
	</div>
<?php endif; ?>

<div class="wpcfm-bundles">
	<div class="wpcfm-action-buttons">
		<div style="float:right">
			<span class="wpcfm-response"></span>
			<a class="button-primary wpcfm-save"><?php _e( 'Save Changes', 'wp-cfm' ); ?></a>
		</div>
		<a class="button add-bundle"><?php _e( 'Add Bundle', 'wp-cfm' ); ?></a>
		<div class="clear"></div>
	</div>

	<div class="bundle-row row-all" data-bundle="all">
		<div class="bundle-header">
			<div class="bundle-actions">
				<a class="button diff-bundle" title="Compare differences"><?php _e( 'Diff', 'wp-cfm' ); ?></a> &nbsp;
				<?php if ( ! $this->is_push_disabled() ): ?>
				<a class="button push-bundle" title="Write database changes to the filesystem"><?php _e( 'Push', 'wp-cfm' ); ?></a> &nbsp;
				<?php endif ?>
				<?php if ( ! $this->is_pull_disabled() ): ?>
				<a class="button pull-bundle" title="Import file changes into the database"><?php _e( 'Pull', 'wp-cfm' ); ?></a>
				<?php endif ?>
			</div>
			<div class="bundle-toggle">All Bundles</div>
			<div class="clear"></div>
		</div>
	</div>
</div>

<?php if ( defined( 'WPCFM_HIDE_ATTRIBUTION' ) && WPCFM_HIDE_ATTRIBUTION === true ): ?>
<div class="attribution">
	Created by <a href="http://forumone.com/" target="_blank">Forum One</a>
</div>
<?php endif ?>

<!-- clone settings -->

<div class="bundles-hidden">
	<div class="bundle-row" data-bundle="new_bundle">
		<div class="bundle-header">
			<div class="bundle-actions">
				<span class="no-actions">Save to see actions</span>
				<a class="button diff-bundle" title="Compare differences"><?php _e( 'Diff', 'wp-cfm' ); ?></a> &nbsp;
				<?php if ( ! $this->is_push_disabled() ): ?>
				<a class="button push-bundle disabled" title="Write database changes to the filesystem"><?php _e( 'Push', 'wp-cfm' ); ?></a> &nbsp;
				<?php endif ?>
				<?php if ( ! $this->is_pull_disabled() ): ?>
				<a class="button pull-bundle disabled" title="Import file changes into the database"><?php _e( 'Pull', 'wp-cfm' ); ?></a>
				<?php endif ?>
			</div>
			<div class="bundle-toggle">New bundle</div>
			<div class="clear"></div>
		</div>
		<div class="bundle-row-inner">
			<input type="text" class="bundle-label" value="New bundle" />
			<input type="text" class="bundle-name" value="new_bundle" />
			<a href="#" class="hide-registered">Hide registered</a>
			<a href="#" class="show-all hidden">Show all</a>
			<div class="bundle-select-wrapper">
				<select class="bundle-select" multiple="multiple">
					<?php foreach ( $configuration as $group => $config ) : ?>
						<optgroup label="<?php echo $group; ?>">
							<?php foreach ( $config as $key => $data ) : ?>
								<?php $label = isset( $data['label'] ) ? $data['label'] : $key; ?>
								<option value="<?php echo $key; ?>"><?php echo $label; ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endforeach; ?>
				</select>
			</div>
			<a class="remove-bundle"><?php _e( 'Delete Bundle', 'wp-cfm' ); ?></a>
			<a class="download-bundle hidden"><?php _e( 'Download', 'wp-cfm' ); ?></a>
			<div class="clear"></div>
		</div>
	</div>
</div>
</div>

<!-- diff modal -->

<div class="media-modal">
	<a class="media-modal-close"><span class="media-modal-icon"></span></a>
	<div class="media-modal-content">
		<div class="media-frame">
			<div class="media-frame-title">
				<h1>Diff Viewer</h1>
			</div>
			<div class="media-frame-router">
				<div class="media-router">
					Compare file and database versions. Changes marked in <span style="background:#c6ffc6">GREEN</span> exist in the database but not the filesystem.
				</div>
			</div>
			<div class="media-frame-content">
				<div class="wpcfm-diff">
					<pre class="original"></pre>
					<pre class="changed"></pre>
					<pre class="diff"></pre>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="media-modal-backdrop"></div>
