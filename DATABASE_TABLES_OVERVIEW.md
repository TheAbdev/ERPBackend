# Database Tables & Relationships Overview – CRM & ERP System

## System Overview

This is a multi-tenant SaaS system where each company operates as a separate tenant. All data tables (except core system tables) include a `tenant_id` field to ensure complete data isolation. The system uses soft deletes for most tables, allowing data recovery when needed.

**Key Concepts:**
- **Tenant**: Represents a company/organization using the system
- **Multi-tenancy**: All business data is scoped by `tenant_id`
- **Soft Deletes**: Most tables support soft deletion (records are marked as deleted, not removed)
- **Audit Trail**: Comprehensive logging of all system changes
- **Relationships**: Tables are connected through foreign keys and pivot tables

---

## 1. Core System Tables

### tenants
**Purpose:** Represents companies/organizations using the system. Each tenant has complete data isolation.

**Key Fields:**
- `id` - Unique identifier
- `name` - Company name
- `slug` - URL-friendly identifier (unique)
- `subdomain` - Subdomain for tenant access (optional, unique)
- `domain` - Custom domain (optional, unique)
- `status` - active, suspended, inactive
- `settings` - JSON configuration data
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Has many: users, roles, and all business data tables
- All other tables belong to a tenant

---

### users
**Purpose:** System users who can log in and access the application.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Links user to tenant
- `name` - User's full name
- `email` - Email address (unique per tenant)
- `password` - Encrypted password
- `email_verified_at` - Email verification timestamp
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: tenant
- Has many: roles (through user_role pivot table)
- Has many: created records (via `created_by` foreign keys)
- Has many: assigned records (via `assigned_to` foreign keys)
- Has many: notifications, audit logs, activity feed entries

---

### roles
**Purpose:** Defines user roles within a tenant (e.g., Admin, Manager, Staff).

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Links role to tenant
- `name` - Role display name
- `slug` - Role identifier (unique per tenant)
- `description` - Role description
- `is_system` - Whether this is a system role (cannot be deleted)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant
- Belongs to many: users (through user_role pivot table)
- Belongs to many: permissions (through role_permission pivot table)

---

### permissions
**Purpose:** Defines granular permissions for system actions (e.g., `crm.leads.create`, `erp.products.view`).

**Key Fields:**
- `id` - Unique identifier
- `name` - Permission display name
- `slug` - Permission identifier (unique, format: `module.resource.action`)
- `module` - Module name (crm, erp, core)
- `description` - Permission description
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to many: roles (through role_permission pivot table)
- Note: Permissions are global, not tenant-specific

---

### user_role (Pivot Table)
**Purpose:** Links users to roles within a tenant context.

**Key Fields:**
- `id` - Unique identifier
- `user_id` - User reference
- `role_id` - Role reference
- `tenant_id` - Tenant context (ensures user-role assignment is tenant-scoped)
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: user, role, tenant
- Unique constraint: One user can have one role per tenant

---

### role_permission (Pivot Table)
**Purpose:** Links roles to permissions, defining what actions each role can perform.

**Key Fields:**
- `id` - Unique identifier
- `role_id` - Role reference
- `permission_id` - Permission reference
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: role, permission

---

### audit_logs
**Purpose:** Comprehensive audit trail of all system actions and changes.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `user_id` - User who performed the action (nullable)
- `action` - Action type (create, update, delete, login, logout, etc.)
- `model_type` - Type of entity affected (fully qualified class name)
- `model_id` - ID of affected entity
- `model_name` - Human-readable entity name
- `old_values` - JSON of values before change
- `new_values` - JSON of values after change
- `metadata` - Additional context (IP, user agent, etc.)
- `ip_address` - IP address of request
- `user_agent` - Browser/client information
- `request_id` - Request correlation ID
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: tenant, user (nullable)
- Polymorphic: Can reference any model via `model_type` and `model_id`

---

### erp_notifications
**Purpose:** User notifications for system events, approvals, and alerts.

**Key Fields:**
- `id` - Unique identifier (UUID)
- `tenant_id` - Tenant context
- `user_id` - Target user
- `entity_type` - Type of related entity (optional)
- `entity_id` - ID of related entity (optional)
- `type` - Notification type (info, warning, alert)
- `title` - Notification title
- `message` - Notification message
- `metadata` - Additional data (JSON)
- `read_at` - When notification was read (nullable)
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: tenant, user
- Polymorphic: Can reference any entity via `entity_type` and `entity_id`

---

### erp_activity_feed
**Purpose:** Activity feed showing recent actions across the system.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `user_id` - User who performed the action (nullable)
- `entity_type` - Type of entity (e.g., SalesInvoice, Deal, Product)
- `entity_id` - ID of entity
- `action` - Action performed (created, updated, approved, rejected, etc.)
- `metadata` - Additional context (JSON)
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: tenant, user (nullable)
- Polymorphic: Can reference any entity via `entity_type` and `entity_id`

---

## 2. CRM Tables

### leads
**Purpose:** Sales leads - potential customers who have shown interest.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `name` - Lead name
- `email` - Email address
- `phone` - Phone number
- `source` - Lead source (website, referral, etc.)
- `status` - Lead status (new, contacted, qualified, converted, lost)
- `assigned_to` - User assigned to this lead (nullable)
- `created_by` - User who created the lead
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, assigned user, creator
- Has many: contacts (leads can be converted to contacts)
- Has many: deals (leads can be converted to deals)
- Has many: notes, activities

---

### contacts
**Purpose:** Individual contacts (people) associated with companies or leads.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `lead_id` - Original lead (if converted from lead, nullable)
- `first_name` - Contact first name
- `last_name` - Contact last name
- `email` - Email address
- `phone` - Phone number
- `job_title` - Job title
- `notes` - Additional notes
- `created_by` - User who created the contact
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, lead (nullable), creator
- Belongs to many: accounts (through account_contact pivot table)
- Has many: deals, notes, activities

---

### accounts
**Purpose:** Company accounts (organizations) in the CRM system.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `parent_id` - Parent account (for account hierarchy, nullable)
- `name` - Account name
- `email` - Account email
- `phone` - Phone number
- `website` - Website URL
- `industry` - Industry classification
- `address` - Physical address
- `created_by` - User who created the account
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, parent account (nullable), creator
- Has many: child accounts (self-referential)
- Belongs to many: contacts (through account_contact pivot table)
- Has many: deals, notes, activities

---

### pipelines
**Purpose:** Sales pipelines defining the sales process stages.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `name` - Pipeline name
- `is_default` - Whether this is the default pipeline
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant
- Has many: stages, deals

---

### pipeline_stages
**Purpose:** Stages within a sales pipeline (e.g., Qualification, Proposal, Negotiation).

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `pipeline_id` - Parent pipeline
- `name` - Stage name
- `position` - Display order within pipeline
- `probability` - Win probability percentage (0-100)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, pipeline
- Has many: deals

---

### deals
**Purpose:** Sales opportunities - potential sales transactions.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `pipeline_id` - Associated pipeline
- `stage_id` - Current stage
- `lead_id` - Source lead (nullable)
- `contact_id` - Associated contact (nullable)
- `account_id` - Associated account (nullable)
- `title` - Deal title
- `amount` - Deal value
- `currency` - Currency code (3 letters)
- `probability` - Win probability (0-100)
- `expected_close_date` - Expected closing date
- `status` - Deal status (open, won, lost)
- `created_by` - User who created the deal
- `assigned_to` - User assigned to the deal (nullable)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, pipeline, stage, lead (nullable), contact (nullable), account (nullable), creator, assigned user
- Has many: notes, activities, deal histories

---

### activities
**Purpose:** Tasks, calls, meetings, and other activities related to CRM entities.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `type` - Activity type (task, call, meeting)
- `subject` - Activity subject
- `description` - Activity description
- `due_date` - Due date and time
- `priority` - Priority level (low, medium, high)
- `status` - Status (pending, completed, canceled)
- `related_type` - Type of related entity (lead, contact, account, deal)
- `related_id` - ID of related entity
- `assigned_to` - User assigned to activity (nullable)
- `created_by` - User who created the activity
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, assigned user (nullable), creator
- Polymorphic: Can reference any CRM entity via `related_type` and `related_id`

---

### notes
**Purpose:** Notes attached to CRM entities (leads, contacts, accounts, deals).

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `noteable_type` - Type of entity (lead, contact, account, deal)
- `noteable_id` - ID of entity
- `body` - Note content
- `created_by` - User who created the note
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, creator
- Polymorphic: Can reference any CRM entity via `noteable_type` and `noteable_id`

---

### erp_workflows (CRM Workflows)
**Purpose:** Workflow definitions for CRM automation and approval processes.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `entity_type` - Entity type this workflow applies to
- `name` - Workflow name
- `description` - Workflow description
- `is_active` - Whether workflow is active
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant
- Has many: workflow steps, workflow instances

---

## 3. ERP Tables

### products
**Purpose:** Product catalog - items sold or purchased by the company.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `category_id` - Product category (nullable)
- `sku` - Stock Keeping Unit (unique per tenant)
- `name` - Product name
- `description` - Product description
- `barcode` - Barcode/EAN
- `unit_of_measure_id` - Unit of measurement
- `is_tracked` - Whether inventory is tracked
- `is_serialized` - Whether serial numbers are tracked
- `is_batch_tracked` - Whether batch/lot numbers are tracked
- `type` - Product type (stock, service, kit)
- `is_active` - Whether product is active
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, category (nullable), unit of measure
- Has many: product variants, stock items, sales order items, purchase order items

---

### product_categories
**Purpose:** Product categorization for organizing the product catalog.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `parent_id` - Parent category (for hierarchy, nullable)
- `code` - Category code (unique per tenant)
- `name` - Category name
- `description` - Category description
- `is_active` - Whether category is active
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, parent category (nullable)
- Has many: child categories, products

---

### stock_items
**Purpose:** Current inventory levels for products in warehouses.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `warehouse_id` - Warehouse location
- `product_id` - Product reference
- `product_variant_id` - Product variant (nullable)
- `quantity_on_hand` - Current stock quantity
- `reserved_quantity` - Quantity reserved for orders
- `available_quantity` - Available quantity (on_hand - reserved)
- `average_cost` - Average cost for valuation
- `last_cost` - Last purchase cost
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, warehouse, product, product variant (nullable)
- Unique constraint: One stock item per product/variant per warehouse

---

### inventory_transactions
**Purpose:** History of all inventory movements (receipts, issues, adjustments, transfers).

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `warehouse_id` - Warehouse
- `product_id` - Product
- `product_variant_id` - Product variant (nullable)
- `batch_id` - Batch/lot number (nullable)
- `transaction_type` - Type (opening_balance, adjustment, transfer, receipt, issue)
- `reference_type` - Source document type (nullable)
- `reference_id` - Source document ID (nullable)
- `quantity` - Quantity (positive for receipts, negative for issues)
- `unit_cost` - Cost per unit
- `total_cost` - Total cost (quantity × unit_cost)
- `unit_of_measure_id` - Unit of measurement
- `base_quantity` - Quantity in base unit
- `notes` - Transaction notes
- `created_by` - User who created the transaction
- `transaction_date` - Transaction date
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, warehouse, product, product variant (nullable), batch (nullable), unit of measure, creator
- Polymorphic: Can reference source documents via `reference_type` and `reference_id`

---

### sales_orders
**Purpose:** Customer sales orders - orders placed by customers.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `order_number` - Unique order number
- `order_date` - Order date
- `delivery_date` - Expected delivery date (nullable)
- `warehouse_id` - Fulfillment warehouse
- `currency_id` - Currency
- `customer_name` - Customer name
- `customer_email` - Customer email (nullable)
- `customer_phone` - Customer phone (nullable)
- `customer_address` - Customer address (nullable)
- `status` - Order status (draft, confirmed, partially_delivered, delivered, cancelled)
- `subtotal` - Subtotal amount
- `tax_amount` - Tax amount
- `discount_amount` - Discount amount
- `total_amount` - Total amount
- `notes` - Order notes
- `created_by` - User who created the order
- `confirmed_by` - User who confirmed the order (nullable)
- `confirmed_at` - Confirmation timestamp (nullable)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, warehouse, currency, creator, confirmed user (nullable)
- Has many: sales order items

---

### sales_order_items
**Purpose:** Line items within a sales order.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `sales_order_id` - Parent sales order
- `product_id` - Product
- `product_variant_id` - Product variant (nullable)
- `unit_of_measure_id` - Unit of measurement
- `quantity` - Ordered quantity
- `base_quantity` - Quantity in base unit
- `unit_price` - Price per unit
- `discount_percentage` - Discount percentage
- `discount_amount` - Discount amount
- `tax_percentage` - Tax percentage
- `tax_amount` - Tax amount
- `line_total` - Line total (quantity × unit_price - discount + tax)
- `delivered_quantity` - Quantity delivered
- `notes` - Line item notes
- `line_number` - Line number in order
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, sales order, product, product variant (nullable), unit of measure

---

### purchase_orders
**Purpose:** Purchase orders - orders placed with suppliers.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `order_number` - Unique order number
- `order_date` - Order date
- `expected_delivery_date` - Expected delivery date (nullable)
- `warehouse_id` - Receiving warehouse
- `currency_id` - Currency
- `supplier_name` - Supplier name
- `supplier_email` - Supplier email (nullable)
- `supplier_phone` - Supplier phone (nullable)
- `supplier_address` - Supplier address (nullable)
- `status` - Order status (draft, confirmed, partially_received, received, cancelled)
- `subtotal` - Subtotal amount
- `tax_amount` - Tax amount
- `discount_amount` - Discount amount
- `total_amount` - Total amount
- `notes` - Order notes
- `created_by` - User who created the order
- `confirmed_by` - User who confirmed the order (nullable)
- `confirmed_at` - Confirmation timestamp (nullable)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, warehouse, currency, creator, confirmed user (nullable)
- Has many: purchase order items

---

### purchase_order_items
**Purpose:** Line items within a purchase order.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `purchase_order_id` - Parent purchase order
- `product_id` - Product
- `product_variant_id` - Product variant (nullable)
- `unit_of_measure_id` - Unit of measurement
- `quantity` - Ordered quantity
- `base_quantity` - Quantity in base unit
- `unit_price` - Price per unit
- `discount_percentage` - Discount percentage
- `discount_amount` - Discount amount
- `tax_percentage` - Tax percentage
- `tax_amount` - Tax amount
- `line_total` - Line total
- `received_quantity` - Quantity received
- `notes` - Line item notes
- `line_number` - Line number in order
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, purchase order, product, product variant (nullable), unit of measure

---

### chart_of_accounts
**Purpose:** Chart of accounts for financial reporting and accounting.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `parent_id` - Parent account (for account hierarchy, nullable)
- `code` - Account code (unique per tenant)
- `name` - Account name
- `type` - Account type (asset, liability, equity, revenue, expense)
- `description` - Account description
- `is_active` - Whether account is active
- `display_order` - Display order
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, parent account (nullable)
- Has many: child accounts, journal entry lines

---

### journal_entries
**Purpose:** Manual journal entries for accounting adjustments.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `entry_number` - Unique entry number (unique per tenant)
- `fiscal_year_id` - Fiscal year
- `fiscal_period_id` - Fiscal period
- `entry_date` - Entry date
- `reference_type` - Source document type (nullable)
- `reference_id` - Source document ID (nullable)
- `description` - Entry description
- `status` - Entry status (draft, posted)
- `created_by` - User who created the entry
- `posted_by` - User who posted the entry (nullable)
- `posted_at` - Posting timestamp (nullable)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, fiscal year, fiscal period, creator, posted user (nullable)
- Has many: journal entry lines
- Polymorphic: Can reference source documents via `reference_type` and `reference_id`

---

### journal_entry_lines
**Purpose:** Debit and credit lines within a journal entry.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `journal_entry_id` - Parent journal entry
- `account_id` - Account
- `currency_id` - Currency
- `debit` - Debit amount
- `credit` - Credit amount
- `amount_base` - Amount in base currency
- `description` - Line description
- `line_number` - Line number in entry
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, journal entry, account, currency

---

## 4. Reporting & System Tables

### erp_reports
**Purpose:** Report definitions for generating various business reports.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `name` - Report name
- `type` - Report type (sales_summary, purchase_summary, inventory, financial, etc.)
- `module` - Module (ERP, CRM)
- `filters` - Report filters (JSON)
- `description` - Report description
- `is_active` - Whether report is active
- `created_by` - User who created the report
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, creator
- Has many: report schedules

---

### erp_report_schedules
**Purpose:** Scheduled report generation and delivery.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `report_id` - Report to generate
- `cron_expression` - Schedule expression (cron format)
- `last_run_at` - Last execution timestamp (nullable)
- `next_run_at` - Next execution timestamp (nullable)
- `is_active` - Whether schedule is active
- `recipients` - Recipient list (JSON array of user IDs or emails)
- `format` - Export format (pdf, excel, csv)
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, report

---

### erp_system_settings
**Purpose:** Tenant-specific system configuration settings.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `key` - Setting key (unique per tenant)
- `value` - Setting value
- `type` - Value type (string, integer, boolean, json)
- `module` - Module this setting belongs to
- `is_encrypted` - Whether value is encrypted
- `created_by` - User who created the setting
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Relationships:**
- Belongs to: tenant, creator

---

### erp_system_health
**Purpose:** System health monitoring and metrics.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `cpu_usage` - CPU usage percentage
- `memory_usage` - Memory usage percentage
- `disk_usage` - Disk usage percentage
- `database_connections` - Active database connections
- `queue_size` - Queue size
- `status` - Health status (healthy, warning, critical)
- `message` - Status message
- `checked_at` - Health check timestamp
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: tenant

---

### erp_webhooks
**Purpose:** Webhook configurations for external integrations.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `url` - Webhook URL
- `secret` - Webhook secret for HMAC signing
- `is_active` - Whether webhook is active
- `module` - Module (ERP, CRM)
- `event_types` - Subscribed event types (JSON array)
- `last_delivery_status` - Last delivery status (success, failure, nullable)
- `last_delivery_at` - Last delivery timestamp (nullable)
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: tenant
- Has many: webhook deliveries

---

### erp_webhook_deliveries
**Purpose:** Webhook delivery history and retry tracking.

**Key Fields:**
- `id` - Unique identifier
- `tenant_id` - Tenant context
- `webhook_id` - Webhook reference
- `event_type` - Event type
- `payload` - Delivery payload (JSON)
- `status` - Delivery status (pending, success, failed)
- `response_code` - HTTP response code (nullable)
- `response_body` - Response body (nullable)
- `error_message` - Error message (nullable)
- `attempts` - Number of delivery attempts
- `delivered_at` - Delivery timestamp (nullable)
- `created_at`, `updated_at` - Timestamps

**Relationships:**
- Belongs to: tenant, webhook

---

## 5. Relationship Summary

### Tenant → Users → Data Ownership

**Hierarchy:**
- **Tenant** is the top-level entity representing a company
- **Users** belong to a tenant and can only access data for their tenant
- All business data tables include `tenant_id` to ensure complete isolation
- Users are assigned **Roles** within a tenant context
- Roles have **Permissions** that define what actions users can perform

**Data Isolation:**
- Every query automatically filters by `tenant_id`
- Users cannot access data from other tenants
- All foreign key relationships respect tenant boundaries

---

### CRM Data Flow

**Lead Conversion Flow:**
1. **Leads** are created from potential customers
2. Leads can be converted to **Contacts** (individuals)
3. Contacts can be associated with **Accounts** (companies)
4. Leads or Contacts can be converted to **Deals** (sales opportunities)

**Deal Management:**
1. **Deals** are created and assigned to a **Pipeline**
2. Deals move through **Pipeline Stages** as they progress
3. Each stage has a probability percentage
4. Deals can be won or lost

**Activity Tracking:**
- **Activities** (tasks, calls, meetings) can be linked to any CRM entity
- **Notes** can be attached to leads, contacts, accounts, or deals
- All actions are logged in **audit_logs** and **activity_feed**

---

### ERP Data Flow

**Product Management:**
1. **Products** are organized into **Product Categories**
2. Products can have variants (sizes, colors, etc.)
3. **Stock Items** track inventory levels per warehouse
4. **Inventory Transactions** record all stock movements

**Order Processing:**
1. **Sales Orders** are created for customer orders
2. **Sales Order Items** define the products and quantities
3. Orders can be confirmed, delivered, or cancelled
4. **Purchase Orders** work similarly for supplier orders

**Accounting Integration:**
1. Orders and transactions can generate **Journal Entries**
2. Journal entries have **Journal Entry Lines** (debits and credits)
3. Lines reference **Chart of Accounts** accounts
4. All entries are posted to maintain accurate financial records

---

### CRM and ERP Integration

**Shared Concepts:**
- Both modules use the same **Tenant**, **User**, and **Role** structure
- Both share **Notifications**, **Activity Feed**, and **Audit Logs**
- Both support **Workflows** for approval processes

**Data Connections:**
- CRM **Accounts** can be linked to ERP customers/suppliers
- Sales orders can reference CRM deals
- Financial transactions from ERP can be tracked back to CRM activities
- Both modules share the same user base and permissions system

**Unified Experience:**
- Users see a unified dashboard combining CRM and ERP metrics
- Notifications and activity feed show events from both modules
- Reports can span both CRM and ERP data
- Workflows can integrate approval processes across modules

---

## Important Notes for Frontend Developers

1. **Always Include tenant_id**: When creating or updating records, ensure `tenant_id` is included (usually handled automatically by backend).

2. **Soft Deletes**: Most tables support soft deletes. Deleted records have a `deleted_at` timestamp but are not removed from the database.

3. **Polymorphic Relationships**: Some tables use polymorphic relationships (`entity_type` + `entity_id` or `noteable_type` + `noteable_id`). These can reference multiple different entity types.

4. **Timestamps**: All tables include `created_at` and `updated_at`. Tables with soft deletes also include `deleted_at`.

5. **Status Fields**: Many tables have `status` fields with specific enum values. Check API documentation for valid status values.

6. **Foreign Keys**: Most relationships use foreign keys. When displaying related data, ensure you're loading the relationships (eager loading) to avoid N+1 query problems.

7. **JSON Fields**: Some fields store JSON data (`settings`, `metadata`, `filters`, etc.). These should be parsed and handled as objects/arrays in the frontend.

8. **Unique Constraints**: Some fields have unique constraints per tenant (e.g., `sku` in products, `code` in accounts). Ensure validation on the frontend before submission.

