<?php
namespace CF7ON\Core;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static ?Plugin $instance = null;
    private array $services = [];

    public static function getInstance(): self {
        if ( null === self::$instance ) self::$instance = new self();
        return self::$instance;
    }
    private function __construct() {}

    public function boot(): void {
        $this->loadTextDomain();
        $this->registerServices();
        $this->bootServices();
    }

    private function loadTextDomain(): void {
        load_plugin_textdomain( 'cf7-booster', false, dirname( CF7ON_BASENAME ) . '/languages' );
    }

    private function registerServices(): void {
        $this->services = [
            'db'         => new \CF7ON\Services\DatabaseService(),
            'queue'      => new \CF7ON\Services\QueueService(),
            'cf7'        => new \CF7ON\Modules\CF7\CF7Integration(),
            'telegram'   => new \CF7ON\Modules\Telegram\TelegramAdapter(),
            'bale'       => new \CF7ON\Modules\Bale\BaleAdapter(),
            'automation' => new \CF7ON\Modules\Automation\AutomationEngine(),
            'dashboard'  => new \CF7ON\Modules\Dashboard\DashboardModule(),
            'form_tab'   => new \CF7ON\Modules\FormTab\FormTabModule(),
            'logs'       => new \CF7ON\Modules\Logs\LogsModule(),
        ];

        $this->services['cf7']->setQueue( $this->services['queue'] );
        $this->services['queue']->setAdapters( [
            'telegram' => $this->services['telegram'],
            'bale'     => $this->services['bale'],
        ] );
        $this->services['queue']->setAutomation( $this->services['automation'] );
    }

    private function bootServices(): void {
        foreach ( $this->services as $service ) {
            if ( method_exists( $service, 'register' ) ) $service->register();
        }
    }

    public function get( string $key ) {
        return $this->services[ $key ] ?? null;
    }
}
