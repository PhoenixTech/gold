@extends('layouts.app')

@section('title', __('Help').' |')

@section('content')
    <section class="help-page" aria-label="{{ __('Help') }}">
        <div class="help-topics" aria-label="{{ __('Help topics') }}">
            <p class="help-topics-kicker">{{ __('Help topics') }}</p>
            <nav>
                <ul>
                    @foreach($topics as $topic)
                        <li>
                            <a href="{{ route('admin.help', ['topic' => $topic['slug']]) }}"
                               class="help-topic-link @if($current['slug'] === $topic['slug']) is-active @endif"
                               @if($current['slug'] === $topic['slug']) aria-current="page" @endif
                            >
                                <i class="{{ $topic['icon'] }}"></i>
                                <span>{{ $topic['title'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
        <div class="help-article">
            @include($topicView)
        </div>
    </section>
@endsection
