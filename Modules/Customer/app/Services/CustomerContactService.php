<?php

namespace Modules\Customer\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Models\CustomerContact;

class CustomerContactService
{
    public function getCustomerContacts(string $customerId): Collection
    {
        return CustomerContact::where('customer_id', $customerId)->get();
    }

    public function createContact(array $data): CustomerContact
    {
        if ($this->isDuplicateContact($data)) {
            throw new \Exception(__('customer::messages.duplicate_contact'), 400);
        }
        return CustomerContact::create($data);
    }

    public function updateContact(string $id, array $data): CustomerContact
    {
       
        $contact = CustomerContact::findOrFail($id);
         if ($this->isDuplicateContact($data, $contact->customer_id)) {
            throw new \Exception(__('customer::messages.duplicate_contact'), 400);
        }
        $contact->update($data);
        return $contact;
    }

    public function deleteContact(string $id): bool
    {
        return CustomerContact::destroy($id) > 0;
    }

    public function syncCustomerContacts(string $customerId, array $contacts): void
    {
        // Delete existing contacts
        CustomerContact::where('customer_id', $customerId)->delete();

        // Create new contacts
        foreach ($contacts as $contactData) {
            $contactData['customer_id'] = $customerId;
            $this->createContact($contactData);
        }
    }

    public function setPrimaryContact(string $customerId, string $contactId, string $contactType): CustomerContact
    {
        // Remove primary status from other contacts of same type
        CustomerContact::where('customer_id', $customerId)
            ->where('contact_type', $contactType)
            ->update(['is_primary' => false]);

        // Set new primary contact
        $contact = CustomerContact::findOrFail($contactId);
        $contact->update(['is_primary' => true]);

        return $contact;
    }
    protected function isDuplicateContact(array $contact, $excludeCustomerId = null): bool
    {
    
        $query = DB::table('customer_contacts')
            ->where('contact_type', $contact['contact_type'])
            ->where('value', $contact['value']);
        if ($excludeCustomerId) {
            $query->where('customer_id', '!=', $excludeCustomerId);
        }
        if ($query->exists()) {
            return true; // Trùng liên hệ
        }
        return false;
    }
}
