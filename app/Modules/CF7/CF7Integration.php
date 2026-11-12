<?php
namespace CF7ON\Modules\CF7;

defined( 'ABSPATH' ) || exit;

use CF7ON\Services\QueueService;
use CF7ON\Models\Submission;

class CF7Integration {

    private QueueService $queue;

    public function setQueue( QueueService $queue ): void {
        $this->queue = $queue;
    }

    public function register(): void {
        // Primary hook: fires after email is sent
        add_action( 'wpcf7_mail_sent', [ $this, 'onMailSent' ] );

        // Alternative: fires before email, lets us handle failures too
        add_action( 'wpcf7_before_send_mail', [ $this, 'onBeforeSendMail' ] );

        // Also catch failed submissions for logging
        add_action( 'wpcf7_mail_failed', [ $this, 'onMailFailed' ] );
    }

    /**
     * Main handler — called after CF7 successfully sends its own email.
     */
    public function onMailSent( \WPCF7_ContactForm $cf7 ): void {
        $submission = \WPCF7_Submission::get_instance();
        if ( ! $submission ) {
            return;
        }

        $data = $this->extractSubmissionData( $cf7, $submission );
        $model = new Submission( $data );
        $id    = $model->save();

        if ( $id ) {
            $this->queue->dispatch( $id, $data );
        }
    }

    /**
     * Alternative hook — intercept before email (useful for conditional logic).
     */
    public function onBeforeSendMail( \WPCF7_ContactForm $cf7 ): void {
        // Reserved for pre-send automation hooks (e.g. spam filtering in Phase 3)
    }

    /**
     * Log when CF7's own email fails.
     */
    public function onMailFailed( \WPCF7_ContactForm $cf7 ): void {
        $submission = \WPCF7_Submission::get_instance();
        if ( ! $submission ) {
            return;
        }
        $data  = $this->extractSubmissionData( $cf7, $submission );
        $model = new Submission( array_merge( $data, [ 'status' => 'failed' ] ) );
        $model->save();
    }

    /**
     * Pull all relevant data from a CF7 submission.
     */
    private function extractSubmissionData(
        \WPCF7_ContactForm $cf7,
        \WPCF7_Submission  $submission
    ): array {
        $posted = $submission->get_posted_data();

        // Strip internal CF7 keys
        $fields = array_filter(
            $posted,
            fn( $key ) => ! in_array( $key, [ '_wpcf7', '_wpcf7_version', '_wpcf7_locale',
                '_wpcf7_unit_tag', '_wpcf7_container_post', '_wpcf7_posted_data_hash',
                'g-recaptcha-response', '_wpcf7_recaptcha_response' ], true ),
            ARRAY_FILTER_USE_KEY
        );

        return [
            'form_id'    => $cf7->id(),
            'form_name'  => $cf7->title(),
            'fields'     => $fields,
            'ip_address' => $submission->get_meta( 'remote_ip' ),
            'user_agent' => $submission->get_meta( 'user_agent' ),
            'referrer'   => $submission->get_meta( 'url' ),
            'status'     => 'pending',
        ];
    }
}
