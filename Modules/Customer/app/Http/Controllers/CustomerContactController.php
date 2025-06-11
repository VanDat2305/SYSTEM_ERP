<?php

namespace Modules\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customer\Services\CustomerContactService;

class CustomerContactController extends Controller
{
    protected CustomerContactService $contactService;

    public function __construct(CustomerContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    public function index(string $customerId): JsonResponse
    {
        try {
            $contacts = $this->contactService->getCustomerContacts($customerId);
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.contacts_retrieved'),
                'data' => $contacts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_retrieving_contacts'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|uuid|exists:customers,id',
            'contact_type' => 'required|in:phone,email',
            'value' => 'required|string|max:255',
            'label' => 'nullable|string|max:20',
            'is_primary' => 'boolean',
            'note' => 'nullable|string|max:100'
        ]);

        try {
            $contact = $this->contactService->createContact($request->all());
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.contact_created'),
                'data' => $contact
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_creating_contact'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'contact_type' => '',
            'value' => 'string|max:255',
            'label' => 'nullable|string|max:20',
            'is_primary' => 'boolean',
            'note' => 'nullable|string|max:100'
        ]);

        try {
            $contact = $this->contactService->updateContact($id, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.contact_updated'),
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_updating_contact'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->contactService->deleteContact($id);
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.contact_deleted')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_deleting_contact'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function setPrimary(Request $request, string $customerId, string $contactId): JsonResponse
    {
        $request->validate([
            'contact_type' => 'required'
        ]);

        try {
            $contact = $this->contactService->setPrimaryContact(
                $customerId, 
                $contactId, 
                $request->contact_type
            );
            
            return response()->json([
                'success' => true,
                'message' => __('customer::messages.primary_contact_set'),
                'data' => $contact
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('customer::messages.error_setting_primary'),
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}