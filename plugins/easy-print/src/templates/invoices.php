<div class="row mb-4">
    <div class="col-12">
        <table class="table table-hover table-bordered bg-light mb-0">
            <thead class="thead-dark">
            <tr>
                <th scope="col">Date</th>
                <th scope="col">Document</th>
                <th scope="col">Name</th>
                <th scope="col">Total</th>
                <th scope="col">Print</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($documents as $doc) { ?>
                <tr>
                    <td><?php echo date_format(date_create($doc['createdDate']), "Y-m-d h:i:s A"); ?></td>
                    <td><?php echo $doc['number']; ?></td>
                    <td><?php echo $doc['clientCompanyName'] . $doc['clientFirstName'] . ' ' . $doc['clientLastName']; ?></td>
                    <td><?php echo '$' . number_format($doc['total'], 2); ?></td>
                    <?php if ($doc['type'] == 'payment') { ?>
                        <td><?php echo "<a href='javascript:void(0);' onclick='printJS(\"/crm/billing/payments/" . $doc['number'] . "/pdf-receipt\")'>Print PDF</a>"; ?></td>
                    <?php } else { ?>
                        <td><?php echo "<a href='javascript:void(0);' onclick='printJS(\"/crm/billing/invoice/" . $doc['number'] . "/download-pdf\")'>Print PDF</a>"; ?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>