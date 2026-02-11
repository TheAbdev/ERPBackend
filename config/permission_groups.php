<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Groups
    |--------------------------------------------------------------------------
    |
    | Define functional groups of related permissions that should be
    | displayed together in the Roles & Permissions interface.
    | Each group contains permissions that are typically needed together.
    |
    */

    'groups' => [
        // Core User & Role Management Group
        'core_user_role_management' => [
            'name' => 'User & Role Management',
            'description' => 'Manage users, roles, and permissions within your company',
            'permissions' => [
                'core.users.view',
                'core.users.viewAny',
                'core.users.create',
                'core.users.update',
                'core.users.delete',
                'core.roles.view',
                'core.roles.viewAny',
                'core.roles.create',
                'core.roles.update',
                'core.roles.delete',
                'core.permissions.view',
                'core.permissions.viewAny',
            ],
        ],

        // Core Teams & Custom Fields Group
        'core_team_custom_fields' => [
            'name' => 'Teams & Custom Fields',
            'description' => 'Manage teams and custom fields',
            'permissions' => [
                'core.teams.view',
                'core.teams.viewAny',
                'core.teams.create',
                'core.teams.update',
                'core.teams.delete',
                'core.custom_fields.view',
                'core.custom_fields.viewAny',
                'core.custom_fields.create',
                'core.custom_fields.update',
                'core.custom_fields.delete',
            ],
        ],

        // ERP Product Management Group
        'erp_product_management' => [
            'name' => 'Product Management',
            'description' => 'Complete product management including products and categories',
            'permissions' => [
                'erp.products.view',
                'erp.products.viewAny',
                'erp.products.create',
                'erp.products.update',
                'erp.products.delete',
                'erp.product_categories.view',
                'erp.product_categories.viewAny',
                'erp.product_categories.create',
                'erp.product_categories.update',
                'erp.product_categories.delete',
            ],
        ],

        // ERP Inventory Management Group
        'erp_inventory_management' => [
            'name' => 'Inventory Management',
            'description' => 'Inventory, warehouses, and stock management',
            'permissions' => [
                'erp.inventory.view',
                'erp.inventory.viewAny',
                'erp.inventory.create',
                'erp.inventory.update',
                'erp.inventory.delete',
                'erp.warehouses.view',
                'erp.warehouses.viewAny',
                'erp.warehouses.create',
                'erp.warehouses.update',
                'erp.warehouses.delete',
            ],
        ],

        // ERP Sales Management Group
        'erp_sales_management' => [
            'name' => 'Sales Management',
            'description' => 'Sales orders, invoices, and customer management',
            'permissions' => [
                'erp.orders.view',
                'erp.orders.viewAny',
                'erp.orders.create',
                'erp.orders.update',
                'erp.orders.delete',
                'erp.invoices.view',
                'erp.invoices.viewAny',
                'erp.invoices.create',
                'erp.invoices.update',
                'erp.invoices.delete',
                'erp.recurring_invoices.view',
                'erp.recurring_invoices.viewAny',
                'erp.recurring_invoices.create',
                'erp.recurring_invoices.update',
                'erp.recurring_invoices.delete',
            ],
        ],

        // ERP Purchase Management Group
        'erp_purchase_management' => [
            'name' => 'Purchase Management',
            'description' => 'Purchase orders, suppliers, and procurement',
            'permissions' => [
                'erp.purchases.view',
                'erp.purchases.viewAny',
                'erp.purchases.create',
                'erp.purchases.update',
                'erp.purchases.delete',
                'erp.suppliers.view',
                'erp.suppliers.viewAny',
                'erp.suppliers.create',
                'erp.suppliers.update',
                'erp.suppliers.delete',
            ],
        ],

        // CRM Lead Management Group
        'crm_lead_management' => [
            'name' => 'Lead Management',
            'description' => 'Complete lead management and conversion',
            'permissions' => [
                'crm.leads.view',
                'crm.leads.viewAny',
                'crm.leads.create',
                'crm.leads.update',
                'crm.leads.delete',
                'crm.lead_assignment_rules.view',
                'crm.lead_assignment_rules.viewAny',
                'crm.lead_assignment_rules.create',
                'crm.lead_assignment_rules.update',
                'crm.lead_assignment_rules.delete',
            ],
        ],

        // CRM Contact Management Group
        'crm_contact_management' => [
            'name' => 'Contact Management',
            'description' => 'Contact and account management',
            'permissions' => [
                'crm.contacts.view',
                'crm.contacts.viewAny',
                'crm.contacts.create',
                'crm.contacts.update',
                'crm.contacts.delete',
                'crm.accounts.view',
                'crm.accounts.viewAny',
                'crm.accounts.create',
                'crm.accounts.update',
                'crm.accounts.delete',
            ],
        ],

        // CRM Deal Management Group
        'crm_deal_management' => [
            'name' => 'Deal Management',
            'description' => 'Deal management with pipelines and stages',
            'permissions' => [
                'crm.deals.view',
                'crm.deals.viewAny',
                'crm.deals.create',
                'crm.deals.update',
                'crm.deals.delete',
                'crm.pipelines.view',
                'crm.pipelines.viewAny',
                'crm.pipelines.create',
                'crm.pipelines.update',
                'crm.pipelines.delete',
            ],
        ],

        // CRM Communication Group
        'crm_communication' => [
            'name' => 'CRM Communication',
            'description' => 'Email accounts, templates, campaigns, and notes',
            'permissions' => [
                'crm.email_accounts.view',
                'crm.email_accounts.viewAny',
                'crm.email_accounts.create',
                'crm.email_accounts.update',
                'crm.email_accounts.delete',
                'crm.email_templates.view',
                'crm.email_templates.viewAny',
                'crm.email_templates.create',
                'crm.email_templates.update',
                'crm.email_templates.delete',
                'crm.email_campaigns.view',
                'crm.email_campaigns.viewAny',
                'crm.email_campaigns.create',
                'crm.email_campaigns.update',
                'crm.email_campaigns.delete',
                'crm.notes.view',
                'crm.notes.viewAny',
                'crm.notes.create',
                'crm.notes.update',
                'crm.notes.delete',
            ],
        ],

        // CRM Activities Group
        'crm_activities' => [
            'name' => 'CRM Activities',
            'description' => 'Activities and task management',
            'permissions' => [
                'crm.activities.view',
                'crm.activities.viewAny',
                'crm.activities.create',
                'crm.activities.update',
                'crm.activities.delete',
            ],
        ],

        // E-commerce Store Management Group
        'ecommerce_store_management' => [
            'name' => 'E-commerce Store Management',
            'description' => 'Store management and product synchronization',
            'permissions' => [
                'ecommerce.stores.view',
                'ecommerce.stores.viewAny',
                'ecommerce.stores.create',
                'ecommerce.stores.update',
                'ecommerce.stores.delete',
            ],
        ],

        // E-commerce Content Management Group
        'ecommerce_content' => [
            'name' => 'E-commerce Content',
            'description' => 'Themes, pages, and content management',
            'permissions' => [
                'ecommerce.themes.view',
                'ecommerce.themes.viewAny',
                'ecommerce.themes.create',
                'ecommerce.themes.update',
                'ecommerce.themes.delete',
                'ecommerce.pages.view',
                'ecommerce.pages.viewAny',
                'ecommerce.pages.create',
                'ecommerce.pages.update',
                'ecommerce.pages.delete',
            ],
        ],

        // E-commerce Order Management Group
        'ecommerce_orders' => [
            'name' => 'E-commerce Orders',
            'description' => 'Order management and processing',
            'permissions' => [
                'ecommerce.orders.view',
                'ecommerce.orders.viewAny',
                'ecommerce.orders.create',
                'ecommerce.orders.update',
                'ecommerce.orders.delete',
            ],
        ],

        // ERP Project Management Group
        'erp_project_management' => [
            'name' => 'Project Management',
            'description' => 'Projects and timesheets management',
            'permissions' => [
                'erp.projects.view',
                'erp.projects.viewAny',
                'erp.projects.create',
                'erp.projects.update',
                'erp.projects.delete',
                'erp.timesheets.view',
                'erp.timesheets.viewAny',
                'erp.timesheets.create',
                'erp.timesheets.update',
                'erp.timesheets.delete',
            ],
        ],

        // ERP Financial Management Group
        'erp_financial' => [
            'name' => 'Financial Management',
            'description' => 'Expenses, currencies, and payment gateways',
            'permissions' => [
                'erp.expenses.view',
                'erp.expenses.viewAny',
                'erp.expenses.create',
                'erp.expenses.update',
                'erp.expenses.delete',
                'erp.expense_categories.view',
                'erp.expense_categories.viewAny',
                'erp.expense_categories.create',
                'erp.expense_categories.update',
                'erp.expense_categories.delete',
                'erp.currencies.view',
                'erp.currencies.viewAny',
                'erp.currencies.create',
                'erp.currencies.update',
                'erp.currencies.delete',
                'erp.payment_gateways.view',
                'erp.payment_gateways.viewAny',
                'erp.payment_gateways.create',
                'erp.payment_gateways.update',
                'erp.payment_gateways.delete',
            ],
        ],

        // HR Management Group
        'hr_management' => [
            'name' => 'HR Management',
            'description' => 'Employees, payroll, attendance, and HR operations',
            'permissions' => [
                'hr.departments.view',
                'hr.departments.viewAny',
                'hr.departments.create',
                'hr.departments.update',
                'hr.departments.delete',
                'hr.positions.view',
                'hr.positions.viewAny',
                'hr.positions.create',
                'hr.positions.update',
                'hr.positions.delete',
                'hr.employees.view',
                'hr.employees.viewAny',
                'hr.employees.create',
                'hr.employees.update',
                'hr.employees.delete',
                'hr.contracts.view',
                'hr.contracts.viewAny',
                'hr.contracts.create',
                'hr.contracts.update',
                'hr.contracts.delete',
                'hr.attendances.view',
                'hr.attendances.viewAny',
                'hr.attendances.create',
                'hr.attendances.update',
                'hr.attendances.delete',
                'hr.leave_requests.view',
                'hr.leave_requests.viewAny',
                'hr.leave_requests.create',
                'hr.leave_requests.update',
                'hr.leave_requests.delete',
                'hr.payrolls.view',
                'hr.payrolls.viewAny',
                'hr.payrolls.create',
                'hr.payrolls.update',
                'hr.payrolls.delete',
                'hr.recruitments.view',
                'hr.recruitments.viewAny',
                'hr.recruitments.create',
                'hr.recruitments.update',
                'hr.recruitments.delete',
                'hr.performance_reviews.view',
                'hr.performance_reviews.viewAny',
                'hr.performance_reviews.create',
                'hr.performance_reviews.update',
                'hr.performance_reviews.delete',
                'hr.trainings.view',
                'hr.trainings.viewAny',
                'hr.trainings.create',
                'hr.trainings.update',
                'hr.trainings.delete',
                'hr.training_assignments.view',
                'hr.training_assignments.viewAny',
                'hr.training_assignments.create',
                'hr.training_assignments.update',
                'hr.training_assignments.delete',
                'hr.employee_documents.view',
                'hr.employee_documents.viewAny',
                'hr.employee_documents.create',
                'hr.employee_documents.update',
                'hr.employee_documents.delete',
            ],
        ],

        // ERP Reporting Group
        'erp_reporting' => [
            'name' => 'Reporting',
            'description' => 'ERP reporting and analytics',
            'permissions' => [
                'erp.reports.view',
                'erp.reports.viewAny',
                'erp.reports.create',
                'erp.reports.update',
                'erp.reports.delete',
            ],
        ],
    ],
];



