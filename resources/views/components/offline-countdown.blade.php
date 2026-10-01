@php
    /**
     * Live countdown to an offline card-to-card payment deadline.
     *
     * The absolute Unix deadline is rendered once and every tick re-derives the
     * remaining seconds from the browser clock, so a tab left open overnight --
     * or a machine that was asleep -- cannot drift the way a server-rendered
     * relative offset does.
     *
     * @var \App\Models\Invoice $invoice
     * @var bool $paused  Hide the timer (a receipt is already under review).
     * @var bool $compact Render only the timer, without the surrounding badge.
     */
    $paused = $paused ?? false;
    $compact = $compact ?? false;
    $deadline = $invoice->offlinePaymentDeadline();
    $expired = $invoice->isOfflinePaymentExpired();
@endphp

@if(! $paused && $deadline && ! $expired)
    @if($compact)
        <span data-deadline-countdown
              data-deadline-at="{{ $deadline->timestamp }}"
              data-expired-text="{{ __('Expired') }}"
              class="font-fanum"
              dir="ltr">&hellip;</span>
    @else
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-fanum fs-11 d-inline-flex align-items-center gap-1">
            <i class="ri-timer-line"></i>
            <span data-deadline-countdown
                  data-deadline-at="{{ $deadline->timestamp }}"
                  data-expired-text="{{ __('Expired') }}"
                  dir="ltr">&hellip;</span>
        </span>
    @endif
@endif

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var nodes = document.querySelectorAll('[data-deadline-countdown]');
            if (!nodes.length) return;

            nodes.forEach(function (node) {
                var deadlineAt = parseInt(node.getAttribute('data-deadline-at') || '0', 10);
                var expiredText = node.getAttribute('data-expired-text') || 'Expired';
                var timer = null;
                var firedExpiry = false;

                // Re-derive from the wall clock on every read: this is what
                // keeps the timer honest across tab sleeps and clock skew.
                function remaining() {
                    return Math.max(0, deadlineAt - Math.floor(Date.now() / 1000));
                }

                function render() {
                    var seconds = remaining();

                    if (seconds <= 0) {
                        node.textContent = expiredText;
                        freeze();
                        return;
                    }

                    var hours = Math.floor(seconds / 3600);
                    var minutes = Math.floor((seconds % 3600) / 60);
                    var secs = seconds % 60;
                    var parts = [];

                    if (hours > 0) parts.push(hours);
                    parts.push(minutes, secs);

                    node.textContent = parts.map(function (part) {
                        return part < 10 ? '0' + part : String(part);
                    }).join(':');
                }

                // Once the deadline passes, disable every receipt-upload
                // affordance on the page and refresh so the server-side
                // status (FAILED) takes over.
                function freeze() {
                    if (timer) {
                        clearInterval(timer);
                        timer = null;
                    }

                    document.querySelectorAll('[data-deadline-expired-hide]').forEach(function (el) {
                        el.setAttribute('disabled', 'disabled');
                        el.classList.add('disabled');
                    });

                    if (!firedExpiry) {
                        firedExpiry = true;
                        window.setTimeout(function () { window.location.reload(); }, 4000);
                    }
                }

                render();
                if (timer === null) timer = window.setInterval(render, 1000);

                // A backgrounded tab throttles timers; re-sync on return.
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) render();
                });
                window.addEventListener('focus', render);
            });
        });
    </script>
@endonce