<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerSaveRequest;
use App\Models\Credit;
use App\Models\Customer;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Image\Image;

class CustomerController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Customer::class)
            ->columns(['name', 'mobile', 'email'], ['id'])
            ->searchable(['name', 'mobile', 'email'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.customers.customer-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.customers.customer-form');
    }

    public function store(CustomerSaveRequest $request): JsonResponse|RedirectResponse
    {
        $customer = new Customer;
        $this->saveCustomerData($customer, $request);

        logAdmin(__METHOD__, Customer::class, $customer->id);

        return $this->respondAfterSave($request, $customer, __('As you wished created successfully'), 'admin.customer.edit');
    }

    public function edit(Customer|string|int $item): View
    {
        $item = $this->resolveCustomer($item);

        return view('admin.customers.customer-form', compact('item'));
    }

    public function update(CustomerSaveRequest $request, Customer|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveCustomer($item);
        $this->saveCustomerData($item, $request);

        logAdmin(__METHOD__, Customer::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.customer.edit');
    }

    public function destroy(Customer|string|int $item): RedirectResponse
    {
        $item = $this->resolveCustomer($item);

        logAdmin(__METHOD__, Customer::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Customer::onlyTrashed())
            ->columns(['name', 'mobile', 'email'], ['id', 'deleted_at'])
            ->searchable(['name', 'mobile', 'email'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.customers.customer-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Customer::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, Customer::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Customer::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function show($item)
    {
        $customer = $this->resolveCustomer($item);
        if ($customer && method_exists($customer, 'webUrl')) {
            return redirect($customer->webUrl());
        }

        return redirect()->route('admin.customer.edit', $customer->id);
    }

    protected function resolveCustomer(Customer|string|int $item): Customer
    {
        if ($item instanceof Customer) {
            return $item;
        }

        return Customer::where('id', $item)->firstOrFail();
    }

    protected function saveCustomerData(Customer $customer, Request $request): void
    {
        $customer->name = $request->input('name');

        if ($customer->id !== null && $request->has('credit') && (float) $customer->credit !== (float) $request->input('credit')) {
            $diff = (float) $request->input('credit') - (float) $customer->credit;
            $customer->credit = $request->input('credit') ?? 0;

            $cr = new Credit;
            $cr->customer_id = $customer->id;
            $cr->amount = $diff;
            $cr->data = json_encode([
                'user_id' => auth()->id(),
                'message' => __('Increase / decrease by Admin'),
            ]);
            $cr->save();
        }

        if ($request->has('email')) {
            $customer->email = $request->input('email');
        }

        $customer->mobile = $request->input('mobile');
        $customer->sex = $request->input('sex');

        if ($request->filled('height')) {
            $customer->height = $request->input('height');
        }
        if ($request->filled('weight')) {
            $customer->weight = $request->input('weight');
        }

        $customer->description = $request->input('description');

        if (trim((string) $request->input('password')) !== '') {
            $customer->password = bcrypt($request->input('password'));
        }

        if ($request->filled('dob')) {
            $customer->dob = date('Y-m-d', floor((float) $request->dob));
        } else {
            $customer->dob = null;
        }

        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $name = time().'.'.$avatar->getClientOriginalExtension();
            $customer->avatar = $name;
            $avatar->storeAs('public/customers', $name);

            Image::load($avatar->getPathname())
                ->optimize()
                ->width(500)
                ->height(500)
                ->crop(500, 500)
                ->format('webp')
                ->save(storage_path('app/public/customers/'.$name));
        }

        $customer->colleague = $request->has('colleague');
        $customer->save();
    }
}
