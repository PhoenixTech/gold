<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Ticket;
use App\Models\User;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Image\Image;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! auth('customer')->check()) {
                return redirect()->route('client.sign-in');
            }

            if (\Session::has('locate')) {
                app()->setLocale(\Session::get('locate'));
            }

            return $next($request);
        })->except(['ProductFavToggle', 'ProductBookmarkToggle']);
    }

    public function addressSave(Address $address, Request $request): Address
    {
        $address->address = $request->input('address');
        $address->lat = $request->input('lat');
        $address->lng = $request->input('lng');
        $address->state_id = $request->input('state_id') ?: null;
        $address->city_id = $request->input('city_id') ?: null;
        $address->zip = $request->input('zip');
        $address->save();

        return $address;
    }

    protected function addressValidationRules(): array
    {
        return [
            'address' => ['required', 'string', 'min:10'],
            'zip' => ['required', 'string', 'min:5'],
            'state_id' => ['required', 'exists:states,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'lat' => ['nullable'],
            'lng' => ['nullable'],
        ];
    }

    public function profile()
    {
        $area = 'customer';
        $title = __('Profile');
        $subtitle = 'You information';

        return view('client.default-list', compact('area', 'title', 'subtitle'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'mobile' => ['required', 'string', 'max:255'],
            'height' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'sex' => ['nullable', 'in:MALE,FEMALE'],
            'dob' => ['nullable', 'date'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg', 'max:2048'],
        ]);

        $customer = auth('customer')->user();
        $customer->name = $request->input('name');
        $customer->email = $request->input('email');
        $customer->description = $request->input('description');

        if ($request->filled('sex')) {
            $customer->sex = $request->input('sex');
        }
        if ($request->filled('height')) {
            $customer->height = $request->input('height');
        }
        if ($request->filled('weight')) {
            $customer->weight = $request->input('weight');
        }
        if ($request->filled('password')) {
            $customer->password = bcrypt($request->input('password'));
        }

        if ($request->filled('dob')) {
            $customer->dob = date('Y-m-d', floor((float) $request->dob));
        } else {
            $customer->dob = null;
        }

        if ($request->hasFile('avatar')) {
            $name = time().'.'.$request->file('avatar')->getClientOriginalExtension();
            $customer->avatar = $name;
            $request->file('avatar')->storeAs('public/customers', $name);

            Image::load($request->file('avatar')->getPathname())
                ->optimize()
                ->width(500)
                ->height(500)
                ->crop(500, 500)
                ->format('webp')
                ->save(storage_path('app/public/customers/'.$customer->avatar));
        }

        $customer->save();

        return redirect()->route('client.profile')->with('message', __('Profile updated successfully'));
    }

    public function invoice(Invoice $invoice)
    {
        if (! auth('customer')->check() || $invoice->customer_id != auth('customer')->id()) {
            return redirect()->route('client.sign-in')->withErrors([__('You need to login to access this page')]);
        }

        $area = 'invoice';
        $title = __('Invoice');
        $subtitle = __('Invoice ID:').' '.$invoice->hash;

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
        ]);
        $qr = new QRCode($options);
        $invoice->loadMissing([
            'customer',
            'address.state',
            'address.city',
            'orders.product',
            'orders.quantity',
            'payments',
            'paymentReceipts',
            'activeDelivery',
        ]);

        return view('client.invoice', compact('area', 'title', 'subtitle', 'invoice', 'qr'));
    }

    public function ProductFavToggle(Product $product): JsonResponse|RedirectResponse
    {
        return $this->toggleProductInteraction(
            $product,
            'favorites',
            __('Product added to favorites'),
            __('Product removed from favorites'),
        );
    }

    public function ProductBookmarkToggle(Product $product): JsonResponse|RedirectResponse
    {
        return $this->toggleProductInteraction(
            $product,
            'bookmarks',
            __('Product added to bookmarks'),
            __('Product removed from bookmarks'),
        );
    }

    private function toggleProductInteraction(
        Product $product,
        string $relation,
        string $attachedMessage,
        string $detachedMessage,
    ): JsonResponse|RedirectResponse {
        /** @var Customer|User|null $actor */
        $actor = auth('customer')->user() ?? auth('web')->user();

        if ($actor === null) {
            return errors([__('You need to login first')], 401, __('You need to login first'));
        }

        $changes = $actor->{$relation}()->toggle($product->getKey());
        $isAttached = count($changes['attached']) > 0;
        $message = $isAttached ? $attachedMessage : $detachedMessage;
        $state = $isAttached ? '1' : '0';

        if (request()->ajax() || request()->wantsJson()) {
            return success($state, $message);
        }

        return redirect()->back()->with(['message' => $message]);
    }

    public function addresses()
    {
        return auth('customer')->user()->addresses;
    }

    public function addressUpdate(Request $request, $item)
    {
        $address = Address::where('id', $item)->firstOrFail();
        if ($address->customer_id != auth('customer')->id()) {
            return abort(403);
        }

        $request->validate($this->addressValidationRules());
        $this->addressSave($address, $request);

        return ['OK' => true, 'message' => __('address updated')];
    }

    public function addressDestroy(Address $item)
    {
        if ($item->customer_id != auth('customer')->id()) {
            return abort(403);
        }

        $addressText = $item->address;
        $item->delete();

        return ['OK' => true, 'message' => __(':ADDRESS removed', ['ADDRESS' => $addressText])];
    }

    public function addressStore(Request $request)
    {
        $request->validate($this->addressValidationRules());

        $address = new Address;
        $address->customer_id = auth('customer')->id();
        $this->addressSave($address, $request);

        return ['OK' => true, 'message' => __('Address added successfully'), 'list' => auth('customer')->user()->addresses];
    }

    public function submitTicket(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $ticket = new Ticket;
        $ticket->title = $request->title;
        $ticket->body = trim($request->body);
        $ticket->customer_id = auth('customer')->id();
        $ticket->save();

        return redirect()->route('client.profile')->with('message', __('Ticket added successfully'));
    }

    public function showTicket(Ticket $ticket)
    {
        return view('client.ticket', compact('ticket'));
    }

    public function ticketAnswer(Ticket $ticket, Request $request)
    {
        $request->validate([
            'body' => ['required', 'string'],
        ]);

        $ticket->status = 'PENDING';
        $ticket->save();

        $nticket = new Ticket;
        $nticket->parent_id = $ticket->id;
        $nticket->body = trim($request->body);
        $nticket->customer_id = auth('customer')->id();
        $nticket->save();

        return redirect()->to(route('client.profile').'#tickets')->with('message', __('Ticket answered successfully'));
    }
}
