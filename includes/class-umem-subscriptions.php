<?php
/**
 * Subscriptions (memberships) data model + access logic.
 *
 * Holds the runtime access checks the rest of the plugin relies on. Read
 * results are cached per-request to avoid repeated queries on a single page.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Subscriptions
 */
class UMEM_Subscriptions {

	/**
	 * Per-request cache of a user's active level IDs.
	 *
	 * @var array<int,int[]>
	 */
	private $cache = array();

	/**
	 * Get all subscriptions for a user.
	 *
	 * @param int $user_id User ID.
	 * @return object[]
	 */
	public function get_user_subscriptions( $user_id ) {
		global $wpdb;
		$table = umem_table( 'subscriptions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
				absint( $user_id )
			)
		);
	}

	/**
	 * Get the active level IDs for a user (cached per request).
	 *
	 * @param int $user_id User ID.
	 * @return int[]
	 */
	public function get_active_level_ids( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return array();
		}
		if ( isset( $this->cache[ $user_id ] ) ) {
			return $this->cache[ $user_id ];
		}

		global $wpdb;
		$table = umem_table( 'subscriptions' );

		// "active" or "trialing" both grant access. Cancelled-but-not-expired
		// memberships keep access until current_period_end passes.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT level_id FROM {$table}
				 WHERE user_id = %d
				 AND status IN ( 'active', 'trialing', 'cancelled' )
				 AND ( end_date IS NULL OR end_date = '0000-00-00 00:00:00' OR end_date > %s )",
				$user_id,
				current_time( 'mysql' )
			)
		);

		$ids = $rows ? array_map( 'intval', wp_list_pluck( $rows, 'level_id' ) ) : array();

		$this->cache[ $user_id ] = $ids;
		return $ids;
	}

	/**
	 * Whether the user has access to any (or specific) level(s).
	 *
	 * @param int   $user_id   User ID. Defaults to current user.
	 * @param int[] $level_ids Optional list to check. Empty = any active membership.
	 * @return bool
	 */
	public function user_has_access( $user_id = 0, $level_ids = array() ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// Admins with management cap always have access.
		if ( user_can( $user_id, 'umem_manage_memberships' ) ) {
			return true;
		}

		$active = $this->get_active_level_ids( $user_id );

		if ( empty( $level_ids ) ) {
			$has = ! empty( $active );
		} else {
			$level_ids = array_map( 'intval', (array) $level_ids );
			$has       = (bool) array_intersect( $active, $level_ids );
		}

		/**
		 * Filter the final access decision.
		 *
		 * @param bool  $has       Whether access is granted.
		 * @param int   $user_id   User being checked.
		 * @param int[] $level_ids Levels required.
		 */
		return (bool) apply_filters( 'umem_user_has_access', $has, $user_id, $level_ids );
	}

	/**
	 * Create or update a subscription row.
	 *
	 * @param array $data Subscription fields (already validated upstream).
	 * @param int   $id   Existing ID, or 0 to insert.
	 * @return int Subscription ID.
	 */
	public function save( $data, $id = 0 ) {
		global $wpdb;
		$table = umem_table( 'subscriptions' );
		$id    = absint( $id );
		$now   = current_time( 'mysql' );

		$fields = array(
			'user_id'                 => absint( $data['user_id'] ?? 0 ),
			'level_id'                => absint( $data['level_id'] ?? 0 ),
			'status'                  => sanitize_key( $data['status'] ?? 'active' ),
			'gateway'                 => sanitize_key( $data['gateway'] ?? '' ),
			'gateway_customer_id'     => sanitize_text_field( $data['gateway_customer_id'] ?? '' ),
			'gateway_subscription_id' => sanitize_text_field( $data['gateway_subscription_id'] ?? '' ),
			'start_date'              => ! empty( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : $now,
			'trial_end'              => ! empty( $data['trial_end'] ) ? sanitize_text_field( $data['trial_end'] ) : null,
			'current_period_end'     => ! empty( $data['current_period_end'] ) ? sanitize_text_field( $data['current_period_end'] ) : null,
			'cancel_at'              => ! empty( $data['cancel_at'] ) ? sanitize_text_field( $data['cancel_at'] ) : null,
			'end_date'               => ! empty( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : null,
			'updated_at'             => $now,
		);

		$formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

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

		// Bust cache for this user.
		unset( $this->cache[ $fields['user_id'] ] );

		/**
		 * Fires after a subscription is saved.
		 *
		 * @param int   $saved_id Subscription ID.
		 * @param array $fields   Saved fields.
		 */
		do_action( 'umem_subscription_saved', $saved_id, $fields );

		return $saved_id;
	}

	/**
	 * Count members by subscription status.
	 *
	 * @param string $status Status to count. Default 'active'.
	 * @return int
	 */
	public function count_by_status( $status = 'active' ) {
		global $wpdb;
		$table = umem_table( 'subscriptions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", sanitize_key( $status ) )
		);
	}

	/**
	 * Find a subscription by its Stripe subscription ID.
	 *
	 * @param string $sub_id Stripe subscription ID.
	 * @return object|null
	 */
	public function get_by_stripe_subscription( $sub_id ) {
		global $wpdb;
		$table  = umem_table( 'subscriptions' );
		$sub_id = sanitize_text_field( $sub_id );
		if ( '' === $sub_id ) {
			return null;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE gateway_subscription_id = %s LIMIT 1", $sub_id )
		);
	}

	/**
	 * Find the most recent subscription for a user + level.
	 *
	 * @param int $user_id  User ID.
	 * @param int $level_id Level ID.
	 * @return object|null
	 */
	public function get_user_level_subscription( $user_id, $level_id ) {
		global $wpdb;
		$table = umem_table( 'subscriptions' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND level_id = %d ORDER BY id DESC LIMIT 1",
				absint( $user_id ),
				absint( $level_id )
			)
		);
	}
}
