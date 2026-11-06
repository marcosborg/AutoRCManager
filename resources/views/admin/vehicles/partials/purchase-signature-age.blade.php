@if(($signatureWaitingDays = $followup->purchaseSignatureAgeAlertDays()) !== null)
    <br><span class="label label-warning">À espera de assinatura há {{ $signatureWaitingDays }} dias</span>
@endif
