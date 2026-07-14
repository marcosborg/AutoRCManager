<script>
    $(function () {
        function toggleLotPaymentType() {
            var isTradeIn = $('input[name="payment_type"]:checked').val() === 'trade_in';

            $('#lot-payment-trade-in-fields').toggle(isTradeIn);
            $('#lot-payment-method-field, #lot-payment-proof-field, #lot-payment-classification-fields').toggle(!isTradeIn);
            $('#lot_payment_method_id').prop('required', !isTradeIn);
        }

        $('input[name="payment_type"]').on('change', toggleLotPaymentType);
        $('#fill-lot-balance').on('click', function () {
            $('#lot_payment_amount').val($(this).data('balance'));
        });

        toggleLotPaymentType();
    });
</script>
