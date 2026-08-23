<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Canonical;

use App\Extensions\Migration\System\Canonical\Contracts\CanonicalEntityPackInterface;

final class BuiltinCanonicalEntityCatalog implements CanonicalEntityPackInterface
{
    /** @return array<int, CanonicalEntityDefinition> */
    public function definitions(): array
    {
        return [
            $this->entity('core.company', 'Company', ['external_id', 'name'], ['external_id']),
            $this->entity('core.customer', 'Customer', ['external_id', 'name', 'email', 'phone'], ['external_id']),
            $this->entity('core.contact', 'Contact', ['external_id', 'customer_external_id', 'name', 'email', 'phone'], ['external_id'], ['core.customer']),
            $this->entity('core.address', 'Address', ['external_id', 'owner_external_id', 'line1', 'city', 'postcode', 'country'], ['external_id']),
            $this->entity('core.product', 'Product', ['external_id', 'name', 'sku', 'price'], ['external_id']),
            $this->entity('core.service', 'Service', ['external_id', 'name', 'sku', 'price'], ['external_id']),
            $this->entity('core.invoice', 'Invoice', ['external_id', 'customer_external_id', 'number', 'status', 'total'], ['external_id'], ['core.customer']),
            $this->entity('core.invoice_line', 'Invoice Line', ['external_id', 'invoice_external_id', 'description', 'quantity', 'amount'], ['external_id'], ['core.invoice']),
            $this->entity('core.payment', 'Payment', ['external_id', 'invoice_external_id', 'amount', 'status', 'paid_at'], ['external_id'], ['core.invoice']),
            $this->entity('core.note', 'Note', ['external_id', 'owner_external_id', 'body'], ['external_id']),
            $this->entity('core.attachment', 'Attachment', ['external_id', 'owner_external_id', 'filename', 'uri'], ['external_id']),

            $this->entity('field.job', 'Field Job', ['external_id', 'customer_external_id', 'status', 'scheduled_at'], ['external_id'], ['core.customer'], 'vertical.field_service'),
            $this->entity('field.work_order', 'Work Order', ['external_id', 'job_external_id', 'status', 'summary'], ['external_id'], ['field.job'], 'vertical.field_service'),
            $this->entity('field.technician', 'Technician', ['external_id', 'name', 'email', 'phone'], ['external_id'], [], 'vertical.field_service'),
            $this->entity('field.asset', 'Service Asset', ['external_id', 'customer_external_id', 'name', 'serial_number'], ['external_id'], ['core.customer'], 'vertical.field_service'),

            $this->entity('accommodation.property', 'Accommodation Property', ['external_id', 'name', 'address_external_id'], ['external_id'], ['core.address'], 'vertical.accommodation'),
            $this->entity('accommodation.room', 'Room', ['external_id', 'property_external_id', 'name', 'capacity'], ['external_id'], ['accommodation.property'], 'vertical.accommodation'),
            $this->entity('accommodation.guest', 'Guest', ['external_id', 'name', 'email', 'phone'], ['external_id'], [], 'vertical.accommodation'),
            $this->entity('accommodation.reservation', 'Reservation', ['external_id', 'guest_external_id', 'room_external_id', 'starts_at', 'ends_at', 'status'], ['external_id'], ['accommodation.guest', 'accommodation.room'], 'vertical.accommodation'),

            $this->entity('real_estate.property', 'Real Estate Property', ['external_id', 'address_external_id', 'status'], ['external_id'], ['core.address'], 'vertical.real_estate'),
            $this->entity('real_estate.tenant', 'Tenant', ['external_id', 'name', 'email', 'phone'], ['external_id'], [], 'vertical.real_estate'),
            $this->entity('real_estate.lease', 'Lease', ['external_id', 'property_external_id', 'tenant_external_id', 'starts_at', 'ends_at', 'rent'], ['external_id'], ['real_estate.property', 'real_estate.tenant'], 'vertical.real_estate'),
            $this->entity('real_estate.inspection', 'Property Inspection', ['external_id', 'property_external_id', 'scheduled_at', 'status'], ['external_id'], ['real_estate.property'], 'vertical.real_estate'),

            $this->entity('salon.client', 'Salon Client', ['external_id', 'name', 'email', 'phone'], ['external_id'], [], 'vertical.salon'),
            $this->entity('salon.practitioner', 'Practitioner', ['external_id', 'name', 'email'], ['external_id'], [], 'vertical.salon'),
            $this->entity('salon.treatment', 'Treatment', ['external_id', 'name', 'duration_minutes', 'price'], ['external_id'], [], 'vertical.salon'),
            $this->entity('salon.appointment', 'Salon Appointment', ['external_id', 'client_external_id', 'practitioner_external_id', 'treatment_external_id', 'starts_at', 'status'], ['external_id'], ['salon.client', 'salon.practitioner', 'salon.treatment'], 'vertical.salon'),

            $this->entity('fitness.member', 'Fitness Member', ['external_id', 'name', 'email', 'phone'], ['external_id'], [], 'vertical.fitness'),
            $this->entity('fitness.membership', 'Membership', ['external_id', 'member_external_id', 'plan_name', 'status', 'starts_at', 'ends_at'], ['external_id'], ['fitness.member'], 'vertical.fitness'),
            $this->entity('fitness.class', 'Fitness Class', ['external_id', 'name', 'capacity', 'starts_at'], ['external_id'], [], 'vertical.fitness'),
            $this->entity('fitness.booking', 'Class Booking', ['external_id', 'member_external_id', 'class_external_id', 'status'], ['external_id'], ['fitness.member', 'fitness.class'], 'vertical.fitness'),

            $this->entity('automotive.vehicle', 'Vehicle', ['external_id', 'customer_external_id', 'vin', 'registration', 'make', 'model'], ['external_id'], ['core.customer'], 'vertical.automotive'),
            $this->entity('automotive.service_job', 'Automotive Service Job', ['external_id', 'vehicle_external_id', 'status', 'scheduled_at'], ['external_id'], ['automotive.vehicle'], 'vertical.automotive'),
            $this->entity('automotive.repair_order', 'Repair Order', ['external_id', 'service_job_external_id', 'status', 'total'], ['external_id'], ['automotive.service_job'], 'vertical.automotive'),

            $this->entity('ecommerce.product_variant', 'Product Variant', ['external_id', 'product_external_id', 'sku', 'price'], ['external_id'], ['core.product'], 'vertical.ecommerce'),
            $this->entity('ecommerce.order', 'E-commerce Order', ['external_id', 'customer_external_id', 'status', 'total', 'ordered_at'], ['external_id'], ['core.customer'], 'vertical.ecommerce'),
            $this->entity('ecommerce.order_line', 'E-commerce Order Line', ['external_id', 'order_external_id', 'variant_external_id', 'quantity', 'amount'], ['external_id'], ['ecommerce.order', 'ecommerce.product_variant'], 'vertical.ecommerce'),
            $this->entity('ecommerce.inventory', 'Inventory', ['external_id', 'variant_external_id', 'location', 'quantity'], ['external_id'], ['ecommerce.product_variant'], 'vertical.ecommerce'),

            $this->entity('hire.rental_asset', 'Rental Asset', ['external_id', 'name', 'sku', 'status'], ['external_id'], [], 'vertical.hire'),
            $this->entity('hire.rental_booking', 'Rental Booking', ['external_id', 'asset_external_id', 'customer_external_id', 'starts_at', 'ends_at', 'status'], ['external_id'], ['hire.rental_asset', 'core.customer'], 'vertical.hire'),
            $this->entity('hire.rental_contract', 'Rental Contract', ['external_id', 'booking_external_id', 'status', 'total'], ['external_id'], ['hire.rental_booking'], 'vertical.hire'),

            $this->entity('booking.resource', 'Bookable Resource', ['external_id', 'name', 'capacity'], ['external_id'], [], 'vertical.booking'),
            $this->entity('booking.capacity_slot', 'Capacity Slot', ['external_id', 'resource_external_id', 'starts_at', 'ends_at', 'capacity'], ['external_id'], ['booking.resource'], 'vertical.booking'),
            $this->entity('booking.resource_booking', 'Resource Booking', ['external_id', 'resource_external_id', 'customer_external_id', 'starts_at', 'ends_at', 'status'], ['external_id'], ['booking.resource', 'core.customer'], 'vertical.booking'),
        ];
    }

    /**
     * @param array<int, string> $fields
     * @param array<int, string> $identityFields
     * @param array<int, string> $dependencies
     */
    private function entity(
        string $key,
        string $name,
        array $fields,
        array $identityFields,
        array $dependencies = [],
        ?string $module = null,
    ): CanonicalEntityDefinition {
        $schema = [];
        foreach ($fields as $field) {
            $schema[$field] = [
                'type' => $this->typeFor($field),
                'required' => in_array($field, $identityFields, true) || in_array($field, ['name'], true),
            ];
        }

        return new CanonicalEntityDefinition(
            key: $key,
            name: $name,
            fields: $schema,
            identityRules: [['fields' => $identityFields, 'match' => 'exact']],
            dependencyKeys: $dependencies,
            requiredModules: $module === null ? [] : [$module],
            handlerKey: $key,
        );
    }

    private function typeFor(string $field): string
    {
        return match (true) {
            str_ends_with($field, '_at'), str_ends_with($field, '_date'), in_array($field, ['starts_at', 'ends_at', 'paid_at', 'ordered_at'], true) => 'datetime',
            in_array($field, ['capacity', 'quantity', 'duration_minutes'], true) => 'integer',
            in_array($field, ['price', 'amount', 'total', 'rent'], true) => 'number',
            default => 'string',
        };
    }
}
