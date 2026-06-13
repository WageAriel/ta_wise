<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Penawaran</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
        }
        .header-left {
            margin-bottom: 30px;
        }
        .header-left p {
            margin: 0;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            margin-bottom: 30px;
            text-decoration: underline;
        }
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .info-table td {
            vertical-align: top;
            padding: 3px 0;
        }
        .pernyataan-list {
            margin-top: 0;
            padding-left: 20px;
        }
        .pernyataan-list li {
            margin-bottom: 10px;
            text-align: justify;
        }
        .signature-table {
            width: 100%;
            margin-top: 50px;
        }
        .signature-table td {
            vertical-align: top;
        }
        .sign-box {
            border: 1px solid #000;
            width: 100%;
        }
        .sign-box-inner {
            padding: 5px;
        }
        .sign-box-header {
            border-bottom: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .sign-box-footer {
            border-top: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 10pt;
        }
    </style>
</head>
<body>

    <div class="header-left">
        <p>WISE</p>
        <p>Warehouse</p>
    </div>

    <div class="title">
        SURAT PENAWARAN {{ strtoupper($po->items->first()->barang->nama_barang ?? 'BARANG') }} 
    </div>

    <div class="section-title">DATA INFORMASI SUPPLIER</div>
    <table class="info-table">
        <tr>
            <td width="20%">Nama Supplier</td>
            <td width="2%">:</td>
            <td width="35%">{{ strtoupper($po->supplier->nama_perusahaan ?? '-') }}</td>
            <td width="15%">Nama Bank</td>
            <td width="2%">:</td>
            <td width="26%">{{ strtoupper($po->supplier->nama_bank ?? '-') }}</td>
        </tr>
        <tr>
            <td>Alamat Supplier</td>
            <td>:</td>
            <td>{{ strtoupper($po->supplier->alamat_perusahaan ?? '-') }}</td>
            <td>Nomor Rekening</td>
            <td>:</td>
            <td>{{ $po->supplier->no_rekening ?? '-' }}</td>
        </tr>
        <tr>
            <td>Nomor Supplier</td>
            <td>:</td>
            <td>{{ $po->supplier->no_telp_perusahaan ?? '-' }}</td>
            <td>Nama PIC</td>
            <td>:</td>
            <td>{{ strtoupper($po->supplier->nama_pic ?? '-') }}</td>
        </tr>
        <tr>
            <td>Email Supplier</td>
            <td>:</td>
            <td>{{ strtolower($po->supplier->email_perusahaan ?? '-') }}</td>
            <td>Nomor PIC</td>
            <td>:</td>
            <td>{{ $po->supplier->no_telp_pic ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">PERNYATAAN SUPPLIER</div>
    <ol class="pernyataan-list">
        <li>Bersedia memenuhi seluruh pernyataan, ketentuan, prosedur maupun instruksi yang berlaku dalam Pengadaan {{ strtoupper($po->items->first()->barang->nama_barang ?? 'BARANG') }}.</li>
        <li>Menyampaikan penawaran barang sebagai berikut:
            <table style="margin-top: 10px; margin-left: 20px;">
                <tr>
                    <td width="150">a. Jenis Barang</td>
                    <td>: {{ strtoupper($po->items->first()->barang->nama_barang ?? '') }}</td>
                </tr>
                <tr>
                    <td>b. Tipe Barang</td>
                    <td>: {{ implode(' / ', $po->items->map(fn($item) => strtoupper($item->subtype->subtype_name ?? $item->itemType->type_name ?? ''))->toArray()) }}</td>
                </tr>
                <tr>
                    <td>c. Quantity</td>
                    <td>: {{ number_format($po->items->sum('quantity') ?? 0, 0, ',', '.') }} {{ $po->items->first()->uom ?? 'Kg' }}</td>
                </tr>
            </table>
        </li>
        <li>Bersedia menjamin kualitas barang sesuai dengan penawaran yang diberikan.</li>
    </ol>

    <table class="signature-table">
        <tr>
            <td width="40%">
                <div class="sign-box">
                    <div class="sign-box-inner" style="border-bottom: 1px solid #000; height: 40px; text-align: left; padding: 5px;">
                        Tempat : Warehouse<br>
                        Tanggal : {{ date('d F Y') }}
                    </div>
                    <div class="sign-box-inner" style="height: 100px;"></div>
                    <div class="sign-box-footer" style="height: 25px; padding-top: 10px;">
                        {{ strtoupper($po->supplier->nama_perusahaan ?? 'NAMA PEMASOK') }}
                    </div>
                </div>
            </td>
            <td width="20%"></td>
            <td width="40%">
                <div class="sign-box">
                    <div class="sign-box-inner" style="border-bottom: 1px solid #000; height: 40px; text-align: center; padding: 5px;">
                        <br>SUPPLIER
                    </div>
                    <div class="sign-box-inner" style="height: 100px;"></div>
                    <div class="sign-box-footer" style="height: 25px; padding-top: 10px;">
                        &nbsp;
                    </div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
