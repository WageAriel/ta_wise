<!DOCTYPE html>
<html>
<head>
    <title>Return Detail INB-{{ $id_inbound }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h2 { margin: 0; padding: 0; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px 0; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background-color: #f4f4f4; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Pengembalian Barang (Return)</h2>
        <p>Inbound ID: {{ $id_inbound }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="30%"><strong>Nama Supplier</strong></td>
            <td width="2%">:</td>
            <td>{{ $supplier_name }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal Masuk Barang</strong></td>
            <td>:</td>
            <td>{{ $inbound_date }}</td>
        </tr>
        <tr>
            <td><strong>Tanggal Return</strong></td>
            <td>:</td>
            <td>{{ $return_date }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" width="5%">No</th>
                <th>Nama Barang</th>
                <th class="text-center" width="10%">Qty</th>
                <th width="20%">Kondisi</th>
                <th width="30%">Alasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->barang ? $item->barang->nama_barang : 'Unknown' }}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td>{{ $item->kondisi }}</td>
                <td>{{ $item->alasan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
