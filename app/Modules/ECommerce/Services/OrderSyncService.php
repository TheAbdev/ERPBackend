<?php

namespace App\Modules\ECommerce\Services;

use App\Core\Services\TenantContext;
use App\Modules\ECommerce\Models\Order;
use App\Modules\ECommerce\Models\Customer;
use App\Modules\ERP\Models\SalesOrder;
use App\Modules\ERP\Models\SalesOrderItem;
use App\Modules\ERP\Models\Product;
use App\Modules\CRM\Models\Contact;
use Illuminate\Support\Facades\DB;

class OrderSyncService
{
    protected TenantContext $tenantContext;

    public function __construct(TenantContext $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Convert ecommerce order to ERP sales order.
     *
     * @param  Order  $order
     * @param  int|null  $tenantId
     * @return SalesOrder
     */
    public function convertToSalesOrder(Order $order, ?int $tenantId = null): SalesOrder
    {
        // Use provided tenantId or fallback to order's tenant_id
        $tenantId = $tenantId ?? $order->tenant_id;

        return DB::transaction(function () use ($order, $tenantId) {
            // Map ecommerce customer to ERP contact
            $contactId = null;
            $customerName = 'Guest Customer';
            $customerEmail = '';
            $customerPhone = '';

            if ($order->customer_id) {
                $customer = Customer::with('contact')->find($order->customer_id);
                if ($customer) {
                    $customerName = $customer->name;
                    $customerEmail = $customer->email;
                    $customerPhone = $customer->phone;

                    // If customer already has a contact, use it
                    if ($customer->contact_id) {
                        $contactId = $customer->contact_id;
                    } else {
                        // Create a new contact from customer
                        $contact = $this->createContactFromCustomer($customer);
                        $contactId = $contact->id;

                        // Link customer to contact
                        $customer->contact_id = $contactId;
                        $customer->save();
                    }
                }
            } elseif ($order->billing_address) {
                // Extract customer info from billing address
                $customerName = $order->billing_address['name'] ?? 'Guest Customer';
                $customerEmail = $order->billing_address['email'] ?? '';
                $customerPhone = $order->billing_address['phone'] ?? '';
            }

            // Get first warehouse for tenant (default warehouse)
            $warehouse = \App\Modules\ERP\Models\Warehouse::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (!$warehouse) {
                throw new \Exception('No active warehouse found for tenant');
            }

            // Get default currency (USD)
            $currency = \App\Modules\ERP\Models\Currency::where('code', $order->currency ?? 'USD')->first()
                ?? \App\Modules\ERP\Models\Currency::where('code', 'USD')->first();

            if (!$currency) {
                throw new \Exception('No currency found for order');
            }

            // Get the first admin user for created_by
            $creator = \App\Models\User::where('tenant_id', $tenantId)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'super_admin');
                })
                ->first();

            // Create sales order
            $salesOrder = SalesOrder::create([
                'tenant_id' => $tenantId,
                'order_number' => $this->generateSalesOrderNumber(),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'customer_address' => isset($order->shipping_address) ? ($order->shipping_address['address'] ?? '') : '',
                'order_date' => now(),
                'delivery_date' => null,
                'warehouse_id' => $warehouse->id,
                'currency_id' => $currency->id,
                'status' => 'draft',
                'subtotal' => $order->subtotal ?? 0,
                'tax_amount' => $order->tax ?? 0,
                'discount_amount' => $order->discount ?? 0,
                'total_amount' => $order->total ?? 0,
                'notes' => $order->notes,
                'created_by' => $creator?->id ?? 1, // Fallback to user 1 if no admin found
            ]);

            // Create sales order items
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);

                // Get unit of measure ID
              /*  $unitOfMeasure = \App\Modules\ERP\Models\UnitOfMeasure::where('code', $product->unit_of_measure ?? 'pcs')->first();
                if (!$unitOfMeasure) {
                    // Fallback to first available unit of measure
                    $unitOfMeasure = \App\Modules\ERP\Models\UnitOfMeasure::first();
                }*/

                SalesOrderItem::create([
                    'tenant_id' => $tenantId,
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->variant_id,
                    'unit_of_measure' => $item->unit_of_measure, // Fallback to unit 1
                    'quantity' => $item->quantity,
                    'base_quantity' => $item->quantity, // Same as quantity for ecommerce orders
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->total,
                ]);
            }

            // Link ecommerce order to sales order
            $order->sales_order_id = $salesOrder->id;
            $order->save();

            return $salesOrder;
        });
    }

    /**
     * Create a CRM contact from ecommerce customer.
     *
     * @param  Customer  $customer
     * @return Contact
     */
    protected function createContactFromCustomer(Customer $customer): Contact
    {
        $nameParts = explode(' ', $customer->name, 2);
        $firstName = $nameParts[0] ?? $customer->name;
        $lastName = $nameParts[1] ?? '';

        return Contact::create([
            'tenant_id' => $customer->tenant_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'notes' => "Created from ecommerce customer: {$customer->name}",
            'created_by' => null, // System-created contact
        ]);
    }

    /**
     * Generate sales order number.
     *
     * @return string
     */
    protected function generateSalesOrderNumber(): string
    {
        $prefix = 'SO';
        $date = now()->format('Ymd');
        $lastOrder = SalesOrder::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastOrder ? (int) substr($lastOrder->order_number, -4) + 1 : 1;

        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Update ecommerce order status when sales order status changes.
     *
     * @param  SalesOrder  $salesOrder
     * @return void
     */
    public function onSalesOrderUpdated(SalesOrder $salesOrder): void
    {
        $order = Order::where('sales_order_id', $salesOrder->id)->first();

        if ($order) {
            // Map sales order status to ecommerce order status
            $statusMap = [
                'pending' => 'pending',
                'confirmed' => 'processing',
                'shipped' => 'shipped',
                'delivered' => 'delivered',
                'cancelled' => 'cancelled',
            ];

            $order->status = $statusMap[$salesOrder->status] ?? $order->status;
            $order->save();
        }
    }
}

