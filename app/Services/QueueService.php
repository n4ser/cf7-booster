<?php
namespace CF7ON\Services;

defined( 'ABSPATH' ) || exit;

use CF7ON\Modules\Automation\AutomationEngine;
use CF7ON\Models\Submission;
use CF7ON\Models\Delivery;
use CF7ON\Modules\FormTab\FormTabModule;

class QueueService {

    private array $adapters = [];
    private AutomationEngine $automation;

    public function setAdapters( array $adapters ): void  { $this->adapters = $adapters; }
    public function setAutomation( AutomationEngine $a ): void { $this->automation = $a; }

    public function register(): void {
        add_action( 'cf7on_process_queue',  [ $this, 'processJob' ] );
        add_action( 'cf7on_retry_delivery', [ $this, 'retryDelivery' ] );
    }

    public function dispatch( int $submissionId, array $data ): void {
        $engine = get_option( 'cf7on_engine', [] );
        if ( ( $engine['queue_mode'] ?? 'async' ) === 'async' ) {
            wp_schedule_single_event( time(), 'cf7on_process_queue', [ $submissionId ] );
        } else {
            $this->processJob( $submissionId );
        }
    }

    public function processJob( int $submissionId ): void {
        $data = Submission::find( $submissionId );
        if ( ! $data ) return;

        $form_id     = (int) $data['form_id'];
        $form_cfg    = FormTabModule::getFormSettings( $form_id );

        // Priority: automation rules → per-form settings → global settings
        $targets = $this->automation->resolveTargets( $data );

        if ( empty( $targets ) ) {
            $targets = $this->resolveFormTargets( $form_id, $form_cfg );
        }

        if ( empty( $targets ) ) {
            $targets = $this->getGlobalTargets();
        }

        $allOk = true;
        foreach ( $targets as $target ) {
            $channel = $target['channel'] ?? '';
            $adapter = $this->adapters[ $channel ] ?? null;
            if ( ! $adapter ) continue;

            $deliveryId = Delivery::create( $submissionId, $channel, $target['destination'] ?? '' );
            $result     = $adapter->send( $data, $target );

            if ( $result['success'] ) {
                Delivery::markSuccess( $deliveryId );
            } else {
                $allOk = false;
                Delivery::incrementAttempt( $deliveryId, $result['error'] ?? '' );
                $this->scheduleRetry( $deliveryId, 1 );
            }
        }

        $status = $allOk ? 'delivered' : ( $this->hasAnySuccess( $submissionId ) ? 'partial' : 'failed' );
        Submission::updateStatus( $submissionId, $status );
    }

    public function retryDelivery( int $deliveryId, int $attempt ): void {
        $delivery = Delivery::find( $deliveryId );
        if ( ! $delivery ) return;

        $engine     = get_option( 'cf7on_engine', [] );
        $maxRetries = absint( $engine['max_retries'] ?? 3 );

        if ( $attempt > $maxRetries ) {
            Delivery::markFailed( $deliveryId, 'Max retries exhausted' );
            return;
        }

        $data    = Submission::find( (int) $delivery['submission_id'] );
        $channel = $delivery['channel'];
        $adapter = $this->adapters[ $channel ] ?? null;

        if ( ! $adapter || ! $data ) return;

        $result = $adapter->send( $data, [ 'destination' => $delivery['destination'] ] );
        if ( $result['success'] ) {
            Delivery::markSuccess( $deliveryId );
        } else {
            Delivery::incrementAttempt( $deliveryId, $result['error'] ?? '' );
            $this->scheduleRetry( $deliveryId, $attempt + 1 );
        }
    }

    /**
     * Build targets from per-form settings (CF7 Booster tab).
     * Only included if the channel is enabled AND has destinations.
     * Falls back to global destinations if form destinations are empty.
     */
    private function resolveFormTargets( int $form_id, array $form_cfg ): array {
        $targets = [];

        foreach ( [ 'telegram', 'bale' ] as $channel ) {
            $cfg = $form_cfg[ $channel ] ?? [];
            if ( empty( $cfg['enabled'] ) ) continue;

            // Use per-form destinations if set, otherwise global
            $dests = array_filter( (array) ( $cfg['destinations'] ?? [] ) );
            if ( empty( $dests ) ) {
                $global = get_option( 'cf7on_' . $channel, [] );
                $dests  = array_filter( (array) ( $global['destinations'] ?? [] ) );
            }

            $template = $cfg['template'] ?? '';

            foreach ( $dests as $dest ) {
                $targets[] = [
                    'channel'     => $channel,
                    'destination' => $dest,
                    'template'    => $template,
                ];
            }
        }

        return $targets;
    }

    /** Global fallback — used only if no per-form settings match. */
    private function getGlobalTargets(): array {
        $targets = [];
        foreach ( [ 'telegram', 'bale' ] as $channel ) {
            $opt = get_option( 'cf7on_' . $channel, [] );
            if ( empty( $opt['enabled'] ) ) continue;
            foreach ( array_filter( (array) ( $opt['destinations'] ?? [] ) ) as $dest ) {
                $targets[] = [ 'channel' => $channel, 'destination' => $dest, 'template' => $opt['template'] ?? '' ];
            }
        }
        return $targets;
    }

    private function scheduleRetry( int $deliveryId, int $nextAttempt ): void {
        $engine   = get_option( 'cf7on_engine', [] );
        $interval = absint( $engine['retry_interval'] ?? 90 );
        wp_schedule_single_event( time() + $interval, 'cf7on_retry_delivery', [ $deliveryId, $nextAttempt ] );
    }

    private function hasAnySuccess( int $submissionId ): bool {
        global $wpdb;
        return (bool) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cf7on_deliveries WHERE submission_id = %d AND status = 'success'",
            $submissionId
        ) );
    }
}
