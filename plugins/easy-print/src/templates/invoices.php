<div class="row mb-4">
    <div class="col-12">
        <table class="table table-hover table-bordered bg-light mb-0">
            <thead class="thead-dark">
            <tr>
                <th scope="col">Invoice</th>
                <th scope="col">Total invoiced</th>
                <th scope="col">Print</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $invoice) { ?>
                <tr>
                    <td><?php echo $invoice['number']; ?></td>
                    <td><?php echo number_format($invoice['total'], 2) . ' ' . $invoice['currencyCode']; ?></td>
                    <td><?php echo "<a href='javascript:printJs('https://horizon.tecmeet.com/crm/billing/invoice/" . $invoice['number'] . "/download-pdf')'>Print PDF</a>"; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>