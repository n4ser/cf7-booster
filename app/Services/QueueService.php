<?php
namespace CF7ON\Services;

defined( 'ABSPATH' ) || exit;

use CF7ON\Models\Delivery;
use CF7ON\Models\Submission;
use CF7ON\Modules\Automation\AutomationEngine;
use CF7ON\Modules\FormTab\FormTabModule;

/**
 * Dispatches one delivery per configured channel/destination.
 * The delivery rows are the source of truth for retry and status tracking.
 */
class QueueService {
    private array $adapters = [];
    private AutomationEngine $automation;

    public function setAdapters( array $adapters ): void {
        $this->adapters = $adapters;
    }

    public function setAutomation( AutomationEngine $automation ): void {
        $this->automation = $automation;
    }

    public function register(): void {
        add_action( 'cf7on_process_queue', [ $this, 'processJob' ] );
        add_action( 'cf7on_retry_delivery', [ $this, 'retryDelivery' ], 10, 2 );
    }

    public function dispatch( int $submissionId, array $data ): void {
        $engine = get_option( 'cf7on_engine', [] );
        if ( ( $engine['queue_mode'] ?? 'async' ) !== 'async' ) {
            $this->processJob( $submissionId );
            return;
        }

        // Avoid enqueueing identical pending cron events.
        if ( ! wp_next_scheduled( 'cf7on_process_queue', [ $submissionId ] ) ) {
            wp_schedule_single_event( time(), 'cf7on_process_queue', [ $submissionId ] );
        }
    }

    public function processJob( int $submissionId ): void {
        $data = Submission::find( $submissionId );
        if ( ! $data ) {
            return;
        }

        $targets = $this->resolveTargets( $data );
        if ( ! $targets ) {
            // Do not incorrectly mark an unsent submission as delivered.
            LogService::write( 'queue', 'warning', 'No notification destinations configured for submission ' . $submissionId );
            return;
        }

        // Replayed cron jobs must not create duplicate deliveries.
        $existing = [];
        foreach ( Delivery::forSubmission( $submissionId ) as $delivery ) {
            $existing[ $this->targetKey( $delivery['channel'], $delivery['destination'] ) ] = true;
        }

        $engine     = get_option( 'cf7on_engine', [] );
        $maxRetries = absint( $engine['max_retries'] ?? 3 );

        foreach ( $targets as $target ) {
            $channel     = sanitize_key( $target['channel'] ?? '' );
            $destination = sanitize_text_field( $target['destination'] ?? '' );
            $adapter     = $this->adapters[ $channel ] ?? null;
            $key         = $this->targetKey( $channel, $destination );

            if ( ! $adapter || $destination === '' || isset( $existing[ $key ] ) ) {
                continue;
            }

            $deliveryId = Delivery::create( $submissionId, $channel, $destination );
            if ( ! $deliveryId ) {
                LogService::write( 'queue', 'error', 'Could not create delivery row for submission ' . $submissionId );
                continue;
            }
            $existing[ $key ] = true;

            $result = $adapter->send( $data, $target );
            if ( ! empty( $result['success'] ) ) {
                Delivery::markSuccess( $deliveryId );
                continue;
            }

            Delivery::incrementAttempt( $deliveryId, $result['error'] ?? '' );
            if ( $maxRetries > 0 ) {
                $this->scheduleRetry( $deliveryId, 1 );
            } else {
                Delivery::markFailed( $deliveryId, $result['error'] ?? '' );
            }
        }

        $this->refreshSubmissionStatus( $submissionId );
    }

    public function retryDelivery( int $deliveryId, int $attempt = 1 ): void {
        $delivery = Delivery::find( $deliveryId );
        if ( ! $delivery || $delivery['status'] === 'success' ) {
            return;
        }

        $submissionId = (int) $delivery['submission_id'];
        $data         = Submission::find( $submissionId );
        $adapter      = $this->adapters[ $delivery['channel'] ] ?? null;
        $engine       = get_option( 'cf7on_engine', [] );
        $maxRetries   = absint( $engine['max_retries'] ?? 3 );

        if ( ! $data || ! $adapter || $attempt > $maxRetries ) {
            Delivery::markFailed( $deliveryId, 'Retry unavailable or maximum retries exhausted.' );
            $this->refreshSubmissionStatus( $submissionId );
            return;
        }

        // Preserve the template associated with the selected routing target.
        $target = null;
        foreach ( $this->resolveTargets( $data ) as $candidate ) {
            if ( ( $candidate['channel'] ?? '' ) === $delivery['channel']
                && (string) ( $candidate['destination'] ?? '' ) === (string) $delivery['destination'] ) {
                $target = $candidate;
                break;
            }
        }

        if ( ! $target ) {
            Delivery::markFailed( $deliveryId, 'Destination is no longer configured.' );
            $this->refreshSubmissionStatus( $submissionId );
            return;
        }

        $result = $adapter->send( $data, $target );
        if ( ! empty( $result['success'] ) ) {
            Delivery::markSuccess( $deliveryId );
        } else {
            Delivery::incrementAttempt( $deliveryId, $result['error'] ?? '' );
            if ( $attempt < $maxRetries ) {
                $this->scheduleRetry( $deliveryId, $attempt + 1 );
            } else {
                Delivery::markFailed( $deliveryId, $result['error'] ?? '' );
            }
        }
        $this->refreshSubmissionStatus( $submissionId );
    }

    /**
     * Manually retry only failed deliveries, never already successful ones.
     * Returns the number of retried delivery records.
     */
    public function resendFailed( int $submissionId ): int {
        $count = 0;
        foreach ( Delivery::forSubmission( $submissionId ) as $delivery ) {
            if ( $delivery['status'] !== 'failed' ) {
                continue;
            }
            // A manual resend is a new attempt, even when automatic retries are disabled.
            $this->retryDelivery( (int) $delivery['id'], 0 );
            ++$count;
        }
        return $count;
    }

    private function resolveTargets( array $data ): array {
        $targets = $this->automation->resolveTargets( $data );
        if ( $targets ) {
            return $targets;
        }

        $formCfg = FormTabModule::getFormSettings( (int) $data['form_id'] );
        // An explicitly configured form overrides global routing, including disabled channels.
        if ( $formCfg ) {
            return $this->resolveFormTargets( $formCfg );
        }
        return $this->getGlobalTargets();
    }

    private function resolveFormTargets( array $formCfg ): array {
        $targets = [];
        foreach ( [ 'telegram', 'bale' ] as $channel ) {
            $config = $formCfg[ $channel ] ?? [];
            if ( empty( $config['enabled'] ) ) {
                continue;
            }

            $global       = get_option( 'cf7on_' . $channel, [] );
            $destinations = array_filter( (array) ( $config['destinations'] ?? [] ) );
            if ( ! $destinations ) {
                $destinations = array_filter( (array) ( $global['destinations'] ?? [] ) );
            }
            foreach ( $destinations as $destination ) {
                $targets[] = [
                    'channel'     => $channel,
                    'destination' => $destination,
                    'template'    => ! empty( $config['template'] ) ? $config['template'] : ( $global['template'] ?? '' ),
                ];
            }
        }
        return $targets;
    }

    private function getGlobalTargets(): array {
        $targets = [];
        foreach ( [ 'telegram', 'bale' ] as $channel ) {
            $config = get_option( 'cf7on_' . $channel, [] );
            if ( empty( $config['enabled'] ) ) {
                continue;
            }
            foreach ( array_filter( (array) ( $config['destinations'] ?? [] ) ) as $destination ) {
                $targets[] = [
                    'channel'     => $channel,
                    'destination' => $destination,
                    'template'    => $config['template'] ?? '',
                ];
            }
        }
        return $targets;
    }

    private function targetKey( string $channel, string $destination ): string {
        return $channel . ':' . $destination;
    }

    private function scheduleRetry( int $deliveryId, int $attempt ): void {
        $engine   = get_option( 'cf7on_engine', [] );
        $interval = max( 10, absint( $engine['retry_interval'] ?? 90 ) );
        $args     = [ $deliveryId, $attempt ];
        if ( ! wp_next_scheduled( 'cf7on_retry_delivery', $args ) ) {
            wp_schedule_single_event( time() + $interval, 'cf7on_retry_delivery', $args );
        }
    }

    private function refreshSubmissionStatus( int $submissionId ): void {
        $deliveries = Delivery::forSubmission( $submissionId );
        if ( ! $deliveries ) {
            return;
        }
        $total     = count( $deliveries );
        $successes = 0;
        $pending   = 0;

        foreach ( $deliveries as $delivery ) {
            if ( $delivery['status'] === 'success' ) {
                ++$successes;
            } elseif ( $delivery['status'] === 'pending' ) {
                ++$pending;
            }
        }

        if ( $successes === $total ) {
            $status = 'delivered';
        } elseif ( $successes > 0 ) {
            $status = 'partial';
        } elseif ( $pending > 0 ) {
            $status = 'pending';
        } else {
            $status = 'failed';
        }
        Submission::updateStatus( $submissionId, $status );
    }
}
