<?php

namespace BlackPrint\Commerce;

defined('ABSPATH') || exit;

class Loader
{
    /**
     * Singleton Instance
     *
     * @var Loader|null
     */
    private static ?Loader $instance = null;

    /**
     * Get Instance
     */
    public static function instance(): Loader
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->boot();
    }

    /**
     * Load Classes
     */
    private function load_dependencies(): void
    {
        require_once BP_COMMERCE_PATH . 'admin/class-admin.php';
require_once BP_COMMERCE_PATH . 'admin/class-menu.php';
require_once BP_COMMERCE_PATH . 'admin/class-dashboard.php';
    }

    /**
     * Boot Modules
     */
    private function boot(): void
    {
        new Admin();
    }
}