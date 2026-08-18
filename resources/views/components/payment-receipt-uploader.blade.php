{{--
  Shared payment-receipt uploader (dropzone + file chips).
  Required: $invoice (Invoice), optional $inputId
--}}
@php
    $inputId = $inputId ?? ('receipts-'.($invoice->id ?? 'form'));
    $formId = $formId ?? ('receipt-form-'.($invoice->id ?? 'form'));
    $uploaderDeadline = $invoice->offlinePaymentDeadline();
    $uploaderIsExpired = $invoice->isOfflinePaymentExpired();
@endphp
<form id="{{ $formId }}"
      class="receipt-uploader no-print"
      action="{{ route('client.invoice.receipts.store', $invoice) }}"
      method="post"
      enctype="multipart/form-data"
      data-receipt-uploader>
    @csrf
    <div class="receipt-uploader__head">
        <div class="receipt-uploader__icon" aria-hidden="true">
            <i class="ri-upload-cloud-2-line"></i>
        </div>
        <div>
            <strong class="receipt-uploader__title">{{ __('Upload your payment receipt') }}</strong>
            <p class="receipt-uploader__hint mb-0">
                {{ __('After transferring the money, upload a clear photo or PDF of the receipt so we can confirm your payment.') }}
            </p>
            @if($uploaderDeadline)
                <p class="receipt-uploader__hint receipt-uploader__deadline mb-0">
                    @if($uploaderIsExpired)
                        <i class="ri-error-warning-line"></i>
                        {{ __('The offline payment deadline has passed.') }}
                    @else
                        <i class="ri-time-line"></i>
                        {{ __('You must pay and upload the receipt before :deadline.', ['deadline' => $uploaderDeadline->format('Y-m-d H:i')]) }}
                    @endif
                </p>
            @endif
        </div>
    </div>

    <label class="receipt-dropzone" for="{{ $inputId }}" data-receipt-dropzone tabindex="0">
        <input id="{{ $inputId }}"
               type="file"
               name="receipts[]"
               multiple
               accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
               class="receipt-dropzone__input @error('receipts') is-invalid @enderror @error('receipts.*') is-invalid @enderror"
               data-receipt-input>
        <span class="receipt-dropzone__visual">
            <i class="ri-image-add-line"></i>
            <span class="receipt-dropzone__cta">{{ __('Choose files or drop them here') }}</span>
            <span class="receipt-dropzone__meta">{{ __('JPG, PNG, WEBP or PDF — up to 5MB each') }}</span>
        </span>
    </label>

    <ul class="receipt-file-list" data-receipt-file-list hidden></ul>

    @error('receipts')
        <div class="receipt-uploader__error">{{ $message }}</div>
    @enderror
    @error('receipts.*')
        <div class="receipt-uploader__error">{{ $message }}</div>
    @enderror

    <button type="submit" class="receipt-uploader__submit" data-receipt-submit disabled>
        <i class="ri-send-plane-2-line"></i>
        {{ __('Send receipt(s)') }}
    </button>
</form>
