<?php
namespace CF7ON\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Opt-in, bounded retention maintenance. No existing data is deleted unless
 * an administrator explicitly selects a non-zero retention period.
 */
final class MaintenanceService {
    private const HOOK = 'cf7on_daily_maintenance';

    public function register(): void {
        add_action( self::HOOK, [ $this, 'run' ] );
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + 300, 'daily', self::HOOK );
        }
    }

    public function run(): void {
        global $wpdb;
        $settings = EngineSettings::current();
        $submissions = (int) $settings['retention_days'];
        $logs = (int) $settings['log_retention_days'];

        if ( $submissions > 0 ) {
            $cutoff = wp_date( 'Y-m-d H:i:s', time() - $submissions * DAY_IN_SECONDS );
            $table = $wpdb->prefix . 'cf7on_submissions';
            $deliveryTable = $wpdb->prefix . 'cf7on_deliveries';
            $logTable = $wpdb->prefix . 'cf7on_logs';

            // Bounded batches make the cron task safer on shared hosting.
            for ( $batch = 0; $batch < 10; ++$batch ) {
                $ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE created_at < %s ORDER BY id LIMIT 100",
                    $cutoff
                ) );
                if ( ! $ids ) {
                    break;
                }

                // IDs come from the database and are cast to integers before SQL.
                $ids = implode( ',', array_map( 'absint', $ids ) );
                $deletedLogs = $wpdb->query(
                    "DELETE FROM {$logTable} WHERE delivery_id IN (SELECT id FROM {$deliveryTable} WHERE submission_id IN ({$ids}))"
                );
                if ( $deletedLogs === false ) {
                    break;
                }
                if ( $wpdb->query( "DELETE FROM {$deliveryTable} WHERE submission_id IN ({$ids})" ) === false ) {
                    break;
                }
                if ( $wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ids})" ) === false ) {
                    break;
                }
            }
        }

        if ( $logs > 0 ) {
            $cutoff = wp_date( 'Y-m-d H:i:s', time() - $logs * DAY_IN_SECONDS );
            $logTable = $wpdb->prefix . 'cf7on_logs';
            for ( $batch = 0; $batch < 10; ++$batch ) {
                $affected = $wpdb->query( $wpdb->prepare(
                    "DELETE FROM {$logTable} WHERE created_at < %s LIMIT 500",
                    $cutoff
                ) );
                if ( $affected === false || $affected < 500 ) {
                    break;
                }
            }
        }
    }
}
