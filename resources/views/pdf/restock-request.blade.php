@php
    $totalStock = $barang->inventories->sum('qty');
    $isCritical = $totalStock <= ($barang->min_stock * 0.5);
    $statusText = $isCritical ? 'Kritis' : 'Menipis';
    $statusColor = $isCritical ? '#dc3545' : '#fd7e14';
    
    $locations = $barang->inventories->map(function($inv) use ($barang) {
        if ($inv->location && $inv->location->layout) {
            return $inv->location->layout->nama_layout . ' - ' . $inv->location->kode_location . ' (' . $inv->qty . ' ' . $barang->satuan . ')';
        }
        return 'Unassigned (' . $inv->qty . ' ' . $barang->satuan . ')';
    })->join('<br>');
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Permintaan Restock</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            color: #666;
        }
        .date {
            text-align: right;
            margin-bottom: 30px;
        }
        .content {
            margin-bottom: 40px;
        }
        .table-info {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table-info th, .table-info td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }
        .table-info th {
            background-color: #f8f9fa;
            width: 40%;
        }
        .footer {
            margin-top: 50px;
        }
        .signature-box {
            float: right;
            width: 250px;
            text-align: center;
        }
        .signature-space {
            height: 100px;
        }
        .status-badge {
            font-weight: bold;
            color: {{ $statusColor }};
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>WISE INVENTORY MANAGEMENT</h1>
        <p>Surat Pengajuan Restock Barang (Internal)</p>
    </div>

    <div class="date">
        Tanggal: {{ now()->format('d F Y') }}
    </div>

    <div class="content">
        <p>Kepada Yth.,</p>
        <p><strong>Manajer / Owner</strong></p>
        <p>Di Tempat</p>

        <p>Dengan hormat,</p>
        <p>Bersama surat ini, kami memberitahukan bahwa terdapat barang di gudang yang jumlah stoknya secara keseluruhan telah mencapai batas minimum (<strong><span class="status-badge">{{ $statusText }}</span></strong>). Oleh karena itu, kami mengajukan permohonan untuk segera dilakukan proses pengadaan kembali (restock) guna menjaga kelancaran operasional.</p>

        <p>Berikut adalah rincian data barang tersebut:</p>

        <table class="table-info">
            <tr>
                <th>ID Barang</th>
                <td>{{ $barang->id_barang }}</td>
            </tr>
            <tr>
                <th>Nama Barang</th>
                <td>{{ $barang->nama_barang }}</td>
            </tr>
            <tr>
                <th>Total Sisa Stok Saat Ini</th>
                <td style="color: #dc3545; font-weight: bold;">{{ $totalStock }} {{ $barang->satuan }}</td>
            </tr>
            <tr>
                <th>Batas Minimum Stok</th>
                <td>{{ $barang->min_stock ?? 0 }} {{ $barang->satuan }}</td>
            </tr>
            <tr>
                <th>Rincian Lokasi Penyimpanan</th>
                <td>{!! $locations !!}</td>
            </tr>
        </table>

        <p style="margin-top: 20px;">Demikian surat permohonan ini kami sampaikan. Atas perhatian dan kebijaksanaannya, kami ucapkan terima kasih.</p>
    </div>

    <div class="footer">
        <div class="signature-box">
            <p>Mengetahui/Menyetujui,</p>
            <div class="signature-space"></div>
            <p><strong>_____________________</strong></p>
            <p>Manajer / Owner</p>
        </div>
    </div>

</body>
</html>
