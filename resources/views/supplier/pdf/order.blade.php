<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Order Pembelian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }
        .header-logo {
            font-size: 24pt;
            font-weight: bold;
            font-style: italic;
            color: #004d99; /* Approximate Bulog Blue */
            margin-bottom: 5px;
        }
        .header-subtitle {
            font-size: 10pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 30px;
        }
        .address-box {
            width: 100%;
            margin-bottom: 30px;
        }
        .address-box td {
            vertical-align: top;
        }
        .title-center {
            text-align: center;
            margin-bottom: 20px;
        }
        .title-center h2 {
            margin: 0;
            text-decoration: underline;
            font-size: 14pt;
        }
        .title-center h3 {
            margin: 0;
            font-size: 12pt;
            font-weight: normal;
        }
        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }
        .info-table th {
            text-align: left;
            font-weight: bold;
            padding-bottom: 5px;
        }
        .info-table td {
            vertical-align: top;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            border-bottom: 2px solid #000;
            padding: 8px 5px;
            text-align: right;
        }
        .items-table th:first-child, .items-table td:first-child {
            text-align: left;
        }
        .items-table td {
            padding: 8px 5px;
            vertical-align: top;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        .totals-table {
            width: 50%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        .totals-table td {
            padding: 5px;
        }
        .totals-table tr:first-child td {
            border-top: 1px solid #000;
        }
        .totals-table tr:last-child td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }
        .clearfix {
            clear: both;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
        }
        .signature-table td {
            vertical-align: top;
            width: 50%;
        }
        .footer {
            margin-top: 50px;
            font-size: 9pt;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header-logo">Wise</div>

    <table class="address-box">
        <tr>
            <td width="60%">
                <strong>Alamat Pengiriman:</strong><br>
                Warehouse <br>
                Wise
            </td>
            <td width="40%">
                <strong>Supplier:</strong><br>
                {{ strtoupper($po->supplier->nama_perusahaan ?? 'NAMA PEMASOK') }}
            </td>
        </tr>
    </table>

    <div class="title-center">
        <h2>Order Pembelian</h2>
        <h3>{{ $po->po_number }}</h3>
    </div>

    <table class="info-table">
        <tr>
            <th width="40%">Referensi Pesanan:</th>
            <th width="30%">Tanggal Pemesanan:</th>
            <th width="30%">Masa Berlaku PO:</th>
        </tr>
        <tr>
            <td>
                NO OP/{{ $po->po_number }}<br>
                {{ strtoupper($po->supplier->nama_perusahaan ?? '') }}/{{ strtoupper($po->items->first()->barang->nama_barang ?? '') }}<br>
                {{ number_format($po->items->sum('quantity') ?? 0, 0, ',', '.') }} {{ $po->items->first()->uom ?? 'Kg' }}/{{ strtoupper($po->supplier->nama_perusahaan ?? '') }}
            </td>
            <td>{{ \Carbon\Carbon::parse($po->created_at)->format('d-m-Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($po->created_at)->addDays(7)->format('d-m-Y') }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="35%">Deskripsi</th>
                <th width="15%">Quantity</th>
                <th width="25%">Harga Unit</th>
                <th width="25%">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $item)
            <tr>
                <td>{{ strtoupper($item->barang->nama_barang ?? '') }} {{ strtoupper($item->subtype->subtype_name ?? $item->itemType->type_name ?? '') }}</td>
                <td>{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->uom ?? 'Kg' }}</td>
                <td>Rp {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td>Rp {{ number_format($item->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Total Sebelum Pajak</td>
            <td style="text-align: right;">Rp {{ number_format($po->total_price, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Pajak</td>
            <td style="text-align: right;">Rp 0,00</td>
        </tr>
        <tr>
            <td>Total Pembelian</td>
            <td style="text-align: right;">Rp {{ number_format($po->total_price, 2, ',', '.') }}</td>
        </tr>
    </table>
    <div class="clearfix"></div>

    <table class="signature-table">
        <tr>
            <td style="text-align: left; padding-left: 50px;">
                Yang Menerima,<br>
                Supplier<br>
                <br><br><br><br><br>
                <strong>........................................</strong>
            </td>
            <td style="text-align: center;">
                Surakarta, {{ date('d-m-Y') }}<br>
                Yang Menyerahkan,<br>
                <br><br><br><br><br>
                <strong>........................................</strong>
            </td>
        </tr>
    </table>

    <div class="footer">
        <table width="100%">
            <tr>
                <td>Dicetak Pada waktu</td>
                <td>:</td>
                <td>{{ date('d-m-Y | H:i') }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
