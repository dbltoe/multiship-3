<?php
// -----
// Multiple Ship-To Addresses, encapsulated-plugin installer.
//
// Original plugin Copyright (C) 2014-2019, Vinos de Frutas Tropicales (lat9)
// This file new in v3.0.0, Copyright (C) 2026 My Zen Cart Host (dbltoe)
// @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
//
// Portability note: this plugin declares support back to Zen Cart v2.0.0, and
// v2.0.0's ScriptedInstaller offers only executeInstallerSql() and $this->dbConn.
// The ScriptedInstallHelpers trait (addConfigurationKey, getOrCreateConfigGroupId,
// executeInstallerSelectQuery, ...) was not introduced until v2.0.1, so nothing
// from it is used here. Keep it that way unless the manifest's zcVersions floor
// is raised.
//
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected const CONFIG_GROUP_TITLE = 'Multiple Ship-to Addresses';

    // -----
    // Configuration keys retired in v3.0.0:
    //  - MODULE_MULTISHIP_ENABLE ... installing the plugin now *is* the decision
    //    to use it, so a separate store-wide toggle no longer exists.
    //  - MODULE_MULTISHIP_VERSION and MODULE_MULTISHIP_RELEASE_DATE ... version
    //    tracking is the plugin manager's job under the encapsulated structure.
    //
    protected const RETIRED_CONFIG_KEYS = [
        'MODULE_MULTISHIP_ENABLE',
        'MODULE_MULTISHIP_VERSION',
        'MODULE_MULTISHIP_RELEASE_DATE',
    ];

    protected function executeInstall()
    {
        $success = $this->createSchema();

        $cgi = $this->getConfigurationGroupId();
        if ($cgi === 0) {
            return false;
        }

        $success = $this->addConfigurationKeys($cgi) && $success;
        $this->registerAdminPages($cgi);

        return $success;
    }

    // -----
    // v2.0.0 declares executeUpgrade() with no parameters; v2.0.1 and later pass
    // the previously-installed version. An optional parameter satisfies both.
    //
    // Every step below is idempotent, so this also serves to migrate a store
    // running the old, non-encapsulated multiship install.
    //
    protected function executeUpgrade($oldVersion = null)
    {
        $success = $this->createSchema();

        $cgi = $this->getConfigurationGroupId();
        if ($cgi === 0) {
            return false;
        }

        $success = $this->addConfigurationKeys($cgi) && $success;
        $this->registerAdminPages($cgi);
        $success = $this->removeRetiredConfigurationKeys() && $success;

        return $success;
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(
            ['customersInvoiceMultiship', 'customersPackingslipMultiship', 'configMultiship']
        );

        $success = true;

        if ($this->columnExists(DB_PREFIX . 'orders_products', 'orders_multiship_id')) {
            $success = $this->executeInstallerSql(
                "ALTER TABLE " . DB_PREFIX . "orders_products DROP COLUMN orders_multiship_id"
            );
        }

        $success = $this->executeInstallerSql("DROP TABLE IF EXISTS " . DB_PREFIX . "orders_multiship_total") && $success;
        $success = $this->executeInstallerSql("DROP TABLE IF EXISTS " . DB_PREFIX . "orders_multiship") && $success;

        $success = $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'MODULE_MULTISHIP\_%'"
        ) && $success;
        $success = $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '" . self::CONFIG_GROUP_TITLE . "'
              LIMIT 1"
        ) && $success;

        return $success;
    }

    // -------------------------------------------------------------------------

    // -----
    // Each statement is issued unconditionally -- the success flag is folded in
    // afterwards -- so that one failure does not silently skip the rest.
    //
    protected function createSchema()
    {
        $success = $this->executeInstallerSql(
            "CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "orders_multiship (
                orders_multiship_id int(11) NOT NULL auto_increment,
                orders_id int(11) NOT NULL default '0',
                delivery_name varchar(64) NOT NULL default '',
                delivery_company varchar(64) default NULL,
                delivery_street_address varchar(64) NOT NULL default '',
                delivery_suburb varchar(32) default NULL,
                delivery_city varchar(32) NOT NULL default '',
                delivery_postcode varchar(10) NOT NULL default '',
                delivery_state varchar(32) default NULL,
                delivery_country varchar(32) NOT NULL default '',
                delivery_address_format_id int(5) NOT NULL default '0',
                last_modified datetime default NULL,
                orders_status int(5) NOT NULL default '0',
                content_type char(8) NOT NULL default '',
             PRIMARY KEY (orders_multiship_id))"
        );

        $success = $this->executeInstallerSql(
            "CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "orders_multiship_total (
                orders_multiship_total_id int(11) unsigned NOT NULL auto_increment,
                orders_id int(11) NOT NULL default '0',
                orders_multiship_id int(11) NOT NULL default '0',
                title varchar(255) NOT NULL default '',
                text varchar(255) NOT NULL default '',
                value decimal(15,4) NOT NULL default '0.0000',
                class varchar(32) NOT NULL default '',
                sort_order int(11) NOT NULL default '0',
             PRIMARY KEY (orders_multiship_total_id))"
        ) && $success;

        if (!$this->columnExists(DB_PREFIX . 'orders_products', 'orders_multiship_id')) {
            $success = $this->executeInstallerSql(
                "ALTER TABLE " . DB_PREFIX . "orders_products
                    ADD orders_multiship_id int(11) NOT NULL default '0' AFTER orders_id"
            ) && $success;
        }

        return $success;
    }

    // -----
    // information_schema is used rather than the admin's $sniffer object, since the
    // installer cannot assume that global is in scope.
    //
    protected function columnExists($table_name, $column_name)
    {
        $check = $this->dbConn->Execute(
            "SELECT COLUMN_NAME
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '" . $this->dbConn->prepare_input($table_name) . "'
                AND COLUMN_NAME = '" . $this->dbConn->prepare_input($column_name) . "'
              LIMIT 1"
        );
        return !$check->EOF;
    }

    protected function getConfigurationGroupId()
    {
        $sql =
            "SELECT configuration_group_id
               FROM " . TABLE_CONFIGURATION_GROUP . "
              WHERE configuration_group_title = '" . self::CONFIG_GROUP_TITLE . "'
              LIMIT 1";

        $check = $this->dbConn->Execute($sql);
        if (!$check->EOF) {
            return (int)$check->fields['configuration_group_id'];
        }

        $this->executeInstallerSql(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
                (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES
                ('" . self::CONFIG_GROUP_TITLE . "', '" . self::CONFIG_GROUP_TITLE . "', 1, 1)"
        );

        // -----
        // Re-read rather than trusting insert_ID(), but do not assume the INSERT
        // succeeded: returning 0 here makes the caller abort rather than writing
        // configuration keys into a non-existent group.
        //
        $check = $this->dbConn->Execute($sql);
        if ($check->EOF) {
            return 0;
        }
        $cgi = (int)$check->fields['configuration_group_id'];
        if ($cgi === 0) {
            return 0;
        }

        // Zen Cart convention: a configuration group sorts by its own id.
        $this->executeInstallerSql(
            "UPDATE " . TABLE_CONFIGURATION_GROUP . "
                SET sort_order = $cgi
              WHERE configuration_group_id = $cgi
              LIMIT 1"
        );

        return $cgi;
    }

    protected function addConfigurationKeys($cgi)
    {
        $cgi = (int)$cgi;
        $success = true;

        if (!$this->configurationKeyExists('MODULE_MULTISHIP_PAYMENT_METHODS')) {
            $success = $this->executeInstallerSql(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
                 VALUES
                    ('Unsupported Payment Methods',
                     'MODULE_MULTISHIP_PAYMENT_METHODS',
                     '',
                     'Identify, using a comma-separated list (intervening blanks are OK), the payment methods to be <b>disabled</b> if an order has multiple ship-to addresses.<br /><br />Leave the setting as an empty string (the default) to enable the plugin for <b>all</b> payment methods.<br />',
                     $cgi, 20, NOW())"
            );
        }

        if (!$this->configurationKeyExists('MODULE_MULTISHIP_MAX_ADDRESSES')) {
            $success = $this->executeInstallerSql(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
                 VALUES
                    ('Maximum Delivery Addresses',
                     'MODULE_MULTISHIP_MAX_ADDRESSES',
                     '10',
                     'The most delivery addresses a customer may save while placing a multiple ship-to order.<br /><br />This is deliberately separate from <b>Maximum Address Book Entries</b> under <em>Customer Details</em>. That setting governs how many addresses an ordinary customer may keep and is left alone; this one governs how far a single multiship order may spread. Set it no lower than the store-wide limit.',
                     $cgi, 30, NOW())"
            ) && $success;
        }

        if (!$this->configurationKeyExists('MODULE_MULTISHIP_DEBUG')) {
            $success = $this->executeInstallerSql(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                    (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, set_function)
                 VALUES
                    ('Enable debug?',
                     'MODULE_MULTISHIP_DEBUG',
                     'false',
                     'Enable the plugin debug-log?',
                     $cgi, 500, NOW(),
                     'zen_cfg_select_option(array(\'true\', \'false\'),')"
            ) && $success;
        }

        return $success;
    }

    protected function configurationKeyExists($key_name)
    {
        $check = $this->dbConn->Execute(
            "SELECT configuration_id
               FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key = '" . $this->dbConn->prepare_input($key_name) . "'
              LIMIT 1"
        );
        return !$check->EOF;
    }

    protected function removeRetiredConfigurationKeys()
    {
        return $this->executeInstallerSql(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN ('" . implode("', '", self::RETIRED_CONFIG_KEYS) . "')"
        );
    }

    // -----
    // The third argument is the *name* of the FILENAME_ constant, stored as-is in
    // admin_pages; the constant itself need not be defined at install time.
    //
    protected function registerAdminPages($cgi)
    {
        zen_deregister_admin_pages(
            ['customersInvoiceMultiship', 'customersPackingslipMultiship', 'configMultiship']
        );

        zen_register_admin_page(
            'customersInvoiceMultiship',
            'BOX_CUSTOMERS_INVOICE_MULTISHIP',
            'FILENAME_INVOICE_MULTISHIP',
            '',
            'customers',
            'N'
        );
        zen_register_admin_page(
            'customersPackingslipMultiship',
            'BOX_CUSTOMERS_PACKINGSLIP_MULTISHIP',
            'FILENAME_PACKINGSLIP_MULTISHIP',
            '',
            'customers',
            'N'
        );
        zen_register_admin_page(
            'configMultiship',
            'BOX_CONFIG_MULTISHIP',
            'FILENAME_CONFIGURATION',
            'gID=' . (int)$cgi,
            'configuration',
            'Y'
        );
    }
}
