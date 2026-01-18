<?php

namespace App\Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CRM\Http\Requests\StoreContactRequest;
use App\Modules\CRM\Http\Requests\UpdateContactRequest;
use App\Modules\CRM\Http\Resources\ContactResource;
use App\Modules\CRM\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Contact::class);

        $contacts = Contact::with(['creator', 'lead'])
            ->latest()
            ->paginate();

        return ContactResource::collection($contacts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\StoreContactRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $this->authorize('create', Contact::class);

        $contact = Contact::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $contact->load(['creator', 'lead']);

        return response()->json([
            'data' => new ContactResource($contact),
            'message' => 'Contact created successfully.',
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modules\CRM\Models\Contact  $contact
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Contact $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        $contact->load(['creator', 'lead']);

        return response()->json([
            'data' => new ContactResource($contact),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Modules\CRM\Http\Requests\UpdateContactRequest  $request
     * @param  \App\Modules\CRM\Models\Contact  $contact
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());
        $contact->load(['creator', 'lead']);

        return response()->json([
            'data' => new ContactResource($contact),
            'message' => 'Contact updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modules\CRM\Models\Contact  $contact
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Contact $contact): JsonResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return response()->json([
            'message' => 'Contact deleted successfully.',
        ]);
    }
}

