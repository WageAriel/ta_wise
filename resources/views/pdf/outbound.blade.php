<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan / Pengeluaran Barang</title>
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

    <div class="title">SURAT JALAN / PENGELUARAN BARANG</div>
    <div class="subtitle">Nomor: OUT-{{ str_pad($outbound->id_outbound, 4, '0', STR_PAD_LEFT) }}</div>

    <table class="info-table">
        <tr>
            <td class="label">Tanggal Keluar</td>
            <td>: {{ \Carbon\Carbon::parse($outbound->tanggal)->translatedFormat('d F Y') }}</td>
            <td class="label">Tujuan (Penerima)</td>
            <td>: {{ $outbound->tujuan }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>: {{ $outbound->status }}</td>
            <td class="label">Pembuat / Petugas</td>
            <td>: {{ $outbound->user ? $outbound->user->username : 'Administrator' }}</td>
        </tr>
    </table>

    <table class="content-table">
        <thead>
            <tr>
                <th style="width: 50px;">No.</th>
                <th>Nama Barang</th>
                <th>Lokasi</th>
                <th style="width: 100px;">Quantity</th>
            </tr>
        </thead>
        <tbody>
            @foreach($outbound->details as $index => $detail)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $detail->barang ? $detail->barang->nama_barang : 'Unknown' }}</td>
                <td>{{ ($detail->barang && $detail->barang->inventories->count() > 0 && $detail->barang->inventories[0]->location) ? $detail->barang->inventories[0]->location->kode_location : '-' }}</td>
                <td class="text-center">{{ $detail->qty }} {{ $detail->barang ? $detail->barang->satuan : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p>Demikian surat jalan / pengeluaran barang ini dibuat untuk dipergunakan sebagaimana mestinya. Barang telah diserahkan dan diterima dalam keadaan baik dan sesuai dengan rincian di atas.</p>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    <strong>Pembuat (Warehouse)</strong>
                    <br><br><br><br>
                    <span class="signature-line">{{ $outbound->user ? $outbound->user->username : 'Petugas Gudang' }}</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
