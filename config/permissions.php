<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Permissions
    |--------------------------------------------------------------------------
    |
    | Define all system-wide permissions here.
    | Format: {module}.{resource}.{action}
    |
    */

    'permissions' => [
        // Core module permissions
        'core.users.view',
        'core.users.viewAny',
        'core.users.create',
        'core.users.update',
        'core.users.delete',
        'core.users.restore',

        'core.roles.view',
        'core.roles.viewAny',
        'core.roles.create',
        'core.roles.update',
        'core.roles.delete',
        'core.roles.restore',

        'core.permissions.view',
        'core.permissions.viewAny',

        'core.tenants.view',
        'core.tenants.viewAny',
        'core.tenants.create',
        'core.tenants.update',
        'core.tenants.delete',
        'core.tenants.restore',

        'core.audit_logs.view',
        'core.audit_logs.viewAny',

        'platform.manage',

        // CRM module permissions (for future use)
        'crm.leads.view',
        'crm.leads.viewAny',
        'crm.leads.create',
        'crm.leads.update',
        'crm.leads.delete',
        'crm.leads.restore',

        'crm.contacts.view',
        'crm.contacts.viewAny',
        'crm.contacts.create',
        'crm.contacts.update',
        'crm.contacts.delete',
        'crm.contacts.restore',

        'crm.deals.view',
        'crm.deals.viewAny',
        'crm.deals.create',
        'crm.deals.update',
        'crm.deals.delete',
        'crm.deals.restore',

        'crm.accounts.view',
        'crm.accounts.viewAny',
        'crm.accounts.create',
        'crm.accounts.update',
        'crm.accounts.delete',
        'crm.accounts.restore',

        'crm.pipelines.view',
        'crm.pipelines.viewAny',
        'crm.pipelines.create',
        'crm.pipelines.update',
        'crm.pipelines.delete',
        'crm.pipelines.restore',

        'crm.reports.view',
        'crm.reports.viewAny',

        'crm.workflows.view',
        'crm.workflows.viewAny',
        'crm.workflows.create',
        'crm.workflows.update',
        'crm.workflows.delete',
        'crm.workflows.restore',

        'crm.import.view',
        'crm.import.viewAny',
        'crm.import.create',
        'crm.import.update',
        'crm.import.delete',

        'crm.export.view',
        'crm.export.viewAny',
        'crm.export.create',
        'crm.export.update',
        'crm.export.delete',

        'crm.email_accounts.view',
        'crm.email_accounts.viewAny',
        'crm.email_accounts.create',
        'crm.email_accounts.update',
        'crm.email_accounts.delete',
        'crm.email_accounts.restore',

        'crm.email_templates.view',
        'crm.email_templates.viewAny',
        'crm.email_templates.create',
        'crm.email_templates.update',
        'crm.email_templates.delete',
        'crm.email_templates.restore',

        'crm.email_campaigns.view',
        'crm.email_campaigns.viewAny',
        'crm.email_campaigns.create',
        'crm.email_campaigns.update',
        'crm.email_campaigns.delete',
        'crm.email_campaigns.restore',

        'crm.calendar_integrations.view',
        'crm.calendar_integrations.viewAny',
        'crm.calendar_integrations.create',
        'crm.calendar_integrations.update',
        'crm.calendar_integrations.delete',

        // ERP module permissions
        'erp.core.view',
        'erp.core.manage',

        'erp.products.view',
        'erp.products.viewAny',
        'erp.products.create',
        'erp.products.update',
        'erp.products.delete',
        'erp.products.restore',

        'erp.product_categories.view',
        'erp.product_categories.viewAny',
        'erp.product_categories.create',
        'erp.product_categories.update',
        'erp.product_categories.delete',
        'erp.product_categories.restore',

        'erp.inventory.view',
        'erp.inventory.viewAny',
        'erp.inventory.create',
        'erp.inventory.update',
        'erp.inventory.delete',
        'erp.inventory.restore',

        'erp.warehouses.view',
        'erp.warehouses.viewAny',
        'erp.warehouses.create',
        'erp.warehouses.update',
        'erp.warehouses.delete',
        'erp.warehouses.restore',

        'erp.currencies.view',
        'erp.currencies.viewAny',
        'erp.currencies.create',
        'erp.currencies.update',
        'erp.currencies.delete',
        'erp.currencies.restore',

        'erp.suppliers.view',
        'erp.suppliers.viewAny',
        'erp.suppliers.create',
        'erp.suppliers.update',
        'erp.suppliers.delete',
        'erp.suppliers.restore',

        'erp.sales.view',
        'erp.sales.viewAny',
        'erp.sales.create',
        'erp.sales.update',
        'erp.sales.delete',
        'erp.sales.restore',

        'erp.purchases.view',
        'erp.purchases.viewAny',
        'erp.purchases.create',
        'erp.purchases.update',
        'erp.purchases.delete',
        'erp.purchases.restore',

        'erp.accounting.accounts.view',
        'erp.accounting.accounts.viewAny',
        'erp.accounting.accounts.create',
        'erp.accounting.accounts.update',
        'erp.accounting.accounts.delete',
        'erp.accounting.accounts.restore',

        'erp.accounting.journals.view',
        'erp.accounting.journals.viewAny',
        'erp.accounting.journals.create',
        'erp.accounting.journals.update',
        'erp.accounting.journals.delete',
        'erp.accounting.journals.restore',

        'erp.orders.view',
        'erp.orders.viewAny',
        'erp.orders.create',
        'erp.orders.update',
        'erp.orders.delete',
        'erp.orders.restore',

        'erp.invoices.view',
        'erp.invoices.viewAny',
        'erp.invoices.create',
        'erp.invoices.update',
        'erp.invoices.delete',
        'erp.invoices.restore',

        'erp.credit_notes.view',
        'erp.credit_notes.viewAny',
        'erp.credit_notes.create',
        'erp.credit_notes.update',
        'erp.credit_notes.delete',
        'erp.credit_notes.restore',

        'erp.expenses.view',
        'erp.expenses.viewAny',
        'erp.expenses.create',
        'erp.expenses.update',
        'erp.expenses.delete',
        'erp.expenses.restore',

        'erp.payment_gateways.view',
        'erp.payment_gateways.viewAny',
        'erp.payment_gateways.create',
        'erp.payment_gateways.update',
        'erp.payment_gateways.delete',
        'erp.payment_gateways.restore',

        'erp.projects.view',
        'erp.projects.viewAny',
        'erp.projects.create',
        'erp.projects.update',
        'erp.projects.delete',
        'erp.projects.restore',

        'erp.timesheets.view',
        'erp.timesheets.viewAny',
        'erp.timesheets.create',
        'erp.timesheets.update',
        'erp.timesheets.delete',
        'erp.timesheets.restore',

        'core.custom_dashboards.view',
        'core.custom_dashboards.viewAny',
        'core.custom_dashboards.create',
        'core.custom_dashboards.update',
        'core.custom_dashboards.delete',
        'core.custom_dashboards.restore',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Groups
    |--------------------------------------------------------------------------
    |
    | Group permissions by module for easier management.
    |
    */

    'groups' => [
        'core' => [
            'users',
            'roles',
            'permissions',
            'tenants',
            'custom_dashboards',
        ],
        'crm' => [
            'leads',
            'contacts',
            'deals',
            'accounts',
            'pipelines',
            'reports',
            'workflows',
            'import',
            'export',
            'email_accounts',
            'email_templates',
            'email_campaigns',
            'calendar_integrations',
        ],
        'erp' => [
            'core',
            'products',
            'product_categories',
            'inventory',
            'warehouses',
            'currencies',
            'suppliers',
            'sales',
            'purchases',
            'accounting',
            'orders',
            'invoices',
            'credit_notes',
            'expenses',
            'payment_gateways',
            'projects',
            'timesheets',
        ],
    ],
];

