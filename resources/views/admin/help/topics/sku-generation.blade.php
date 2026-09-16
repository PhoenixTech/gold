<header class="help-article-header">
    <p class="help-article-eyebrow">{{ config('app.name') }}</p>
    <h1>{{ __('How product and piece SKU codes work') }}</h1>
    <p class="help-article-lead">
        {{ __('Product and piece codes follow a 5-part structure combining gender, metal, category, sequence number, and delivered piece counter.') }}
    </p>
</header>

<ol class="help-steps">
    <li>
        <span class="help-step-num" aria-hidden="true">1</span>
        <div>
            <h2>{{ __('Step 1: Gender (Letter)') }}</h2>
            <p>
                {!! __('Choose :women for Women, :men for Men, or :children for Children. This forms the first letter of the SKU.', [
                    'women' => '<strong>'.e(__('Women\'s')).' (F)</strong>',
                    'men' => '<strong>'.e(__('Men\'s')).' (M)</strong>',
                    'children' => '<strong>'.e(__('Children\'s')).' (C)</strong>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">2</span>
        <div>
            <h2>{{ __('Step 2: Metal (Digit)') }}</h2>
            <p>
                {!! __(':gold uses digit :one, and :silver uses digit :two.', [
                    'gold' => '<strong>'.e(__('Gold')).'</strong>',
                    'one' => '<code class="fw-bold">1</code>',
                    'silver' => '<strong>'.e(__('Silver')).'</strong>',
                    'two' => '<code class="fw-bold">2</code>',
                ]) !!}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">3</span>
        <div>
            <h2>{{ __('Step 3: Main Category (Code)') }}</h2>
            <p>
                {{ __('Each of the 11 main categories has an official code letter: Ring (A), Necklace (Gr), Earring (E), Bracelet (D), Bangle (L), Anklet (P), Chain (Z), Accessory (Ac), Piercing (Pr), Set (Set), Ingot (Sh).') }}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">4</span>
        <div>
            <h2>{{ __('Step 4: Product Sequence (4 Digits)') }}</h2>
            <p>
                {{ __('A 4-digit sequential counter of products within that category (e.g. 0001, 0002).') }}
            </p>
        </div>
    </li>
    <li>
        <span class="help-step-num" aria-hidden="true">5</span>
        <div>
            <h2>{{ __('Step 5: Stock Piece Counter (5 Digits)') }}</h2>
            <p>
                {{ __('Each inventory piece receives a 5-digit number separated by a hyphen, counting pieces delivered to customers (e.g. -00001 to -99999).') }}
            </p>
        </div>
    </li>
</ol>

<p class="help-note">
    <strong>{{ __('Example SKU:') }}</strong>
    {!! __('For a woman’s gold ring product #1, piece #5: Product SKU is :prod and Piece SKU is :piece.', [
        'prod' => '<code class="font-monospace fw-bold">F1A0001</code>',
        'piece' => '<code class="font-monospace fw-bold">F1A0001-00005</code>',
    ]) !!}
</p>
