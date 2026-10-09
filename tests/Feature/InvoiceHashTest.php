<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceHashTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_automatically_generates_an_eight_digit_numeric_hash(): void
    {
        $customer = Customer::factory()->create();

        $invoice = new Invoice();
        $invoice->customer_id = $customer->id;
        $invoice->total_price = 500000;
        $invoice->save();

        $this->assertNotNull($invoice->hash);
        $this->assertSame(8, strlen($invoice->hash));
        $this->assertTrue(ctype_digit($invoice->hash));
        $this->assertGreaterThanOrEqual(10000000, (int) $invoice->hash);
        $this->assertLessThanOrEqual(99999999, (int) $invoice->hash);
    }

    public function test_multiple_invoices_generate_unique_hashes(): void
    {
        $customer = Customer::factory()->create();

        $hashes = [];
        for ($i = 0; $i < 10; $i++) {
            $invoice = new Invoice();
            $invoice->customer_id = $customer->id;
            $invoice->total_price = 100000;
            $invoice->save();

            $hashes[] = $invoice->hash;
        }

        $this->assertCount(10, array_unique($hashes));
    }

    public function test_explicitly_set_hash_is_not_overwritten(): void
    {
        $customer = Customer::factory()->create();

        $invoice = new Invoice();
        $invoice->customer_id = $customer->id;
        $invoice->total_price = 100000;
        $invoice->hash = '88889999';
        $invoice->save();

        $this->assertSame('88889999', $invoice->fresh()->hash);
    }

    public function test_resolves_admin_model_finds_invoice_by_numeric_hash(): void
    {
        $customer = Customer::factory()->create();

        $invoice = new Invoice();
        $invoice->customer_id = $customer->id;
        $invoice->total_price = 100000;
        $invoice->save();

        $resolver = new class {
            use ResolvesAdminModel;

            public function resolve(string $class, mixed $item): Invoice
            {
                return $this->resolveModel($class, $item);
            }
        };

        $resolved = $resolver->resolve(Invoice::class, $invoice->hash);

        $this->assertSame($invoice->id, $resolved->id);
        $this->assertSame($invoice->hash, $resolved->hash);
    }

    public function test_contact_automatically_generates_an_eight_digit_numeric_hash(): void
    {
        $contact = new \App\Models\Contact();
        $contact->name = 'Test User';
        $contact->email = 'test@example.com';
        $contact->mobile = '09121234567';
        $contact->body = 'Hello world';
        $contact->save();

        $this->assertNotNull($contact->hash);
        $this->assertSame(8, strlen($contact->hash));
        $this->assertTrue(ctype_digit($contact->hash));
        $this->assertGreaterThanOrEqual(\App\Models\Contact::HASH_MIN, (int) $contact->hash);
        $this->assertLessThanOrEqual(\App\Models\Contact::HASH_MAX, (int) $contact->hash);
    }
}
