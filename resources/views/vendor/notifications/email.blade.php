<x-mail::message>
{{-- Greeting --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# @lang('Whoops!')
@else
# @lang('Hello!')
@endif
@endif

{{-- Intro Lines --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Action Button --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Outro Lines --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Salutation --}}
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards'),<br>
{{ config('app.name') }}
@endif

{{-- Subcopy --}}

<x-slot:subcopy>
@isset($actionText)
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    [
        'actionText' => $actionText,
    ]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
@endisset

Koyn makes it easy to sell your crypto for cash, fast. Withdraw directly to your bank, pay bills and more.

<span style="padding-top: 15px">You are receiving this email because you signed up on {{ env('APP_NAME') }}.
    <table>
        <tr>
            <td>
                <a href="facebook.com/{{ env('APP_NAME') }}" style="padding-right:10px">
                    <img src="{{ asset('images/facebook.png') }}" alt="">
                </a>
            </td>
            <td>
                <a href="instagram.com/{{ env('APP_NAME') }}" style="">
                    <img src="{{ asset('images/instagram.png') }}" alt="">
                </a>
            </td>
        </tr>
    </table>
</div>
</x-slot:subcopy>

</x-mail::message>
