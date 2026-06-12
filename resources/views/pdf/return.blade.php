<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Retur Barang</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12px;
            margin: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 12px;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        .subtitle {
            text-align: center;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            vertical-align: top;
            padding: 2px 5px;
        }
        .info-table .label {
            width: 150px;
            font-weight: bold;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .content-table th, .content-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .content-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .content-table .text-center {
            text-align: center;
        }
        .footer {
            width: 100%;
            margin-top: 40px;
        }
        .footer-table {
            width: 100%;
            text-align: right;
        }
        .footer-table td {
            padding-top: 60px;
        }
        .signature-line {
            display: inline-block;
            width: 150px;
            border-top: 1px solid #000;
            margin-top: 10px;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>PT Tunas Artha - Warehouse Information System</h1>
        <p>Jl. Contoh Perusahaan No. 123, Kota Industri, Indonesia</p>
        <p>Telp: (021) 1234567 | Email: info@tunasartha.com</p>
    </div>

    <div class="title">SURAT PENGEMBALIAN BARANG (RETUR)</div>
    <div class="subtitle">Nomor: RET-{{ str_pad($id_return, 4, '0', STR_PAD_LEFT) }}</div>

    <table class="info-table">
        <tr>
            <td class="label">Tanggal Retur</td>
            <td>: {{ \Carbon\Carbon::parse($return_date)->translatedFormat('d F Y') }}</td>
            <td class="label">Penerima (Supplier)</td>
            <td>: {{ $supplier_name }}</td>
        </tr>
        <tr>
            <td class="label">Referensi Inbound</td>
            <td>: {{ str_pad($id_inbound, 4, '0', STR_PAD_LEFT) }}</td>
            <td class="label">Tanggal Inbound</td>
            <td>: {{ \Carbon\Carbon::parse($inbound_date)->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 40px;">No.</th>
                <th>Nama Barang</th>
                <th style="width: 80px;">Quantity</th>
                <th style="width: 100px;">Kondisi</th>
                <th>Alasan Retur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->display_name ?? ($item->barang ? $item->barang->nama_barang : 'Unknown') }}</td>
                <td class="text-center">{{ $item->qty }}</td>
                <td>{{ $item->kondisi }}</td>
                <td>{{ $item->alasan }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p>Demikian surat retur barang ini dibuat sebagai bukti pengembalian barang yang tidak sesuai / rusak kepada pihak supplier. Harap diterima dan ditindaklanjuti sebagaimana mestinya.</p>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    <strong>Pembuat (Warehouse)</strong>
                    <br><br><br><br>
                    <span class="signature-line">{{ $user_name }}</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
