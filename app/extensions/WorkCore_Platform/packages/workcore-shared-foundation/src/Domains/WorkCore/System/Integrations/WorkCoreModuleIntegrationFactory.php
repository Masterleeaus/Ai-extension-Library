<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Integrations;

use Illuminate\Database\ConnectionInterface;

final class WorkCoreModuleIntegrationFactory
{
    public function __construct(private ConnectionInterface $connection) {}

    public function createBusinessNetworkIntegration(): WorkCoreModuleIntegrationContract
    {
        return new class implements WorkCoreModuleIntegrationContract {
            public function __construct(private ConnectionInterface $connection) {}

            public function moduleName(): string { return 'business_network'; }
            public function extensionName(): string { return 'AiChatPro'; }
            public function canQuery(): bool { return true; }
            public function canMutate(): bool { return true; }

            public function query(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'list_contacts' => $this->listContacts($params),
                    'get_contact' => $this->getContact($params),
                    'search_contacts' => $this->searchContacts($params),
                    'list_companies' => $this->listCompanies($params),
                    'get_company' => $this->getCompany($params),
                    'list_interactions' => $this->listInteractions($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function mutate(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'create_contact' => $this->createContact($params),
                    'update_contact' => $this->updateContact($params),
                    'delete_contact' => $this->deleteContact($params),
                    'log_interaction' => $this->logInteraction($params),
                    'assign_contact' => $this->assignContact($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function getAvailableOperations(): array
            {
                return [
                    'queries' => [
                        'list_contacts' => 'Retrieve all contacts for tenant',
                        'get_contact' => 'Get single contact by ID',
                        'search_contacts' => 'Search contacts by criteria',
                        'list_companies' => 'List all companies',
                        'get_company' => 'Get single company by ID',
                        'list_interactions' => 'List contact interactions',
                    ],
                    'mutations' => [
                        'create_contact' => 'Create new contact',
                        'update_contact' => 'Update existing contact',
                        'delete_contact' => 'Delete contact',
                        'log_interaction' => 'Log customer interaction',
                        'assign_contact' => 'Assign contact to user',
                    ],
                ];
            }

            public function validatePermissions(int $tenantId, int $actorId, string $operation): bool
            {
                $allowedOps = array_merge(
                    array_keys($this->getAvailableOperations()['queries'] ?? []),
                    array_keys($this->getAvailableOperations()['mutations'] ?? [])
                );

                return in_array($operation, $allowedOps, true);
            }

            public function enrichContextWithModuleData(array $context): array
            {
                $context['module'] = 'business_network';
                $context['capabilities'] = [
                    'contact_management' => true,
                    'interaction_tracking' => true,
                    'company_hierarchy' => true,
                ];
                return $context;
            }

            private function listContacts(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('contacts')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getContact(array $params): array
            {
                $contactId = $params['contactId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$contactId) {
                    return ['error' => 'contactId required'];
                }

                $record = $this->connection->table('contacts')
                    ->where('id', $contactId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Contact not found'];
            }

            private function searchContacts(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $query = $params['query'] ?? '';

                $records = $this->connection->table('contacts')
                    ->where('tenant_id', $tenantId)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function listCompanies(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('companies')
                    ->where('id', $tenantId)
                    ->orWhere('parent_company_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getCompany(array $params): array
            {
                $companyId = $params['companyId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$companyId) {
                    return ['error' => 'companyId required'];
                }

                $record = $this->connection->table('companies')
                    ->where('id', $companyId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Company not found'];
            }

            private function listInteractions(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $contactId = $params['contactId'] ?? null;
                $limit = $params['limit'] ?? 50;

                $query = $this->connection->table('customer_interactions')
                    ->where('tenant_id', $tenantId);

                if ($contactId) {
                    $query->where('contact_id', $contactId);
                }

                $records = $query->limit($limit)->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function createContact(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $name = $params['name'] ?? null;
                $email = $params['email'] ?? null;

                if (!$name || !$email) {
                    return ['error' => 'name and email required'];
                }

                $id = $this->connection->table('contacts')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'email' => $email,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function updateContact(array $params): array
            {
                $contactId = $params['contactId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$contactId) {
                    return ['error' => 'contactId required'];
                }

                $updated = $this->connection->table('contacts')
                    ->where('id', $contactId)
                    ->where('tenant_id', $tenantId)
                    ->update(array_merge($params, ['updated_at' => now()]));

                return $updated
                    ? ['success' => true, 'updated' => $updated]
                    : ['error' => 'Contact not found'];
            }

            private function deleteContact(array $params): array
            {
                $contactId = $params['contactId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$contactId) {
                    return ['error' => 'contactId required'];
                }

                $deleted = $this->connection->table('contacts')
                    ->where('id', $contactId)
                    ->where('tenant_id', $tenantId)
                    ->delete();

                return $deleted
                    ? ['success' => true, 'deleted' => $deleted]
                    : ['error' => 'Contact not found'];
            }

            private function logInteraction(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $contactId = $params['contactId'] ?? null;
                $type = $params['type'] ?? 'note';
                $content = $params['content'] ?? null;

                if (!$contactId) {
                    return ['error' => 'contactId required'];
                }

                $id = $this->connection->table('customer_interactions')->insertGetId([
                    'tenant_id' => $tenantId,
                    'contact_id' => $contactId,
                    'type' => $type,
                    'content' => $content,
                    'created_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function assignContact(array $params): array
            {
                $contactId = $params['contactId'] ?? null;
                $userId = $params['userId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$contactId || !$userId) {
                    return ['error' => 'contactId and userId required'];
                }

                $updated = $this->connection->table('contacts')
                    ->where('id', $contactId)
                    ->where('tenant_id', $tenantId)
                    ->update(['assigned_user_id' => $userId, 'updated_at' => now()]);

                return $updated
                    ? ['success' => true, 'assigned' => true]
                    : ['error' => 'Contact not found'];
            }
        };
    }

    public function createCommercialIntegration(): WorkCoreModuleIntegrationContract
    {
        return new class implements WorkCoreModuleIntegrationContract {
            public function __construct(private ConnectionInterface $connection) {}

            public function moduleName(): string { return 'commercial'; }
            public function extensionName(): string { return 'AiChatPro'; }
            public function canQuery(): bool { return true; }
            public function canMutate(): bool { return true; }

            public function query(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'list_products' => $this->listProducts($params),
                    'get_product' => $this->getProduct($params),
                    'search_products' => $this->searchProducts($params),
                    'list_orders' => $this->listOrders($params),
                    'get_order' => $this->getOrder($params),
                    'get_pricing' => $this->getPricing($params),
                    'list_invoices' => $this->listInvoices($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function mutate(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'create_order' => $this->createOrder($params),
                    'update_order' => $this->updateOrder($params),
                    'cancel_order' => $this->cancelOrder($params),
                    'add_invoice' => $this->addInvoice($params),
                    'process_payment' => $this->processPayment($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function getAvailableOperations(): array
            {
                return [
                    'queries' => [
                        'list_products' => 'Retrieve product catalog',
                        'get_product' => 'Get single product details',
                        'search_products' => 'Search products by criteria',
                        'list_orders' => 'List all orders',
                        'get_order' => 'Get order details',
                        'get_pricing' => 'Get pricing information',
                        'list_invoices' => 'List invoices',
                    ],
                    'mutations' => [
                        'create_order' => 'Create new order',
                        'update_order' => 'Update order status',
                        'cancel_order' => 'Cancel order',
                        'add_invoice' => 'Create invoice',
                        'process_payment' => 'Process payment',
                    ],
                ];
            }

            public function validatePermissions(int $tenantId, int $actorId, string $operation): bool
            {
                $allowedOps = array_merge(
                    array_keys($this->getAvailableOperations()['queries'] ?? []),
                    array_keys($this->getAvailableOperations()['mutations'] ?? [])
                );

                return in_array($operation, $allowedOps, true);
            }

            public function enrichContextWithModuleData(array $context): array
            {
                $context['module'] = 'commercial';
                $context['capabilities'] = [
                    'order_management' => true,
                    'product_catalog' => true,
                    'pricing_engine' => true,
                    'invoicing' => true,
                ];
                return $context;
            }

            private function listProducts(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('products')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getProduct(array $params): array
            {
                $productId = $params['productId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$productId) {
                    return ['error' => 'productId required'];
                }

                $record = $this->connection->table('products')
                    ->where('id', $productId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Product not found'];
            }

            private function searchProducts(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $query = $params['query'] ?? '';

                $records = $this->connection->table('products')
                    ->where('tenant_id', $tenantId)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('sku', 'like', "%{$query}%");
                    })
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function listOrders(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('orders')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getOrder(array $params): array
            {
                $orderId = $params['orderId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$orderId) {
                    return ['error' => 'orderId required'];
                }

                $record = $this->connection->table('orders')
                    ->where('id', $orderId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Order not found'];
            }

            private function getPricing(array $params): array
            {
                $productId = $params['productId'] ?? null;
                $quantity = $params['quantity'] ?? 1;

                if (!$productId) {
                    return ['error' => 'productId required'];
                }

                $record = $this->connection->table('products')
                    ->where('id', $productId)
                    ->first();

                if (!$record) {
                    return ['error' => 'Product not found'];
                }

                $basePrice = $record->price;
                $total = $basePrice * $quantity;
                $discount = ($params['discount'] ?? 0);
                $final = $total - ($total * $discount / 100);

                return [
                    'success' => true,
                    'basePrice' => $basePrice,
                    'quantity' => $quantity,
                    'subtotal' => $total,
                    'discount' => $discount,
                    'total' => $final,
                ];
            }

            private function listInvoices(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('invoices')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function createOrder(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $contactId = $params['contactId'] ?? null;
                $items = $params['items'] ?? [];

                if (!$contactId || empty($items)) {
                    return ['error' => 'contactId and items required'];
                }

                $id = $this->connection->table('orders')->insertGetId([
                    'tenant_id' => $tenantId,
                    'contact_id' => $contactId,
                    'status' => 'pending',
                    'items_count' => count($items),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function updateOrder(array $params): array
            {
                $orderId = $params['orderId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;
                $status = $params['status'] ?? null;

                if (!$orderId) {
                    return ['error' => 'orderId required'];
                }

                $updated = $this->connection->table('orders')
                    ->where('id', $orderId)
                    ->where('tenant_id', $tenantId)
                    ->update(['status' => $status, 'updated_at' => now()]);

                return $updated
                    ? ['success' => true, 'updated' => $updated]
                    : ['error' => 'Order not found'];
            }

            private function cancelOrder(array $params): array
            {
                $orderId = $params['orderId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$orderId) {
                    return ['error' => 'orderId required'];
                }

                $updated = $this->connection->table('orders')
                    ->where('id', $orderId)
                    ->where('tenant_id', $tenantId)
                    ->update(['status' => 'cancelled', 'updated_at' => now()]);

                return $updated
                    ? ['success' => true, 'cancelled' => true]
                    : ['error' => 'Order not found'];
            }

            private function addInvoice(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $orderId = $params['orderId'] ?? null;
                $amount = $params['amount'] ?? 0;

                if (!$orderId) {
                    return ['error' => 'orderId required'];
                }

                $id = $this->connection->table('invoices')->insertGetId([
                    'tenant_id' => $tenantId,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function processPayment(array $params): array
            {
                $invoiceId = $params['invoiceId'] ?? null;
                $amount = $params['amount'] ?? 0;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$invoiceId) {
                    return ['error' => 'invoiceId required'];
                }

                $updated = $this->connection->table('invoices')
                    ->where('id', $invoiceId)
                    ->where('tenant_id', $tenantId)
                    ->update(['status' => 'paid', 'paid_amount' => $amount, 'updated_at' => now()]);

                return $updated
                    ? ['success' => true, 'paid' => true]
                    : ['error' => 'Invoice not found'];
            }
        };
    }

    public function createOperationsIntegration(): WorkCoreModuleIntegrationContract
    {
        return new class implements WorkCoreModuleIntegrationContract {
            public function __construct(private ConnectionInterface $connection) {}

            public function moduleName(): string { return 'operations'; }
            public function extensionName(): string { return 'AiChatPro'; }
            public function canQuery(): bool { return true; }
            public function canMutate(): bool { return true; }

            public function query(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'list_workflows' => $this->listWorkflows($params),
                    'get_workflow' => $this->getWorkflow($params),
                    'list_tasks' => $this->listTasks($params),
                    'get_task' => $this->getTask($params),
                    'list_resources' => $this->listResources($params),
                    'get_dashboard' => $this->getDashboard($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function mutate(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'create_workflow' => $this->createWorkflow($params),
                    'execute_workflow' => $this->executeWorkflow($params),
                    'create_task' => $this->createTask($params),
                    'complete_task' => $this->completeTask($params),
                    'allocate_resource' => $this->allocateResource($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function getAvailableOperations(): array
            {
                return [
                    'queries' => [
                        'list_workflows' => 'List all workflows',
                        'get_workflow' => 'Get workflow details',
                        'list_tasks' => 'List all tasks',
                        'get_task' => 'Get task details',
                        'list_resources' => 'List available resources',
                        'get_dashboard' => 'Get operations dashboard',
                    ],
                    'mutations' => [
                        'create_workflow' => 'Create new workflow',
                        'execute_workflow' => 'Execute workflow',
                        'create_task' => 'Create task',
                        'complete_task' => 'Mark task complete',
                        'allocate_resource' => 'Allocate resource',
                    ],
                ];
            }

            public function validatePermissions(int $tenantId, int $actorId, string $operation): bool
            {
                $allowedOps = array_merge(
                    array_keys($this->getAvailableOperations()['queries'] ?? []),
                    array_keys($this->getAvailableOperations()['mutations'] ?? [])
                );

                return in_array($operation, $allowedOps, true);
            }

            public function enrichContextWithModuleData(array $context): array
            {
                $context['module'] = 'operations';
                $context['capabilities'] = [
                    'workflow_automation' => true,
                    'task_management' => true,
                    'resource_allocation' => true,
                ];
                return $context;
            }

            private function listWorkflows(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('workflows')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getWorkflow(array $params): array
            {
                $workflowId = $params['workflowId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$workflowId) {
                    return ['error' => 'workflowId required'];
                }

                $record = $this->connection->table('workflows')
                    ->where('id', $workflowId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Workflow not found'];
            }

            private function listTasks(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('tasks')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getTask(array $params): array
            {
                $taskId = $params['taskId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$taskId) {
                    return ['error' => 'taskId required'];
                }

                $record = $this->connection->table('tasks')
                    ->where('id', $taskId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Task not found'];
            }

            private function listResources(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $type = $params['type'] ?? null;

                $query = $this->connection->table('resources')
                    ->where('tenant_id', $tenantId);

                if ($type) {
                    $query->where('type', $type);
                }

                $records = $query->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getDashboard(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;

                $totalWorkflows = $this->connection->table('workflows')
                    ->where('tenant_id', $tenantId)
                    ->count();

                $activeTasks = $this->connection->table('tasks')
                    ->where('tenant_id', $tenantId)
                    ->where('status', '!=', 'completed')
                    ->count();

                $completedTasks = $this->connection->table('tasks')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'completed')
                    ->count();

                return [
                    'success' => true,
                    'data' => [
                        'totalWorkflows' => $totalWorkflows,
                        'activeTasks' => $activeTasks,
                        'completedTasks' => $completedTasks,
                        'efficiency' => $completedTasks > 0 ? round($completedTasks / ($activeTasks + $completedTasks) * 100, 2) : 0,
                    ],
                ];
            }

            private function createWorkflow(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $name = $params['name'] ?? null;
                $steps = $params['steps'] ?? [];

                if (!$name) {
                    return ['error' => 'name required'];
                }

                $id = $this->connection->table('workflows')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'steps' => json_encode($steps),
                    'status' => 'draft',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function executeWorkflow(array $params): array
            {
                $workflowId = $params['workflowId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$workflowId) {
                    return ['error' => 'workflowId required'];
                }

                $updated = $this->connection->table('workflows')
                    ->where('id', $workflowId)
                    ->where('tenant_id', $tenantId)
                    ->update(['status' => 'executing', 'updated_at' => now()]);

                return $updated
                    ? ['success' => true, 'executing' => true]
                    : ['error' => 'Workflow not found'];
            }

            private function createTask(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $title = $params['title'] ?? null;
                $workflowId = $params['workflowId'] ?? null;

                if (!$title) {
                    return ['error' => 'title required'];
                }

                $id = $this->connection->table('tasks')->insertGetId([
                    'tenant_id' => $tenantId,
                    'workflow_id' => $workflowId,
                    'title' => $title,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function completeTask(array $params): array
            {
                $taskId = $params['taskId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$taskId) {
                    return ['error' => 'taskId required'];
                }

                $updated = $this->connection->table('tasks')
                    ->where('id', $taskId)
                    ->where('tenant_id', $tenantId)
                    ->update(['status' => 'completed', 'completed_at' => now(), 'updated_at' => now()]);

                return $updated
                    ? ['success' => true, 'completed' => true]
                    : ['error' => 'Task not found'];
            }

            private function allocateResource(array $params): array
            {
                $resourceId = $params['resourceId'] ?? null;
                $taskId = $params['taskId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$resourceId || !$taskId) {
                    return ['error' => 'resourceId and taskId required'];
                }

                $id = $this->connection->table('task_resources')->insertGetId([
                    'tenant_id' => $tenantId,
                    'task_id' => $taskId,
                    'resource_id' => $resourceId,
                    'created_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }
        };
    }

    public function createPropertyIntegration(): WorkCoreModuleIntegrationContract
    {
        return new class implements WorkCoreModuleIntegrationContract {
            public function __construct(private ConnectionInterface $connection) {}

            public function moduleName(): string { return 'property'; }
            public function extensionName(): string { return 'AiChatPro'; }
            public function canQuery(): bool { return true; }
            public function canMutate(): bool { return true; }

            public function query(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'list_properties' => $this->listProperties($params),
                    'get_property' => $this->getProperty($params),
                    'search_properties' => $this->searchProperties($params),
                    'list_maintenance' => $this->listMaintenance($params),
                    'get_asset' => $this->getAsset($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function mutate(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'create_property' => $this->createProperty($params),
                    'update_property' => $this->updateProperty($params),
                    'schedule_maintenance' => $this->scheduleMaintenance($params),
                    'log_maintenance' => $this->logMaintenance($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function getAvailableOperations(): array
            {
                return [
                    'queries' => [
                        'list_properties' => 'List properties and assets',
                        'get_property' => 'Get property details',
                        'search_properties' => 'Search properties',
                        'list_maintenance' => 'List maintenance history',
                        'get_asset' => 'Get asset details',
                    ],
                    'mutations' => [
                        'create_property' => 'Register new property',
                        'update_property' => 'Update property info',
                        'schedule_maintenance' => 'Schedule maintenance',
                        'log_maintenance' => 'Log maintenance completion',
                    ],
                ];
            }

            public function validatePermissions(int $tenantId, int $actorId, string $operation): bool
            {
                $allowedOps = array_merge(
                    array_keys($this->getAvailableOperations()['queries'] ?? []),
                    array_keys($this->getAvailableOperations()['mutations'] ?? [])
                );

                return in_array($operation, $allowedOps, true);
            }

            public function enrichContextWithModuleData(array $context): array
            {
                $context['module'] = 'property';
                $context['capabilities'] = [
                    'property_management' => true,
                    'maintenance_tracking' => true,
                    'asset_management' => true,
                ];
                return $context;
            }

            private function listProperties(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('properties')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getProperty(array $params): array
            {
                $propertyId = $params['propertyId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$propertyId) {
                    return ['error' => 'propertyId required'];
                }

                $record = $this->connection->table('properties')
                    ->where('id', $propertyId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Property not found'];
            }

            private function searchProperties(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $query = $params['query'] ?? '';

                $records = $this->connection->table('properties')
                    ->where('tenant_id', $tenantId)
                    ->where(function ($q) use ($query) {
                        $q->where('address', 'like', "%{$query}%")
                          ->orWhere('name', 'like', "%{$query}%");
                    })
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function listMaintenance(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $propertyId = $params['propertyId'] ?? null;
                $limit = $params['limit'] ?? 50;

                $query = $this->connection->table('maintenance_logs')
                    ->where('tenant_id', $tenantId);

                if ($propertyId) {
                    $query->where('property_id', $propertyId);
                }

                $records = $query->limit($limit)->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getAsset(array $params): array
            {
                $assetId = $params['assetId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$assetId) {
                    return ['error' => 'assetId required'];
                }

                $record = $this->connection->table('assets')
                    ->where('id', $assetId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Asset not found'];
            }

            private function createProperty(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $address = $params['address'] ?? null;
                $name = $params['name'] ?? null;

                if (!$address) {
                    return ['error' => 'address required'];
                }

                $id = $this->connection->table('properties')->insertGetId([
                    'tenant_id' => $tenantId,
                    'address' => $address,
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function updateProperty(array $params): array
            {
                $propertyId = $params['propertyId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$propertyId) {
                    return ['error' => 'propertyId required'];
                }

                $updated = $this->connection->table('properties')
                    ->where('id', $propertyId)
                    ->where('tenant_id', $tenantId)
                    ->update(array_merge($params, ['updated_at' => now()]));

                return $updated
                    ? ['success' => true, 'updated' => $updated]
                    : ['error' => 'Property not found'];
            }

            private function scheduleMaintenance(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $propertyId = $params['propertyId'] ?? null;
                $type = $params['type'] ?? 'routine';
                $scheduledDate = $params['scheduledDate'] ?? null;

                if (!$propertyId) {
                    return ['error' => 'propertyId required'];
                }

                $id = $this->connection->table('maintenance_logs')->insertGetId([
                    'tenant_id' => $tenantId,
                    'property_id' => $propertyId,
                    'type' => $type,
                    'status' => 'scheduled',
                    'scheduled_date' => $scheduledDate,
                    'created_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function logMaintenance(array $params): array
            {
                $maintenanceId = $params['maintenanceId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;
                $notes = $params['notes'] ?? null;

                if (!$maintenanceId) {
                    return ['error' => 'maintenanceId required'];
                }

                $updated = $this->connection->table('maintenance_logs')
                    ->where('id', $maintenanceId)
                    ->where('tenant_id', $tenantId)
                    ->update(['status' => 'completed', 'notes' => $notes, 'completed_at' => now(), 'updated_at' => now()]);

                return $updated
                    ? ['success' => true, 'logged' => true]
                    : ['error' => 'Maintenance log not found'];
            }
        };
    }

    public function createHRIntegration(): WorkCoreModuleIntegrationContract
    {
        return new class implements WorkCoreModuleIntegrationContract {
            public function __construct(private ConnectionInterface $connection) {}

            public function moduleName(): string { return 'hr'; }
            public function extensionName(): string { return 'AiChatPro'; }
            public function canQuery(): bool { return true; }
            public function canMutate(): bool { return true; }

            public function query(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'list_employees' => $this->listEmployees($params),
                    'get_employee' => $this->getEmployee($params),
                    'search_employees' => $this->searchEmployees($params),
                    'list_performance_reviews' => $this->listPerformanceReviews($params),
                    'get_payroll' => $this->getPayroll($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function mutate(string $operation, array $params = []): array
            {
                return match ($operation) {
                    'create_employee' => $this->createEmployee($params),
                    'update_employee' => $this->updateEmployee($params),
                    'log_performance' => $this->logPerformance($params),
                    'process_payroll' => $this->processPayroll($params),
                    default => ['error' => 'Unknown operation: ' . $operation],
                };
            }

            public function getAvailableOperations(): array
            {
                return [
                    'queries' => [
                        'list_employees' => 'List all employees',
                        'get_employee' => 'Get employee record',
                        'search_employees' => 'Search employees',
                        'list_performance_reviews' => 'Get performance reviews',
                        'get_payroll' => 'Get payroll information',
                    ],
                    'mutations' => [
                        'create_employee' => 'Onboard employee',
                        'update_employee' => 'Update employee info',
                        'log_performance' => 'Record performance',
                        'process_payroll' => 'Process payroll run',
                    ],
                ];
            }

            public function validatePermissions(int $tenantId, int $actorId, string $operation): bool
            {
                $allowedOps = array_merge(
                    array_keys($this->getAvailableOperations()['queries'] ?? []),
                    array_keys($this->getAvailableOperations()['mutations'] ?? [])
                );

                return in_array($operation, $allowedOps, true);
            }

            public function enrichContextWithModuleData(array $context): array
            {
                $context['module'] = 'hr';
                $context['capabilities'] = [
                    'employee_management' => true,
                    'performance_tracking' => true,
                    'payroll' => true,
                ];
                return $context;
            }

            private function listEmployees(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $limit = $params['limit'] ?? 50;

                $records = $this->connection->table('employees')
                    ->where('tenant_id', $tenantId)
                    ->limit($limit)
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getEmployee(array $params): array
            {
                $employeeId = $params['employeeId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$employeeId) {
                    return ['error' => 'employeeId required'];
                }

                $record = $this->connection->table('employees')
                    ->where('id', $employeeId)
                    ->where('tenant_id', $tenantId)
                    ->first();

                return $record
                    ? ['success' => true, 'data' => (array) $record]
                    : ['error' => 'Employee not found'];
            }

            private function searchEmployees(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $query = $params['query'] ?? '';

                $records = $this->connection->table('employees')
                    ->where('tenant_id', $tenantId)
                    ->where(function ($q) use ($query) {
                        $q->where('name', 'like', "%{$query}%")
                          ->orWhere('email', 'like', "%{$query}%");
                    })
                    ->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function listPerformanceReviews(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $employeeId = $params['employeeId'] ?? null;

                $query = $this->connection->table('performance_reviews')
                    ->where('tenant_id', $tenantId);

                if ($employeeId) {
                    $query->where('employee_id', $employeeId);
                }

                $records = $query->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function getPayroll(array $params): array
            {
                $employeeId = $params['employeeId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;
                $period = $params['period'] ?? null;

                if (!$employeeId) {
                    return ['error' => 'employeeId required'];
                }

                $query = $this->connection->table('payroll_records')
                    ->where('employee_id', $employeeId)
                    ->where('tenant_id', $tenantId);

                if ($period) {
                    $query->where('period', $period);
                }

                $records = $query->get();

                return ['success' => true, 'data' => $records->toArray()];
            }

            private function createEmployee(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $name = $params['name'] ?? null;
                $email = $params['email'] ?? null;

                if (!$name || !$email) {
                    return ['error' => 'name and email required'];
                }

                $id = $this->connection->table('employees')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'email' => $email,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function updateEmployee(array $params): array
            {
                $employeeId = $params['employeeId'] ?? null;
                $tenantId = $params['tenantId'] ?? 0;

                if (!$employeeId) {
                    return ['error' => 'employeeId required'];
                }

                $updated = $this->connection->table('employees')
                    ->where('id', $employeeId)
                    ->where('tenant_id', $tenantId)
                    ->update(array_merge($params, ['updated_at' => now()]));

                return $updated
                    ? ['success' => true, 'updated' => $updated]
                    : ['error' => 'Employee not found'];
            }

            private function logPerformance(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $employeeId = $params['employeeId'] ?? null;
                $rating = $params['rating'] ?? 0;
                $feedback = $params['feedback'] ?? null;

                if (!$employeeId) {
                    return ['error' => 'employeeId required'];
                }

                $id = $this->connection->table('performance_reviews')->insertGetId([
                    'tenant_id' => $tenantId,
                    'employee_id' => $employeeId,
                    'rating' => $rating,
                    'feedback' => $feedback,
                    'created_at' => now(),
                ]);

                return ['success' => true, 'id' => $id];
            }

            private function processPayroll(array $params): array
            {
                $tenantId = $params['tenantId'] ?? 0;
                $period = $params['period'] ?? null;

                if (!$period) {
                    return ['error' => 'period required'];
                }

                $employees = $this->connection->table('employees')
                    ->where('tenant_id', $tenantId)
                    ->where('status', 'active')
                    ->get();

                $processed = 0;
                foreach ($employees as $emp) {
                    $this->connection->table('payroll_records')->insert([
                        'tenant_id' => $tenantId,
                        'employee_id' => $emp->id,
                        'period' => $period,
                        'status' => 'processed',
                        'created_at' => now(),
                    ]);
                    $processed++;
                }

                return ['success' => true, 'processed' => $processed];
            }
        };
    }
}
