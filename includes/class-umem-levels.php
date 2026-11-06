<?php
/**
 * Membership levels data model (CRUD).
 *
 * All database access goes through prepared statements. This class is the
 * single source of truth for reading/writing the levels table so callers
 * never touch $wpdb directly.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Levels
 */
class UMEM_Levels {

	/**
	 * Get a single level by ID.
	 *
	 * @param int $id Level ID.
	 * @return object|null
	 */
	public static function get( $id ) {
		global $wpdb;
		$table = umem_table( 'levels' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) )
		);
	}

	/**
	 * Get a single level by slug.
	 *
	 * @param string $slug Level slug.
	 * @return object|null
	 */
	public static function get_by_slug( $slug ) {
		global $wpdb;
		$table = umem_table( 'levels' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE slug = %s", sanitize_title( $slug ) )
		);
	}

	/**
	 * Get all levels matching args.
	 *
	 * @param array $args { Optional. Query args.
	 *     @type string $status  Filter by status. Default 'active'.
	 *     @type string $orderby Column to order by. Default 'menu_order'.
	 *     @type string $order   ASC|DESC. Default 'ASC'.
	 * }
	 * @return object[]
	 */
	public static function get_all( $args = array() ) {
		global $wpdb;
		$table = umem_table( 'levels' );

		$args = wp_parse_args(
			$args,
			array(
				'status'  => 'active',
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			)
		);

		// Whitelist orderby/order to prevent injection (can't be parameterised).
		$allowed_orderby = array( 'id', 'name', 'price', 'menu_order', 'created_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'menu_order';
		$order           = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

		if ( 'any' === $args['status'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY {$orderby} {$order}" );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = %s ORDER BY {$orderby} {$order}",
				sanitize_key( $args['status'] )
			)
		);
	}

	/**
	 * Insert or update a level.
	 *
	 * @param array $data Raw, untrusted input (already unslashed).
	 * @param int   $id   Existing ID to update, or 0 to insert.
	 * @return int|WP_Error New/updated level ID on success.
	 */
	public static function save( $data, $id = 0 ) {
		global $wpdb;
		$table = umem_table( 'levels' );
		$id    = absint( $id );

		$name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		if ( '' === $name ) {
			return new WP_Error( 'umem_missing_name', __( 'A membership level needs a name.', 'unstoppable-memberships' ) );
		}

		$slug = isset( $data['slug'] ) && '' !== $data['slug'] ? sanitize_title( $data['slug'] ) : sanitize_title( $name );

		$billing_type = isset( $data['billing_type'] ) ? sanitize_key( $data['billing_type'] ) : 'free';
		$billing_type = in_array( $billing_type, array( 'free', 'onetime', 'recurring' ), true ) ? $billing_type : 'free';

		$interval = isset( $data['billing_interval'] ) ? sanitize_key( $data['billing_interval'] ) : 'month';
		$interval = in_array( $interval, array( 'day', 'week', 'month', 'year' ), true ) ? $interval : 'month';

		$roles = '';
		if ( ! empty( $data['roles'] ) ) {
			$roles = is_array( $data['roles'] ) ? implode( ',', array_map( 'sanitize_key', $data['roles'] ) ) : sanitize_text_field( $data['roles'] );
		}

		$now = current_time( 'mysql' );

		$fields = array(
			'name'                   => $name,
			'slug'                   => $slug,
			'description'            => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
			'price'                  => isset( $data['price'] ) ? round( (float) $data['price'], 2 ) : 0,
			'currency'               => isset( $data['currency'] ) ? strtoupper( substr( sanitize_text_field( $data['currency'] ), 0, 3 ) ) : 'USD',
			'billing_type'           => $billing_type,
			'billing_interval'       => $interval,
			'billing_interval_count' => isset( $data['billing_interval_count'] ) ? max( 1, absint( $data['billing_interval_count'] ) ) : 1,
			'trial_days'             => isset( $data['trial_days'] ) ? absint( $data['trial_days'] ) : 0,
			'signup_fee'             => isset( $data['signup_fee'] ) ? round( (float) $data['signup_fee'], 2 ) : 0,
			'grace_days'             => isset( $data['grace_days'] ) ? absint( $data['grace_days'] ) : 0,
			'roles'                  => $roles,
			'badge'                  => isset( $data['badge'] ) ? sanitize_text_field( $data['badge'] ) : '',
			'status'                 => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'active',
			'menu_order'             => isset( $data['menu_order'] ) ? (int) $data['menu_order'] : 0,
			'stripe_price_id'        => isset( $data['stripe_price_id'] ) ? sanitize_text_field( $data['stripe_price_id'] ) : '',
			'updated_at'             => $now,
		);

		$formats = array( '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%d', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s' );

		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table, $fields, array( 'id' => $id ), $formats, array( '%d' ) );
			$saved_id = $id;
		} else {
			$fields['created_at'] = $now;
			$formats[]            = '%s';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert( $table, $fields, $formats );
			$saved_id = (int) $wpdb->insert_id;
		}

		/**
		 * Fires after a membership level is created or updated.
		 *
		 * @param int   $saved_id Level ID.
		 * @param array $fields   Saved field values.
		 * @param bool  $is_new   Whether this was an insert.
		 */
		do_action( 'umem_level_saved', $saved_id, $fields, ( 0 === $id ) );

		return $saved_id;
	}

	/**
	 * Delete a level.
	 *
	 * @param int $id Level ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		global $wpdb;
		$table = umem_table( 'levels' );
		$id    = absint( $id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( $result ) {
			/**
			 * Fires after a membership level is deleted.
			 *
			 * @param int $id Level ID.
			 */
			do_action( 'umem_level_deleted', $id );
		}

		return (bool) $result;
	}

	/**
	 * Count members on a level (active subscriptions).
	 *
	 * @param int $level_id Level ID.
	 * @return int
	 */
	public static function count_members( $level_id ) {
		global $wpdb;
		$table = umem_table( 'subscriptions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE level_id = %d AND status = 'active'",
				absint( $level_id )
			)
		);
	}
}
