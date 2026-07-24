@php
    use App\Models\Infocabinet;
    $cabFooter = null;
    if (isset($cabinetId)) {
        $cabFooter = Infocabinet::find($cabinetId);
    } elseif (isset($cabinet) && is_object($cabinet) && $cabinet instanceof Infocabinet) {
        $cabFooter = $cabinet;
    }
    if (!$cabFooter) {
        $cabFooter = Infocabinet::first();
    }
@endphp
@if($cabFooter && $cabFooter->piedPage)
<div class="footer" style="text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #6b7280;">
    {{ $cabFooter->piedPage }}
</div>
@else
<div class="footer" style="margin-top: 30px;"></div>
@endif
