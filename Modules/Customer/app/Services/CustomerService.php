<?php

namespace Modules\Customer\Services;

namespace Modules\Customer\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\Customer;
use Modules\Customer\Interfaces\CustomerRepositoryInterface;
use Modules\Customer\Services\CustomerContactService;
use Modules\Customer\Services\CustomerRepresentativeService;

class CustomerService
{
    protected CustomerRepositoryInterface $customerRepository;
    protected CustomerContactService $contactService;
    protected CustomerRepresentativeService $representativeService;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerContactService $contactService,
        CustomerRepresentativeService $representativeService
    ) {
        $this->customerRepository = $customerRepository;
        $this->contactService = $contactService;
        $this->representativeService = $representativeService;
    }

    public function getAllCustomers(): Collection
    {
        return $this->customerRepository->all();
    }

    public function getCustomersPaginated(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->customerRepository->paginate($perPage, $filters);
    }

    public function getCustomerById(string $id): ?Customer
    {
        return $this->customerRepository->find($id);
    }

    public function createCustomer(array $data): Customer
    {
        DB::beginTransaction();

        try {
            // Create customer
            $customerData = collect($data)->only([
                'customer_type',
                'full_name',
                'short_name',
                'gender',
                'date_of_birth',
                'tax_code',
                'industry',
                'address',
                'province_code',
                'identity_type',
                'identity_number',
                'position',
                'website',
                'team_id',
                'assigned_to',
                'is_active'
            ])->toArray();
            if ($this->isDuplicateCustomer($customerData)) {
                throw new \Exception(__('customer::messages.duplicate_customer'));
            }

            $customer = $this->customerRepository->create($customerData);

            // Create contacts if provided
            if (!empty($data['contacts'])) {
                if ($this->isDuplicateContact($data['contacts'])) {
                    throw new \Exception(__('customer::messages.duplicate_contact'));
                }
                foreach ($data['contacts'] as $contactData) {
                    $contactData['customer_id'] = $customer->id;
                    $this->contactService->createContact($contactData);
                }
            }

            // Create representatives if provided
            if (!empty($data['representatives'])) {
                if ($this->isDuplicateRepresentative($data['representatives'], $customer->id)) {
                    throw new \Exception(__('customer::messages.duplicate_representative'));
                }
                foreach ($data['representatives'] as $repData) {
                    $repData['customer_id'] = $customer->id;
                    $this->representativeService->createRepresentative($repData);
                }
            }

            DB::commit();

            return $customer->fresh(['contacts', 'representatives']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateCustomer(string $id, array $data): Customer
    {
        DB::beginTransaction();

        try {
            // Update customer
            $customerData = collect($data)->only([
                'customer_type',
                'full_name',
                'short_name',
                'gender',
                'date_of_birth',
                'tax_code',
                'industry',
                'address',
                'province_code',
                'identity_type',
                'identity_number',
                'position',
                'website',
                'team_id',
                'assigned_to',
                'is_active'
            ])->toArray();
            if ($this->isDuplicateCustomer($customerData, $id)) {
                throw new \Exception(__('customer::messages.duplicate_customer'));
            }
            $customer = $this->customerRepository->update($id, $customerData);

            // Update contacts if provided
            if (isset($data['contacts'])) {
                $this->contactService->syncCustomerContacts($id, $data['contacts']);
            }

            // Update representatives if provided
            if (isset($data['representatives'])) {
                $this->representativeService->syncCustomerRepresentatives($id, $data['representatives']);
            }

            DB::commit();

            return $customer->fresh(['contacts', 'representatives']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function deleteCustomer(string $id): bool
    {
        return $this->customerRepository->delete($id);
    }

    public function getCustomersByType(string $type): Collection
    {
        return $this->customerRepository->findByType($type);
    }

    public function getCustomersByTeam(string $teamId): Collection
    {
        return $this->customerRepository->findByTeam($teamId);
    }

    public function getActiveCustomers(): Collection
    {
        return $this->customerRepository->findActive();
    }

    public function searchCustomers(string $keyword): Collection
    {
        return $this->customerRepository->search($keyword);
    }

    public function toggleCustomerStatus(string $id): Customer
    {
        $customer = $this->getCustomerById($id);
        if (!$customer) {
            throw new \Exception(__('customer::messages.customer_not_found'));
        }

        return $this->updateCustomer($id, ['is_active' => !$customer->is_active]);
    }
    /**
     * Kiểm tra trùng dữ liệu khách hàng
     * Trả về true nếu đã tồn tại khách hàng trùng.
     */
    public function isDuplicateCustomer(array $data, $excludeId = null): bool
    {
        $query = Customer::query();

        // Kiểm tra tổ chức (mã số thuế hoặc tên)
        if (($data['customer_type'] ?? null) === 'ORGANIZATION') {
            if (!empty($data['tax_code'])) {
                $query->where('tax_code', $data['tax_code']);
            }
            // Optionally: kiểm tra full_name
            // if (!empty($data['full_name'])) {
            //     $query->orWhere('full_name', $data['full_name']);
            // }
        }

        // Kiểm tra cá nhân (số định danh)
        if (($data['customer_type'] ?? null) === 'INDIVIDUAL') {
            if (!empty($data['identity_type']) && !empty($data['identity_number'])) {
                $query->where('identity_type', $data['identity_type'])
                    ->where('identity_number', $data['identity_number']);
            }
            // Optionally: kiểm tra full_name + date_of_birth
            // if (!empty($data['full_name']) && !empty($data['date_of_birth'])) {
            //     $query->orWhere(function($q) use ($data) {
            //         $q->where('full_name', $data['full_name'])
            //           ->where('date_of_birth', $data['date_of_birth']);
            //     });
            // }
        }

        // Nếu update, loại trừ chính bản ghi đó
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    protected function isDuplicateContact(array $contacts, $excludeCustomerId = null): bool
    {
        foreach ($contacts as $contact) {
            $query = DB::table('customer_contacts')
                ->where('contact_type', $contact['contact_type'])
                ->where('value', $contact['value']);
            if ($excludeCustomerId) {
                $query->where('customer_id', '!=', $excludeCustomerId);
            }
            if ($query->exists()) {
                return true; // Trùng liên hệ
            }
        }
        return false;
    }
    public function isDuplicateRepresentative(array $representatives, $excludeCustomerId = null): bool
    {
        foreach ($representatives as $rep) {
            $query = DB::table('customer_representatives')
                ->where('name', $rep['name'])
                ->where('email', $rep['email']);
            //identity_type và identity_number có thể là duy nhất trong một khách hàng
            if (!empty($rep['identity_type']) && !empty($rep['identity_number'])) {
                $query->where('identity_type', $rep['identity_type'])
                      ->where('identity_number', $rep['identity_number']);
            }
            if ($excludeCustomerId) {
                $query->where('customer_id', '!=', $excludeCustomerId);
            }
            if ($query->exists()) {
                return true; // Trùng đại diện
            }
        }
        return false;
    }
}
