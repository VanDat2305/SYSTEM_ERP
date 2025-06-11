<?php

namespace Modules\Customer\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Customer\Models\CustomerRepresentative;

class CustomerRepresentativeService
{
    public function getCustomerRepresentatives(string $customerId): Collection
    {
        return CustomerRepresentative::where('customer_id', $customerId)->get();
    }

    public function createRepresentative(array $data): CustomerRepresentative
    {
        // Check for duplicate representatives
        if ($this->isDuplicateRepresentatives($data)) {
            throw new \Exception(__('customer::messages.duplicate_representative'), 400);
        }
        return CustomerRepresentative::create($data);
    }

    public function updateRepresentative(string $id, array $data): CustomerRepresentative
    {
        $representative = CustomerRepresentative::findOrFail($id);
        // Check for duplicate representatives
        if ($this->isDuplicateRepresentatives($data, $representative->customer_id)) {
            throw new \Exception(__('customer::messages.duplicate_representative'), 400);
        }
        $representative->update($data);
        return $representative;
    }

    public function deleteRepresentative(string $id): bool
    {
        return CustomerRepresentative::destroy($id) > 0;
    }

    public function syncCustomerRepresentatives(string $customerId, array $representatives): void
    {
        // Delete existing representatives
        CustomerRepresentative::where('customer_id', $customerId)->delete();

        // Create new representatives
        foreach ($representatives as $repData) {
            $repData['customer_id'] = $customerId;
            $this->createRepresentative($repData);
        }
    }
    public function isDuplicateRepresentatives(array $data, ?string $customerId = null): bool
    {
        $query = CustomerRepresentative::where('full_name', $data['full_name'])
            ->where('email', $data['email'])
            ->where('phone', $data['phone']);

        if ($customerId) {
            $query->where('customer_id', '!=', $customerId);
        }

        return $query->exists();
    }
}