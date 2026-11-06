<?php
/**
 * Content restriction.
 *
 * Adds a per-post restriction metabox and enforces access on the front end.
 * Per-post settings are stored in post meta (key: _umem_restriction) so they
 * travel with the content and respect normal WP autosave/revision behaviour.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Content_Restriction
 */
class UMEM_Content_Restriction {

	/**
	 * Meta key used for per-post restriction settings.
	 */
	const META_KEY = '_umem_restriction';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post', array( $this, 'save_metabox' ), 10, 2 );

		// Quick Edit: column + inline field + save + script.
		add_action( 'admin_init', array( $this, 'register_list_columns' ) );
		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_quick_edit' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_quick_edit' ) );

		// Enforcement.
		add_action( 'template_redirect', array( $this, 'maybe_block_singular' ) );
		add_filter( 'the_content', array( $this, 'filter_content' ), 20 );
	}

	/**
	 * Register the "Access" admin column on supported post types.
	 *
	 * Runs on admin_init so custom post types (registered on init) are included.
	 *
	 * @return void
	 */
	public function register_list_columns() {
		foreach ( $this->get_supported_post_types() as $type ) {
			if ( 'page' === $type ) {
				add_filter( 'manage_pages_columns', array( $this, 'add_access_column' ) );
				add_action( 'manage_pages_custom_column', array( $this, 'render_access_column' ), 10, 2 );
			} else {
				add_filter( "manage_{$type}_posts_columns", array( $this, 'add_access_column' ) );
				add_action( "manage_{$type}_posts_custom_column", array( $this, 'render_access_column' ), 10, 2 );
			}
		}
	}

	/**
	 * Add the Access column before the Date column.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_access_column( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			if ( 'date' === $key ) {
				$new['umem_access'] = __( 'Access', 'unstoppable-memberships' );
			}
			$new[ $key ] = $label;
		}
		if ( ! isset( $new['umem_access'] ) ) {
			$new['umem_access'] = __( 'Access', 'unstoppable-memberships' );
		}
		return $new;
	}

	/**
	 * Render the Access column cell plus hidden data for Quick Edit JS.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_access_column( $column, $post_id ) {
		if ( 'umem_access' !== $column ) {
			return;
		}
		$settings = get_post_meta( $post_id, self::META_KEY, true );
		$access   = ( is_array( $settings ) && ! empty( $settings['access'] ) ) ? $settings['access'] : 'public';
		$levels   = ( is_array( $settings ) && ! empty( $settings['levels'] ) ) ? array_map( 'intval', (array) $settings['levels'] ) : array();

		echo esc_html( $this->access_label( $access, $levels ) );
		printf(
			'<div class="hidden umem-inline-data" id="umem_inline_%1$d" data-access="%2$s" data-levels="%3$s"></div>',
			(int) $post_id,
			esc_attr( $access ),
			esc_attr( implode( ',', $levels ) )
		);
	}

	/**
	 * Human label for an access setting.
	 *
	 * @param string $access Access mode.
	 * @param int[]  $levels Level IDs.
	 * @return string
	 */
	private function access_label( $access, $levels = array() ) {
		switch ( $access ) {
			case 'loggedin':
				return __( 'Logged-in users', 'unstoppable-memberships' );
			case 'members':
				return __( 'Any member', 'unstoppable-memberships' );
			case 'levels':
				$names = array();
				foreach ( $levels as $lid ) {
					$level = UMEM_Levels::get( $lid );
					if ( $level ) {
						$names[] = $level->name;
					}
				}
				return $names ? implode( ', ', $names ) : __( 'Specific levels', 'unstoppable-memberships' );
			default:
				return __( 'Public', 'unstoppable-memberships' );
		}
	}

	/**
	 * Render the Quick Edit access controls.
	 *
	 * @param string $column_name Column key (fires once per custom column).
	 * @param string $post_type   Post type of the list table.
	 * @return void
	 */
	public function quick_edit_box( $column_name, $post_type ) {
		if ( 'umem_access' !== $column_name ) {
			return;
		}
		if ( ! in_array( $post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}

		$levels = UMEM_Levels::get_all( array( 'status' => 'active' ) );
		wp_nonce_field( 'umem_quick_edit', 'umem_qe_nonce' );
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Membership access', 'unstoppable-memberships' ); ?></span>
					<select name="umem_qe[access]" class="umem-qe-access">
						<option value="public"><?php esc_html_e( 'Everyone (public)', 'unstoppable-memberships' ); ?></option>
						<option value="loggedin"><?php esc_html_e( 'Logged-in users only', 'unstoppable-memberships' ); ?></option>
						<option value="members"><?php esc_html_e( 'Any active member', 'unstoppable-memberships' ); ?></option>
						<option value="levels"><?php esc_html_e( 'Specific membership levels', 'unstoppable-memberships' ); ?></option>
					</select>
				</label>
				<div class="umem-qe-levels umem-hidden">
					<?php if ( $levels ) : ?>
						<?php foreach ( $levels as $level ) : ?>
							<label class="umem-check-line">
								<input type="checkbox" name="umem_qe[levels][]" value="<?php echo esc_attr( $level->id ); ?>" />
								<?php echo esc_html( $level->name ); ?>
							</label>
						<?php endforeach; ?>
					<?php else : ?>
						<em><?php esc_html_e( 'No levels yet.', 'unstoppable-memberships' ); ?></em>
					<?php endif; ?>
				</div>
				<p class="description umem-qe-note"><?php esc_html_e( 'Drip and custom message are set in the full editor.', 'unstoppable-memberships' ); ?></p>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Save the Quick Edit access fields, preserving drip/message settings.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function save_quick_edit( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['umem_qe_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['umem_qe_nonce'] ) ), 'umem_quick_edit' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['umem_qe'] ) || ! is_array( $_POST['umem_qe'] ) ) {
			return;
		}

		$raw = wp_unslash( $_POST['umem_qe'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.

		$access = isset( $raw['access'] ) ? sanitize_key( $raw['access'] ) : 'public';
		$access = in_array( $access, array( 'public', 'loggedin', 'members', 'levels' ), true ) ? $access : 'public';
		$levels = isset( $raw['levels'] ) ? array_map( 'absint', (array) $raw['levels'] ) : array();

		// Preserve drip/message configured in the full editor.
		$existing = get_post_meta( $post_id, self::META_KEY, true );
		$existing = is_array( $existing ) ? $existing : array();

		$clean = array(
			'access'    => $access,
			'levels'    => 'levels' === $access ? $levels : array(),
			'drip_type' => isset( $existing['drip_type'] ) ? sanitize_key( $existing['drip_type'] ) : 'none',
			'drip_days' => isset( $existing['drip_days'] ) ? absint( $existing['drip_days'] ) : 0,
			'drip_date' => isset( $existing['drip_date'] ) ? sanitize_text_field( $existing['drip_date'] ) : '',
			'message'   => isset( $existing['message'] ) ? wp_kses_post( $existing['message'] ) : '',
			'redirect_page' => isset( $existing['redirect_page'] ) ? absint( $existing['redirect_page'] ) : 0,
		);

		if ( 'public' === $clean['access'] && 'none' === $clean['drip_type'] ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			update_post_meta( $post_id, self::META_KEY, $clean );
		}
	}

	/**
	 * Enqueue the Quick Edit population script on supported list tables.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_quick_edit( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, $this->get_supported_post_types(), true ) ) {
			return;
		}

		// Shared styles for the metabox + Quick Edit controls (both screens).
		if ( in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
			wp_enqueue_style(
				'umem-restrict',
				UMEM_PLUGIN_URL . 'assets/css/umem-restrict.css',
				array(),
				UMEM_VERSION
			);
		}

		// Metabox: reveal the level picker when "Specific levels" is chosen.
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_script(
				'umem-restrict',
				UMEM_PLUGIN_URL . 'assets/js/umem-restrict.js',
				array(),
				UMEM_VERSION,
				true
			);
		}

		// Quick Edit population script (list table only).
		if ( 'edit.php' === $hook ) {
			wp_enqueue_script(
				'umem-quick-edit',
				UMEM_PLUGIN_URL . 'assets/js/umem-quick-edit.js',
				array( 'jquery', 'inline-edit-post' ),
				UMEM_VERSION,
				true
			);
		}
	}

	/**
	 * Post types that get the restriction metabox.
	 *
	 * @return string[]
	 */
	private function get_supported_post_types() {
		$types = get_post_types( array( 'public' => true ), 'names' );
		unset( $types['attachment'] );
		/**
		 * Filter the post types that support content restriction.
		 *
		 * @param string[] $types Post type names.
		 */
		return apply_filters( 'umem_restriction_post_types', array_values( $types ) );
	}

	/**
	 * Register the metabox.
	 *
	 * @return void
	 */
	public function add_metabox() {
		foreach ( $this->get_supported_post_types() as $type ) {
			add_meta_box(
				'umem_restriction',
				__( 'Membership Access', 'unstoppable-memberships' ),
				array( $this, 'render_metabox' ),
				$type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the metabox UI.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_metabox( $post ) {
		wp_nonce_field( 'umem_save_restriction', 'umem_restriction_nonce' );

		$settings = get_post_meta( $post->ID, self::META_KEY, true );
		$settings = wp_parse_args(
			is_array( $settings ) ? $settings : array(),
			array(
				'access'    => 'public',
				'levels'    => array(),
				'drip_type' => 'none',
				'drip_days' => 0,
				'drip_date' => '',
				'message'   => '',
				'redirect_page' => 0,
			)
		);

		$levels   = UMEM_Levels::get_all( array( 'status' => 'active' ) );
		$access   = $settings['access'];
		$selected = array_map( 'intval', (array) $settings['levels'] );
		?>
		<p>
			<label for="umem_access"><strong><?php esc_html_e( 'Who can view this?', 'unstoppable-memberships' ); ?></strong></label>
			<select name="umem_restriction[access]" id="umem_access" class="widefat">
				<option value="public" <?php selected( $access, 'public' ); ?>><?php esc_html_e( 'Everyone (public)', 'unstoppable-memberships' ); ?></option>
				<option value="loggedin" <?php selected( $access, 'loggedin' ); ?>><?php esc_html_e( 'Logged-in users only', 'unstoppable-memberships' ); ?></option>
				<option value="members" <?php selected( $access, 'members' ); ?>><?php esc_html_e( 'Any active member', 'unstoppable-memberships' ); ?></option>
				<option value="levels" <?php selected( $access, 'levels' ); ?>><?php esc_html_e( 'Specific membership levels', 'unstoppable-memberships' ); ?></option>
			</select>
		</p>

		<div class="umem-level-picker<?php echo 'levels' === $access ? '' : ' umem-hidden'; ?>">
			<p><strong><?php esc_html_e( 'Allowed levels', 'unstoppable-memberships' ); ?></strong></p>
			<?php if ( $levels ) : ?>
				<?php foreach ( $levels as $level ) : ?>
					<label class="umem-check-line">
						<input type="checkbox" name="umem_restriction[levels][]" value="<?php echo esc_attr( $level->id ); ?>" <?php checked( in_array( (int) $level->id, $selected, true ) ); ?> />
						<?php echo esc_html( $level->name ); ?>
					</label>
				<?php endforeach; ?>
			<?php else : ?>
				<em><?php esc_html_e( 'No levels yet. Create one first.', 'unstoppable-memberships' ); ?></em>
			<?php endif; ?>
		</div>

		<hr />
		<p>
			<label for="umem_drip_type"><strong><?php esc_html_e( 'Drip schedule', 'unstoppable-memberships' ); ?></strong></label>
			<select name="umem_restriction[drip_type]" id="umem_drip_type" class="widefat">
				<option value="none" <?php selected( $settings['drip_type'], 'none' ); ?>><?php esc_html_e( 'No drip (available immediately)', 'unstoppable-memberships' ); ?></option>
				<option value="days" <?php selected( $settings['drip_type'], 'days' ); ?>><?php esc_html_e( 'Days after signup', 'unstoppable-memberships' ); ?></option>
				<option value="date" <?php selected( $settings['drip_type'], 'date' ); ?>><?php esc_html_e( 'Fixed date', 'unstoppable-memberships' ); ?></option>
			</select>
		</p>
		<p>
			<input type="number" min="0" name="umem_restriction[drip_days]" value="<?php echo esc_attr( $settings['drip_days'] ); ?>" placeholder="<?php esc_attr_e( 'Days', 'unstoppable-memberships' ); ?>" class="umem-half-input" />
			<input type="date" name="umem_restriction[drip_date]" value="<?php echo esc_attr( $settings['drip_date'] ); ?>" class="umem-half-input" />
		</p>

		<p>
			<label for="umem_message"><strong><?php esc_html_e( 'Custom restricted message', 'unstoppable-memberships' ); ?></strong></label>
			<textarea name="umem_restriction[message]" id="umem_message" class="widefat" rows="3" placeholder="<?php esc_attr_e( 'Leave blank to use the global default.', 'unstoppable-memberships' ); ?>"><?php echo esc_textarea( $settings['message'] ); ?></textarea>
		</p>

		<p>
			<label for="umem_redirect_page"><strong><?php esc_html_e( 'Redirect non-members to', 'unstoppable-memberships' ); ?></strong></label>
			<?php
			wp_dropdown_pages(
				array(
					'name'              => 'umem_restriction[redirect_page]',
					'id'                => 'umem_redirect_page',
					'class'             => 'widefat',
					'selected'          => (int) $settings['redirect_page'],
					'show_option_none'  => __( '— Use global setting —', 'unstoppable-memberships' ),
					'option_none_value' => 0,
				)
			);
			?>
			<span class="description"><?php esc_html_e( 'Overrides the site-wide Content Restriction setting for this item only. When set, non-members are redirected here before the page renders (reliable for Elementor pages).', 'unstoppable-memberships' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Persist metabox values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save_metabox( $post_id, $post ) {
		// Bail on autosave / AJAX / revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['umem_restriction_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['umem_restriction_nonce'] ) ), 'umem_save_restriction' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['umem_restriction'] ) || ! is_array( $_POST['umem_restriction'] ) ) {
			return;
		}

		$raw = wp_unslash( $_POST['umem_restriction'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field-by-field below.

		$access = isset( $raw['access'] ) ? sanitize_key( $raw['access'] ) : 'public';
		$access = in_array( $access, array( 'public', 'loggedin', 'members', 'levels' ), true ) ? $access : 'public';

		$clean = array(
			'access'    => $access,
			'levels'    => isset( $raw['levels'] ) ? array_map( 'absint', (array) $raw['levels'] ) : array(),
			'drip_type' => isset( $raw['drip_type'] ) ? sanitize_key( $raw['drip_type'] ) : 'none',
			'drip_days' => isset( $raw['drip_days'] ) ? absint( $raw['drip_days'] ) : 0,
			'drip_date' => isset( $raw['drip_date'] ) ? sanitize_text_field( $raw['drip_date'] ) : '',
			'message'   => isset( $raw['message'] ) ? wp_kses_post( $raw['message'] ) : '',
			'redirect_page' => isset( $raw['redirect_page'] ) ? absint( $raw['redirect_page'] ) : 0,
		);

		if ( 'public' === $clean['access'] && 'none' === $clean['drip_type'] ) {
			delete_post_meta( $post_id, self::META_KEY );
		} else {
			update_post_meta( $post_id, self::META_KEY, $clean );
		}
	}

	/**
	 * Decide whether the current user can access a given post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function can_access( $post_id ) {
		$settings = get_post_meta( $post_id, self::META_KEY, true );

		// No rule = public.
		if ( ! is_array( $settings ) || empty( $settings['access'] ) || 'public' === $settings['access'] ) {
			return true;
		}

		$user_id = get_current_user_id();

		// Admins always pass.
		if ( $user_id && user_can( $user_id, 'umem_manage_memberships' ) ) {
			return true;
		}

		$access = $settings['access'];

		if ( 'loggedin' === $access ) {
			$pass = is_user_logged_in();
		} elseif ( 'members' === $access ) {
			$pass = umem_user_has_access( $user_id );
		} elseif ( 'levels' === $access ) {
			$pass = umem_user_has_access( $user_id, $settings['levels'] );
		} else {
			$pass = true;
		}

		// Drip check only matters once base access passes.
		if ( $pass && isset( $settings['drip_type'] ) && 'none' !== $settings['drip_type'] ) {
			$pass = $this->drip_available( $user_id, $settings );
		}

		/**
		 * Filter access to a single restricted post.
		 *
		 * @param bool  $pass     Whether access is granted.
		 * @param int   $post_id  Post ID.
		 * @param array $settings Restriction settings.
		 */
		return (bool) apply_filters( 'umem_can_access_post', $pass, $post_id, $settings );
	}

	/**
	 * Whether dripped content is unlocked yet for this user.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $settings Restriction settings.
	 * @return bool
	 */
	private function drip_available( $user_id, $settings ) {
		if ( 'date' === $settings['drip_type'] && ! empty( $settings['drip_date'] ) ) {
			return current_time( 'timestamp' ) >= strtotime( $settings['drip_date'] );
		}

		if ( 'days' === $settings['drip_type'] && $user_id ) {
			$subs = UMEM_Plugin::instance()->subscriptions->get_user_subscriptions( $user_id );
			if ( empty( $subs ) ) {
				return false;
			}
			// Earliest signup wins.
			$start = min( array_map( fn( $s ) => strtotime( $s->start_date ), $subs ) );
			$unlock = $start + ( (int) $settings['drip_days'] * DAY_IN_SECONDS );
			return current_time( 'timestamp' ) >= $unlock;
		}

		return true;
	}

	/**
	 * Hard-block restricted singular views (redirect or replace).
	 *
	 * @return void
	 */
	public function maybe_block_singular() {
		if ( ! is_singular() ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id || $this->can_access( $post_id ) ) {
			return;
		}

		do_action( 'umem_blocked_singular', $post_id );

		// Per-page redirect override: if this item names its own target, redirect
		// there regardless of the global mode. Otherwise fall back to the global
		// setting (redirect mode + global target, or replace-in-place).
		$settings    = get_post_meta( $post_id, self::META_KEY, true );
		$page_target = ( is_array( $settings ) && ! empty( $settings['redirect_page'] ) ) ? (int) $settings['redirect_page'] : 0;

		if ( $page_target ) {
			$target_id   = $page_target;
		} elseif ( 'redirect' === umem_get_option( 'restrict_mode', 'replace' ) ) {
			$target_id   = (int) umem_get_option( 'restrict_redirect_page', 0 );
		} else {
			// Replace mode and no per-page override: let filter_content() handle it.
			return;
		}

		// Never redirect the target page onto itself (prevents an endless loop).
		if ( $target_id && $target_id === $post_id ) {
			return;
		}

		$current = get_permalink( $post_id );

		$target_url = $target_id ? get_permalink( $target_id ) : '';
		if ( $target_url ) {
			$destination = add_query_arg( 'redirect_to', rawurlencode( $current ), $target_url );
		} else {
			// No valid target (none set, or the page was trashed) -> login page.
			$destination = wp_login_url( $current );
		}

		/**
		 * Filter where a blocked visitor is redirected.
		 *
		 * @param string $destination Redirect URL.
		 * @param int    $post_id     Restricted post ID.
		 */
		$destination = apply_filters( 'umem_restriction_redirect_url', $destination, $post_id );

		if ( $destination ) {
			wp_safe_redirect( $destination );
			exit;
		}
	}

	/**
	 * Replace restricted content with the access message.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function filter_content( $content ) {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_id = get_the_ID();
		if ( ! $post_id || $this->can_access( $post_id ) ) {
			return $content;
		}

		$settings = get_post_meta( $post_id, self::META_KEY, true );
		$message  = ( is_array( $settings ) && ! empty( $settings['message'] ) )
			? $settings['message']
			: umem_get_option( 'restricted_message', __( 'This content is for members only.', 'unstoppable-memberships' ) );

		ob_start();
		umem_get_template( 'restricted', array( 'message' => $message ) );
		return ob_get_clean();
	}
}
