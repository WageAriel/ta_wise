<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Permohonan Order Pembelian</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #000;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-box {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .header-box h2 {
            margin: 0;
            font-size: 14pt;
        }
        .header-box h3 {
            margin: 0;
            font-size: 12pt;
        }
        .border-table th, .border-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .box-top {
            width: 100%;
            margin-bottom: 20px;
        }
        .box-top td {
            vertical-align: top;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .mt-4 { margin-top: 20px; }
        .mb-2 { margin-bottom: 10px; }
    </style>
</head>
<body>

    <!-- Nama Pemasok (Kanan atas) -->
    <div style="text-align: center; margin-bottom: 30px;">
        <h3 style="margin:0; font-size: 12pt;">{{ strtoupper($po->supplier->nama_perusahaan ?? 'NAMA PEMASOK') }}</h3>
        <p style="margin:0;">{{ strtoupper($po->supplier->alamat ?? 'ALAMAT PEMASOK') }}</p>
        <p style="margin:0;">NO {{ strtoupper($po->po_number) }}</p>
    </div>

    <!-- Kotak Agenda -->
    <table class="box-top">
        <tr>
            <td width="45%">
                <table class="border-table">
                    <tr><td width="45%" style="text-align:left;">AGENDA NOMOR</td><td></td></tr>
                    <tr><td style="text-align:left;">TANGGAL TERIMA</td><td></td></tr>
                    <tr><td style="text-align:left;">PARAF</td><td></td></tr>
                </table>
            </td>
            <td width="10%"></td>
            <td width="45%">
                <table class="border-table">
                    <tr><td width="45%" style="text-align:left;">KONTRAK YLL</td><td></td></tr>
                    <tr><td style="text-align:left;">REALISASI S/D</td><td></td></tr>
                    <tr><td style="text-align:left;">DISETUJUI/TIDAK</td><td></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="header-box" style="margin-top: 30px;">
        <h2>SURAT PERMOHONAN ORDER PEMBELIAN (OP)</h2>
        <h3>PENGADAAN {{ strtoupper($po->items->first()->barang->nama_barang ?? 'BARANG') }} TAHUN {{ date('Y') }}</h3>
    </div>

    <div style="margin-bottom: 10px;">
        Kepada Yth.<br>
        Manajer WISE<br>
        Di Tempat
    </div>

    <p style="text-align: justify;">
        Bersama ini kami {{ strtoupper($po->supplier->nama_perusahaan ?? 'Pemasok') }} bermohon untuk ikut serta dalam rangka pengadaan {{ strtoupper($po->items->first()->barang->nama_barang ?? 'Barang') }} tahun {{ date('Y') }} dengan mengajukan penawaran untuk menyediakan komoditas sebagai berikut :
    </p>

    <table class="border-table" style="margin-top: 15px; margin-bottom: 15px;">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Deskripsi</th>
                <th width="15%">Quantity</th>
                <th width="25%">Harga Unit</th>
                <th width="25%">Total Harga</th>
            </tr>
        </thead>
        <tbody>
            @foreach($po->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ strtoupper($item->barang->nama_barang ?? '') }} {{ strtoupper($item->subtype->subtype_name ?? $item->itemType->type_name ?? '') }}</td>
                <td>{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->uom ?? 'Kg' }}</td>
                <td>Rp {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td>Rp {{ number_format($item->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL KESELURUHAN</td>
                <td style="font-weight: bold;">Rp {{ number_format($po->total_price ?? $po->items->sum('subtotal'), 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p style="text-align: justify;">
        Kami bersedia memenuhi ketentuan yang tercantum dalam order pembelian {{ strtoupper($po->items->first()->barang->nama_barang ?? 'Barang') }} 
    </p>
    <p>
        Demikian disampaikan dan atas persetujuan dan kerja samanya diucapkan terima kasih.
    </p>

    <table style="width: 100%; margin-top: 30px;">
        <tr>
            <!-- Box Kiri Bawah (AM Pengadaan) -->
            <td width="50%" style="vertical-align: bottom;">
                <table class="border-table" style="width: 80%; text-align: left;">
                    <tr>
                        <th colspan="2" style="background-color: #f2f2f2; text-align:center;">WAREHOUSE</th>
                    </tr>
                    <tr>
                        <td width="40%">PERSETUJUAN</td>
                        <td width="60%" style="text-align:center;">{{ number_format($po->items->sum('quantity') ?? 0, 0, ',', '.') }} {{ $po->items->first()->uom ?? 'Kg' }}</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="height: 80px; vertical-align:top; text-align:left;">
                            Disposisi:<br>
                            [ ] Cek dan Koordinasikan<br>
                            [ ] Tindak Lanjut Sesuai Ketentuan<br>
                            [ ] Tertib Administrasi<br>
                            [ ] .........................................
                        </td>
                    </tr>
                </table>
            </td>
            <!-- Tanda Tangan Kanan Bawah -->
            <td width="50%" style="vertical-align: bottom; text-align: center;">
                <p>Warehouse, {{ date('d F Y') }}</p>
                <p style="margin-bottom: 80px;">Pemohon</p>
                <p style="text-decoration: underline; font-weight: bold; margin-bottom: 0;">{{ strtoupper($po->supplier->kelas_label ?? '.............') }}</p>
                <p style="margin-top: 0;">{{ strtoupper($po->supplier->nama_perusahaan ?? '') }}</p>
            </td>
        </tr>
    </table>

</body>
</html>
